<?php

namespace App\Services;

use App\Models\AdmissionFeeSnapshotItemModel;
use App\Models\AdmissionFeeSnapshotModel;
use App\Models\AdmissionInstallmentModel;
use App\Models\AdmissionModel;
use App\Models\AdmissionPaymentAllocationModel;
use App\Models\AdmissionPaymentModel;
use App\Models\AdmissionStatusLogModel;
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
