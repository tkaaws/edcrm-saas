<?php

namespace App\Services;

use App\Models\AdmissionFeeSnapshotItemModel;
use App\Models\AdmissionFeeSnapshotModel;
use App\Models\AdmissionFollowupModel;
use App\Models\AdmissionInstallmentModel;
use App\Models\AdmissionModel;
use App\Models\AdmissionBatchAssignmentHistoryModel;
use App\Models\AdmissionBatchAssignmentModel;
use App\Models\AdmissionPaymentAllocationModel;
use App\Models\AdmissionPaymentModel;
use App\Models\AdmissionStatusLogModel;
use App\Models\BatchModel;
use App\Models\EnquiryModel;
use App\Models\EnquiryStatusLogModel;
use App\Models\FeeStructureItemModel;
use App\Models\FeeStructureModel;
use RuntimeException;

class AdmissionService
{
    protected AdmissionModel $admissionModel;
    protected AdmissionStatusLogModel $statusLogModel;
    protected AdmissionFeeSnapshotModel $feeSnapshotModel;
    protected AdmissionFeeSnapshotItemModel $feeSnapshotItemModel;
    protected AdmissionPaymentModel $paymentModel;
    protected AdmissionPaymentAllocationModel $paymentAllocationModel;
    protected AdmissionInstallmentModel $installmentModel;
    protected AdmissionFollowupModel $followupModel;
    protected AdmissionBatchAssignmentModel $batchAssignmentModel;
    protected AdmissionBatchAssignmentHistoryModel $batchAssignmentHistoryModel;
    protected BatchModel $batchModel;
    protected EnquiryModel $enquiryModel;
    protected EnquiryStatusLogModel $enquiryStatusLogModel;
    protected FeeStructureModel $feeStructureModel;
    protected FeeStructureItemModel $feeStructureItemModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->admissionModel = new AdmissionModel();
        $this->statusLogModel = new AdmissionStatusLogModel();
        $this->feeSnapshotModel = new AdmissionFeeSnapshotModel();
        $this->feeSnapshotItemModel = new AdmissionFeeSnapshotItemModel();
        $this->paymentModel = new AdmissionPaymentModel();
        $this->paymentAllocationModel = new AdmissionPaymentAllocationModel();
        $this->installmentModel = new AdmissionInstallmentModel();
        $this->followupModel = new AdmissionFollowupModel();
        $this->batchAssignmentModel = new AdmissionBatchAssignmentModel();
        $this->batchAssignmentHistoryModel = new AdmissionBatchAssignmentHistoryModel();
        $this->batchModel = new BatchModel();
        $this->enquiryModel = new EnquiryModel();
        $this->enquiryStatusLogModel = new EnquiryStatusLogModel();
        $this->feeStructureModel = new FeeStructureModel();
        $this->feeStructureItemModel = new FeeStructureItemModel();
        $this->db = db_connect();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createAdmission(int $tenantId, array $payload, ?object $sourceEnquiry = null): int
    {
        $this->db->transException(true)->transStart();

        if ($sourceEnquiry && $this->admissionModel->findByEnquiryId($tenantId, (int) $sourceEnquiry->id)) {
            throw new RuntimeException('This enquiry has already been converted into an admission.');
        }

        $feeStructure = $this->feeStructureModel->findForTenant($tenantId, (int) $payload['fee_structure_id']);
        if (! $feeStructure || $feeStructure->status !== 'active') {
            throw new RuntimeException('Choose an active fee structure for the selected course.');
        }

        if ((int) $feeStructure->course_id !== (int) $payload['course_id']) {
            throw new RuntimeException('Fee structure must match the selected course.');
        }

        $feeItems = $this->feeStructureItemModel->getForStructure((int) $feeStructure->id);
        if ($feeItems === []) {
            throw new RuntimeException('The selected fee structure does not have any fee heads yet.');
        }

        $grossAmount = $this->normalizeMoney((float) $feeStructure->total_amount);
        $netAmount = $this->normalizeMoney($grossAmount - (float) $payload['discount_amount']);
        $initialPaymentAmount = min($this->normalizeMoney((float) $payload['initial_payment_amount']), $netAmount);
        $balanceAmount = $this->normalizeMoney($netAmount - $initialPaymentAmount);

        $admissionId = (int) $this->admissionModel->insertWithActor([
            'tenant_id'        => $tenantId,
            'branch_id'        => (int) $payload['branch_id'],
            'enquiry_id'       => $sourceEnquiry ? (int) $sourceEnquiry->id : null,
            'admission_number' => $this->nextAdmissionNumber($tenantId),
            'student_name'     => (string) $payload['student_name'],
            'email'            => $payload['email'] ?: null,
            'mobile'           => (string) $payload['mobile'],
            'whatsapp_number'  => $payload['whatsapp_number'] ?: null,
            'gender'           => $payload['gender'] ?: null,
            'city'             => $payload['city'] ?: null,
            'college_id'       => (int) $payload['college_id'] ?: null,
            'course_id'        => (int) $payload['course_id'] ?: null,
            'assigned_user_id' => (int) $payload['assigned_user_id'],
            'mode_of_class'    => $payload['mode_of_class'] ?: null,
            'admission_date'   => (string) $payload['admission_date'],
            'status'           => 'active',
            'remarks'          => $payload['remarks'] ?: null,
        ]);

        $this->statusLogModel->insertWithActor([
            'tenant_id'   => $tenantId,
            'admission_id'=> $admissionId,
            'old_status'  => null,
            'new_status'  => 'active',
            'reason'      => 'Admission created',
            'remarks'     => $payload['remarks'] ?: null,
            'changed_by'  => session()->get('user_id') ?: null,
        ]);

        $snapshotId = (int) $this->feeSnapshotModel->insertWithActor([
            'tenant_id'       => $tenantId,
            'admission_id'    => $admissionId,
            'fee_structure_id'=> (int) $feeStructure->id,
            'fee_plan_label'  => (string) $feeStructure->name,
            'gross_amount'    => $grossAmount,
            'discount_amount' => $this->normalizeMoney((float) $payload['discount_amount']),
            'net_amount'      => $netAmount,
            'paid_amount'     => $initialPaymentAmount,
            'balance_amount'  => $balanceAmount,
        ]);

        foreach ($feeItems as $item) {
            $this->feeSnapshotItemModel->insertWithActor([
                'tenant_id'        => $tenantId,
                'admission_id'     => $admissionId,
                'snapshot_id'      => $snapshotId,
                'fee_head_name'    => $item->fee_head_name,
                'fee_head_code'    => $item->fee_head_code,
                'amount'           => (float) $item->amount,
                'allow_discount'   => (int) ($item->allow_discount ?? 0),
                'display_order'    => (int) ($item->display_order ?? 0),
            ]);
        }

        $installmentIds = [];
        if ($balanceAmount > 0) {
            $installmentIds = $this->generateInstallments($tenantId, $admissionId, $balanceAmount, $payload);
        }

        if ($initialPaymentAmount > 0) {
            $paymentId = (int) $this->paymentModel->insertWithActor([
                'tenant_id'              => $tenantId,
                'admission_id'           => $admissionId,
                'receipt_number'         => $this->nextReceiptNumber($tenantId),
                'payment_kind'           => 'initial',
                'amount'                 => $initialPaymentAmount,
                'payment_date'           => (string) $payload['payment_date'],
                'payment_mode'           => (string) $payload['payment_mode'],
                'transaction_reference'  => $payload['transaction_reference'] ?: null,
                'remarks'                => $payload['payment_remarks'] ?: null,
                'received_by'            => session()->get('user_id') ?: null,
            ]);

            $this->allocatePayment($tenantId, $admissionId, $paymentId, $initialPaymentAmount, $installmentIds);
        }

        if ($sourceEnquiry) {
            $this->enquiryModel->updateWithActor((int) $sourceEnquiry->id, [
                'lifecycle_status' => 'admitted',
                'admitted_at'      => date('Y-m-d H:i:s'),
            ]);

            $this->enquiryStatusLogModel->insertWithActor([
                'tenant_id'    => $tenantId,
                'enquiry_id'   => (int) $sourceEnquiry->id,
                'from_status'  => $sourceEnquiry->lifecycle_status ?? 'active',
                'to_status'    => 'admitted',
                'reason'       => 'Converted to admission',
                'changed_by'   => session()->get('user_id') ?: null,
            ]);
        }

        $this->db->transComplete();

        return $admissionId;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function collectPayment(int $tenantId, int $admissionId, array $payload): int
    {
        $admission = $this->admissionModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->find($admissionId);

        if (! $admission) {
            throw new RuntimeException('Admission not found.');
        }

        if ($admission->status === 'cancelled') {
            throw new RuntimeException('Cancelled admissions cannot receive new payments.');
        }

        $snapshot = $this->feeSnapshotModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('admission_id', $admissionId)
            ->first();

        if (! $snapshot) {
            throw new RuntimeException('Fee snapshot is missing for this admission.');
        }

        $amount = $this->normalizeMoney((float) ($payload['amount'] ?? 0));
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        $balanceAmount = $this->normalizeMoney((float) ($snapshot->balance_amount ?? 0));
        if ($balanceAmount <= 0) {
            throw new RuntimeException('This admission does not have any outstanding balance.');
        }

        if ($amount > $balanceAmount) {
            throw new RuntimeException('Payment amount cannot be greater than the current balance.');
        }

        $paymentDate = trim((string) ($payload['payment_date'] ?? ''));
        $paymentMode = trim((string) ($payload['payment_mode'] ?? ''));
        if ($paymentDate === '' || $paymentMode === '') {
            throw new RuntimeException('Payment date and payment mode are required.');
        }

        $this->db->transException(true)->transStart();

        $paymentId = (int) $this->paymentModel->insertWithActor([
            'tenant_id' => $tenantId,
            'admission_id' => $admissionId,
            'receipt_number' => $this->nextReceiptNumber($tenantId),
            'payment_kind' => 'installment',
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'payment_mode' => $paymentMode,
            'transaction_reference' => ($payload['transaction_reference'] ?? '') !== '' ? (string) $payload['transaction_reference'] : null,
            'remarks' => ($payload['remarks'] ?? '') !== '' ? (string) $payload['remarks'] : null,
            'received_by' => session()->get('user_id') ?: null,
        ]);

        $this->allocatePayment($tenantId, $admissionId, $paymentId, $amount, []);
        $this->refreshFeeSnapshotTotals($tenantId, $admissionId);

        $this->db->transComplete();

        return $paymentId;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function addFollowup(int $tenantId, int $admissionId, array $payload): int
    {
        $admission = $this->requireAdmission($tenantId, $admissionId);

        $followupId = (int) $this->followupModel->insertWithActor([
            'tenant_id' => $tenantId,
            'admission_id' => $admissionId,
            'followup_status_id' => (int) ($payload['followup_status_id'] ?? 0) ?: null,
            'communication_mode_id' => (int) ($payload['communication_mode_id'] ?? 0) ?: null,
            'remarks' => ($payload['remarks'] ?? '') !== '' ? (string) $payload['remarks'] : null,
            'next_followup_at' => ($payload['next_followup_at'] ?? '') !== '' ? (string) $payload['next_followup_at'] : null,
            'is_system_generated' => 0,
        ]);

        $this->refreshFollowupSnapshot($admission);

        return $followupId;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateFollowup(int $tenantId, int $admissionId, int $followupId, array $payload): void
    {
        $admission = $this->requireAdmission($tenantId, $admissionId);
        $followup = $this->requireFollowup($tenantId, $admissionId, $followupId);

        $this->followupModel->updateWithActor((int) $followup->id, [
            'followup_status_id' => (int) ($payload['followup_status_id'] ?? 0) ?: null,
            'communication_mode_id' => (int) ($payload['communication_mode_id'] ?? 0) ?: null,
            'remarks' => ($payload['remarks'] ?? '') !== '' ? (string) $payload['remarks'] : null,
            'next_followup_at' => ($payload['next_followup_at'] ?? '') !== '' ? (string) $payload['next_followup_at'] : null,
        ]);

        $this->refreshFollowupSnapshot($admission);
    }

    public function deleteFollowup(int $tenantId, int $admissionId, int $followupId): void
    {
        $admission = $this->requireAdmission($tenantId, $admissionId);
        $followup = $this->requireFollowup($tenantId, $admissionId, $followupId);

        $this->followupModel->delete((int) $followup->id);
        $this->refreshFollowupSnapshot($admission);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function assignBatch(int $tenantId, int $admissionId, array $payload): void
    {
        $admission = $this->requireAdmission($tenantId, $admissionId);
        $batchId = (int) ($payload['batch_id'] ?? 0);
        if ($batchId < 1) {
            throw new RuntimeException('Choose batch.');
        }

        $batch = $this->batchModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->find($batchId);
        if (! $batch) {
            throw new RuntimeException('Selected batch is not available.');
        }

        if ((int) ($batch->branch_id ?? 0) !== (int) ($admission->branch_id ?? 0)) {
            throw new RuntimeException('Choose a batch from the same branch as the admission.');
        }

        $currentAssignment = $this->batchAssignmentModel->findActiveForAdmission($admissionId);
        $fromBatchId = (int) ($currentAssignment->batch_id ?? $admission->current_batch_id ?? 0);
        if ($fromBatchId === $batchId) {
            throw new RuntimeException('This admission is already assigned to that batch.');
        }

        $reason = trim((string) ($payload['remarks'] ?? ''));

        $this->db->transException(true)->transStart();

        if ($currentAssignment) {
            $this->batchAssignmentModel->updateWithActor((int) $currentAssignment->id, [
                'status' => 'removed',
            ]);
        }

        $this->batchAssignmentModel->insertWithActor([
            'tenant_id' => $tenantId,
            'admission_id' => $admissionId,
            'batch_id' => $batchId,
            'status' => 'active',
            'assigned_on' => date('Y-m-d H:i:s'),
            'assigned_by' => session()->get('user_id') ?: null,
        ]);

        $this->batchAssignmentHistoryModel->insertWithActor([
            'tenant_id' => $tenantId,
            'admission_id' => $admissionId,
            'from_batch_id' => $fromBatchId > 0 ? $fromBatchId : null,
            'to_batch_id' => $batchId,
            'reason' => $reason !== '' ? $reason : ($fromBatchId > 0 ? 'Batch changed' : 'Batch assigned'),
            'moved_by' => session()->get('user_id') ?: null,
            'moved_at' => date('Y-m-d H:i:s'),
        ]);

        $this->admissionModel->updateWithActor($admissionId, [
            'current_batch_id' => $batchId,
        ]);

        $this->db->transComplete();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function changeStatus(int $tenantId, int $admissionId, string $newStatus, array $payload = []): void
    {
        $admission = $this->requireAdmission($tenantId, $admissionId);
        $oldStatus = (string) $admission->status;
        if ($oldStatus === $newStatus) {
            return;
        }

        $this->admissionModel->updateWithActor($admissionId, [
            'status' => $newStatus,
        ]);

        $this->statusLogModel->insertWithActor([
            'tenant_id' => $tenantId,
            'admission_id' => $admissionId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => ($payload['reason'] ?? '') !== '' ? (string) $payload['reason'] : ucfirst(str_replace('_', ' ', $newStatus)),
            'remarks' => ($payload['remarks'] ?? '') !== '' ? (string) $payload['remarks'] : null,
            'changed_by' => session()->get('user_id') ?: null,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, int>
     */
    protected function generateInstallments(int $tenantId, int $admissionId, float $balanceAmount, array $payload): array
    {
        $count = max(1, (int) ($payload['installment_count'] ?? 1));
        $firstDueDate = new \DateTimeImmutable((string) ($payload['first_due_date'] ?: date('Y-m-d')));
        $gapDays = max(1, (int) ($payload['installment_gap_days'] ?? 30));

        $baseAmount = floor(($balanceAmount / $count) * 100) / 100;
        $remaining = $this->normalizeMoney($balanceAmount - ($baseAmount * $count));
        $installmentIds = [];

        for ($index = 1; $index <= $count; $index++) {
            $extra = $index === $count ? $remaining : 0;
            $dueAmount = $this->normalizeMoney($baseAmount + $extra);
            $dueDate = $firstDueDate->modify('+' . (($index - 1) * $gapDays) . ' days')->format('Y-m-d');

            $installmentIds[] = (int) $this->installmentModel->insertWithActor([
                'tenant_id'           => $tenantId,
                'admission_id'        => $admissionId,
                'installment_number'  => $index,
                'due_date'            => $dueDate,
                'due_amount'          => $dueAmount,
                'paid_amount'         => 0,
                'balance_amount'      => $dueAmount,
                'status'              => 'pending',
                'remarks'             => $count === 1 ? 'Remaining balance installment' : 'Auto-generated installment',
            ]);
        }

        return $installmentIds;
    }

    /**
     * @param array<int, int> $installmentIds
     */
    protected function allocatePayment(int $tenantId, int $admissionId, int $paymentId, float $amount, array $installmentIds): void
    {
        $remaining = $amount;

        if ($installmentIds === []) {
            $this->paymentAllocationModel->insertWithActor([
                'tenant_id'         => $tenantId,
                'admission_id'      => $admissionId,
                'payment_id'        => $paymentId,
                'allocated_amount'  => $amount,
            ]);
            return;
        }

        foreach ($this->installmentModel->getForAdmission($admissionId) as $installment) {
            if ($remaining <= 0) {
                break;
            }

            $allocatable = min($remaining, (float) $installment->balance_amount);
            if ($allocatable <= 0) {
                continue;
            }

            $newPaid = $this->normalizeMoney((float) $installment->paid_amount + $allocatable);
            $newBalance = $this->normalizeMoney((float) $installment->due_amount - $newPaid);

            $this->paymentAllocationModel->insertWithActor([
                'tenant_id'          => $tenantId,
                'admission_id'       => $admissionId,
                'payment_id'         => $paymentId,
                'installment_id'     => (int) $installment->id,
                'allocated_amount'   => $allocatable,
            ]);

            $this->installmentModel->updateWithActor((int) $installment->id, [
                'paid_amount'    => $newPaid,
                'balance_amount' => $newBalance,
                'status'         => $newBalance <= 0 ? 'paid' : 'partial',
            ]);

            $remaining = $this->normalizeMoney($remaining - $allocatable);
        }
    }

    protected function refreshFeeSnapshotTotals(int $tenantId, int $admissionId): void
    {
        $snapshot = $this->feeSnapshotModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('admission_id', $admissionId)
            ->first();

        if (! $snapshot) {
            return;
        }

        $paid = (float) $this->paymentModel->withoutTenantScope()
            ->selectSum('amount')
            ->where('tenant_id', $tenantId)
            ->where('admission_id', $admissionId)
            ->where('is_cancelled', 0)
            ->get()
            ->getRow()
            ->amount ?? 0;

        $net = (float) ($snapshot->net_amount ?? 0);
        $this->feeSnapshotModel->updateWithActor((int) $snapshot->id, [
            'paid_amount' => $this->normalizeMoney($paid),
            'balance_amount' => $this->normalizeMoney(max(0, $net - $paid)),
        ]);
    }

    protected function refreshFollowupSnapshot(object $admission): void
    {
        $lastFollowup = $this->followupModel->withoutTenantScope()
            ->where('tenant_id', (int) $admission->tenant_id)
            ->where('admission_id', (int) $admission->id)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        $this->admissionModel->updateWithActor((int) $admission->id, [
            'last_followup_at' => $lastFollowup?->created_at ?: null,
            'next_followup_at' => $lastFollowup?->next_followup_at ?: null,
        ]);
    }

    protected function requireAdmission(int $tenantId, int $admissionId): object
    {
        $admission = $this->admissionModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->find($admissionId);

        if (! $admission) {
            throw new RuntimeException('Admission not found.');
        }

        return $admission;
    }

    protected function requireFollowup(int $tenantId, int $admissionId, int $followupId): object
    {
        $followup = $this->followupModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('admission_id', $admissionId)
            ->find($followupId);

        if (! $followup) {
            throw new RuntimeException('Follow-up not found.');
        }

        return $followup;
    }

    protected function nextAdmissionNumber(int $tenantId): string
    {
        $prefix = 'ADM-' . date('ymd') . '-';
        $count = $this->admissionModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->like('admission_number', $prefix, 'after')
            ->countAllResults() + 1;

        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function nextReceiptNumber(int $tenantId): string
    {
        $prefix = 'RCP-' . date('ymd') . '-';
        $count = $this->paymentModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->like('receipt_number', $prefix, 'after')
            ->countAllResults() + 1;

        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function normalizeMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
