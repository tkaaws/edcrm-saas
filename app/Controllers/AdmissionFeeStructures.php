<?php

namespace App\Controllers;

use App\Controllers\Concerns\PaginatesCollections;
use App\Services\FeeStructureService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

class AdmissionFeeStructures extends BaseController
{
    use PaginatesCollections;

    protected FeeStructureService $feeStructureService;

    public function __construct()
    {
        $this->feeStructureService = service('feeStructure');
    }

    public function index(): string|RedirectResponse
    {
        if ($response = $this->ensureSchemaReady()) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        $rows = $this->feeStructureService->getAdminGrid($tenantId);
        $paginated = $this->paginateCollection($rows);

        return view('admissions/fee_structures', $this->buildShellViewData([
            'title' => 'Fee Structures',
            'pageTitle' => 'Fee Structures',
            'activeNav' => 'admissions',
            'admissionsSubnav' => 'fee_structures',
            'rows' => $paginated['items'],
            'pagination' => $paginated['pagination'],
            'courses' => service('masterData')->getEffectiveValues('course', $tenantId),
            'canManageFeeStructures' => service('permissions')->has('fees.structure'),
        ]));
    }

    public function options()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['structures' => []]);
        }

        if ($response = $this->ensureSchemaReady()) {
            return $this->response->setJSON(['error' => 'Fee structures are not ready on this server yet.'])->setStatusCode(503);
        }

        $tenantId = (int) session()->get('tenant_id');
        $courseId = (int) $this->request->getGet('course_id');

        if ($courseId < 1) {
            return $this->response->setJSON(['structures' => []]);
        }

        $structures = array_map(static function (object $row): array {
            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'description' => (string) ($row->description ?? ''),
                'total_amount' => (float) $row->total_amount,
                'default_installment_count' => (int) $row->default_installment_count,
                'default_installment_gap_days' => (int) $row->default_installment_gap_days,
                'items' => array_map(static fn(object $item): array => [
                    'fee_head_name' => (string) $item->fee_head_name,
                    'fee_head_code' => (string) ($item->fee_head_code ?? ''),
                    'amount' => (float) $item->amount,
                    'allow_discount' => (bool) $item->allow_discount,
                ], $row->items ?? []),
            ];
        }, $this->feeStructureService->getActiveOptionsForCourse($tenantId, $courseId));

        return $this->response->setJSON(['structures' => $structures]);
    }

    public function store(): RedirectResponse
    {
        return $this->saveStructure(null);
    }

    public function update(int $id): RedirectResponse
    {
        return $this->saveStructure($id);
    }

    public function delete(int $id): RedirectResponse
    {
        if ($response = $this->ensureSchemaReady()) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');

        try {
            $this->feeStructureService->deleteStructure($tenantId, $id);
            return redirect()->to('/admissions/fee-structures')->with('message', 'Fee structure removed successfully.');
        } catch (RuntimeException $exception) {
            return redirect()->to('/admissions/fee-structures')->with('error', $exception->getMessage());
        }
    }

    protected function saveStructure(?int $id): RedirectResponse
    {
        if ($response = $this->ensureSchemaReady()) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        $payload = [
            'course_id' => (int) $this->request->getPost('course_id'),
            'name' => trim((string) $this->request->getPost('name')),
            'description' => trim((string) $this->request->getPost('description')),
            'default_installment_count' => (int) $this->request->getPost('default_installment_count'),
            'default_installment_gap_days' => (int) $this->request->getPost('default_installment_gap_days'),
            'status' => (string) $this->request->getPost('status'),
            'items' => $this->request->getPost('items') ?? [],
        ];

        try {
            if ($id === null) {
                $this->feeStructureService->createStructure($tenantId, $payload);
                return redirect()->to('/admissions/fee-structures')->with('message', 'Fee structure created successfully.');
            }

            $this->feeStructureService->updateStructure($tenantId, $id, $payload);
            return redirect()->to('/admissions/fee-structures')->with('message', 'Fee structure updated successfully.');
        } catch (RuntimeException $exception) {
            return redirect()->to('/admissions/fee-structures')->withInput()->with('error', $exception->getMessage());
        }
    }

    protected function ensureSchemaReady(): ?RedirectResponse
    {
        foreach (['fee_structures', 'fee_structure_items'] as $table) {
            if (! db_connect()->tableExists($table)) {
                return redirect()->to('/admissions')->with(
                    'error',
                    'Fee structures are not ready on this server yet. Please run the latest database migrations first.'
                );
            }
        }

        return null;
    }
}
