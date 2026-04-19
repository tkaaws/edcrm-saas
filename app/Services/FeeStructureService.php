<?php

namespace App\Services;

use App\Models\AdmissionFeeSnapshotModel;
use App\Models\FeeStructureItemModel;
use App\Models\FeeStructureModel;
use RuntimeException;

class FeeStructureService
{
    protected FeeStructureModel $structureModel;
    protected FeeStructureItemModel $itemModel;
    protected AdmissionFeeSnapshotModel $snapshotModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->structureModel = new FeeStructureModel();
        $this->itemModel = new FeeStructureItemModel();
        $this->snapshotModel = new AdmissionFeeSnapshotModel();
        $this->db = db_connect();
    }

    /**
     * @return array<int, object>
     */
    public function getAdminGrid(int $tenantId): array
    {
        $structures = $this->structureModel->getAdminGrid($tenantId);
        $itemsByStructure = $this->itemModel->getGroupedForStructures(array_map(static fn(object $row): int => (int) $row->id, $structures));

        foreach ($structures as $structure) {
            $structure->items = $itemsByStructure[(int) $structure->id] ?? [];
            $structure->item_count = count($structure->items);
            $structure->fee_head_summary = implode(', ', array_map(
                static fn(object $item): string => (string) $item->fee_head_name,
                array_slice($structure->items, 0, 3)
            ));
        }

        return $structures;
    }

    public function findForTenant(int $tenantId, int $id): ?object
    {
        $structure = $this->structureModel->findForTenant($tenantId, $id);
        if (! $structure) {
            return null;
        }

        $structure->items = $this->itemModel->getForStructure((int) $structure->id);
        return $structure;
    }

    /**
     * @return array<int, object>
     */
    public function getActiveOptionsForCourse(int $tenantId, int $courseId): array
    {
        $structures = $this->structureModel->getActiveOptionsForCourse($tenantId, $courseId);
        $itemsByStructure = $this->itemModel->getGroupedForStructures(array_map(static fn(object $row): int => (int) $row->id, $structures));

        foreach ($structures as $structure) {
            $structure->items = $itemsByStructure[(int) $structure->id] ?? [];
        }

        return $structures;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createStructure(int $tenantId, array $payload): int
    {
        $normalized = $this->normalizePayload($payload);

        if ($this->structureModel->nameExistsForTenantCourse($tenantId, (int) $normalized['course_id'], (string) $normalized['name'])) {
            throw new RuntimeException('A fee structure with this name already exists for the selected course.');
        }

        $this->db->transException(true)->transStart();

        $structureId = (int) $this->structureModel->insertWithActor([
            'tenant_id' => $tenantId,
            'course_id' => (int) $normalized['course_id'],
            'name' => (string) $normalized['name'],
            'description' => $normalized['description'] ?: null,
            'default_installment_count' => (int) $normalized['default_installment_count'],
            'default_installment_gap_days' => (int) $normalized['default_installment_gap_days'],
            'total_amount' => (float) $normalized['total_amount'],
            'status' => (string) $normalized['status'],
        ]);

        $this->writeItems($tenantId, $structureId, $normalized['items']);

        $this->db->transComplete();

        return $structureId;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateStructure(int $tenantId, int $structureId, array $payload): void
    {
        $existing = $this->findForTenant($tenantId, $structureId);
        if (! $existing) {
            throw new RuntimeException('That fee structure is no longer available.');
        }

        $normalized = $this->normalizePayload($payload);

        if ($this->structureModel->nameExistsForTenantCourse($tenantId, (int) $normalized['course_id'], (string) $normalized['name'], $structureId)) {
            throw new RuntimeException('A fee structure with this name already exists for the selected course.');
        }

        $this->db->transException(true)->transStart();

        $this->structureModel->updateWithActor($structureId, [
            'course_id' => (int) $normalized['course_id'],
            'name' => (string) $normalized['name'],
            'description' => $normalized['description'] ?: null,
            'default_installment_count' => (int) $normalized['default_installment_count'],
            'default_installment_gap_days' => (int) $normalized['default_installment_gap_days'],
            'total_amount' => (float) $normalized['total_amount'],
            'status' => (string) $normalized['status'],
        ]);

        $this->itemModel->where('fee_structure_id', $structureId)->delete();
        $this->writeItems($tenantId, $structureId, $normalized['items']);

        $this->db->transComplete();
    }

    public function deleteStructure(int $tenantId, int $structureId): void
    {
        $existing = $this->findForTenant($tenantId, $structureId);
        if (! $existing) {
            throw new RuntimeException('That fee structure is no longer available.');
        }

        $usageCount = $this->snapshotModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('fee_structure_id', $structureId)
            ->countAllResults();

        if ($usageCount > 0) {
            throw new RuntimeException('This fee structure is already used in admissions and cannot be removed.');
        }

        $this->db->transException(true)->transStart();
        $this->itemModel->where('fee_structure_id', $structureId)->delete();
        $this->structureModel->delete($structureId);
        $this->db->transComplete();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $courseId = (int) ($payload['course_id'] ?? 0);
        $description = trim((string) ($payload['description'] ?? ''));
        $status = ($payload['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $defaultInstallmentCount = max(1, (int) ($payload['default_installment_count'] ?? 1));
        $defaultInstallmentGapDays = max(1, (int) ($payload['default_installment_gap_days'] ?? 30));
        $items = $this->normalizeItems($payload['items'] ?? []);

        if ($name === '') {
            throw new RuntimeException('Fee structure name is required.');
        }

        if ($courseId < 1) {
            throw new RuntimeException('Choose the course for this fee structure.');
        }

        if ($items === []) {
            throw new RuntimeException('Add at least one fee head.');
        }

        $totalAmount = array_reduce($items, static fn(float $carry, array $item): float => $carry + (float) $item['amount'], 0.0);

        return [
            'name' => $name,
            'course_id' => $courseId,
            'description' => $description,
            'status' => $status,
            'default_installment_count' => $defaultInstallmentCount,
            'default_installment_gap_days' => $defaultInstallmentGapDays,
            'items' => $items,
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * @param mixed $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];
        $order = 1;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['fee_head_name'] ?? ''));
            $amount = round((float) ($item['amount'] ?? 0), 2);
            if ($name === '' && $amount <= 0) {
                continue;
            }

            if ($name === '') {
                throw new RuntimeException('Each fee head needs a name.');
            }

            if ($amount <= 0) {
                throw new RuntimeException('Each fee head amount must be greater than zero.');
            }

            $normalized[] = [
                'fee_head_name' => $name,
                'fee_head_code' => trim((string) ($item['fee_head_code'] ?? '')) ?: null,
                'amount' => $amount,
                'allow_discount' => ! empty($item['allow_discount']) ? 1 : 0,
                'display_order' => (int) ($item['display_order'] ?? $order) ?: $order,
            ];

            $order++;
        }

        usort($normalized, static fn(array $left, array $right): int => (int) $left['display_order'] <=> (int) $right['display_order']);

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    protected function writeItems(int $tenantId, int $structureId, array $items): void
    {
        foreach ($items as $item) {
            $this->itemModel->insertWithActor([
                'tenant_id' => $tenantId,
                'fee_structure_id' => $structureId,
                'fee_head_name' => $item['fee_head_name'],
                'fee_head_code' => $item['fee_head_code'],
                'amount' => $item['amount'],
                'allow_discount' => $item['allow_discount'],
                'display_order' => $item['display_order'],
            ]);
        }
    }
}
