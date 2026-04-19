<?php

namespace App\Controllers;

use App\Controllers\Concerns\PaginatesCollections;
use App\Models\AdmissionBatchAssignmentHistoryModel;
use App\Models\AdmissionBatchAssignmentModel;
use App\Models\AdmissionFeeSnapshotItemModel;
use App\Models\AdmissionFeeSnapshotModel;
use App\Models\AdmissionFollowupModel;
use App\Models\AdmissionInstallmentModel;
use App\Models\AdmissionModel;
use App\Models\AdmissionPaymentModel;
use App\Models\AdmissionStatusLogModel;
use App\Models\BatchModel;
use App\Models\BranchModel;
use App\Models\CollegeModel;
use App\Models\UserModel;
use App\Services\AdmissionQueueService;
use App\Services\AdmissionService;
use App\Services\DelegationGuardService;
use App\Services\EnquiryQueueService;
use App\Services\FeeStructureService;
use App\Services\UserAccessScopeService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Admissions extends BaseController
{
    use PaginatesCollections;

    protected AdmissionModel $admissionModel;
    protected AdmissionFeeSnapshotModel $feeSnapshotModel;
    protected AdmissionFeeSnapshotItemModel $feeSnapshotItemModel;
    protected AdmissionPaymentModel $paymentModel;
    protected AdmissionInstallmentModel $installmentModel;
    protected AdmissionFollowupModel $followupModel;
    protected AdmissionStatusLogModel $statusLogModel;
    protected AdmissionBatchAssignmentModel $batchAssignmentModel;
    protected AdmissionBatchAssignmentHistoryModel $batchAssignmentHistoryModel;
    protected BatchModel $batchModel;
    protected BranchModel $branchModel;
    protected UserModel $userModel;
    protected CollegeModel $collegeModel;
    protected AdmissionQueueService $queueService;
    protected AdmissionService $admissionService;
    protected FeeStructureService $feeStructureService;
    protected EnquiryQueueService $enquiryQueueService;
    protected UserAccessScopeService $userAccessScope;
    protected DelegationGuardService $delegationGuard;

    public function __construct()
    {
        $this->admissionModel = new AdmissionModel();
        $this->feeSnapshotModel = new AdmissionFeeSnapshotModel();
        $this->feeSnapshotItemModel = new AdmissionFeeSnapshotItemModel();
        $this->paymentModel = new AdmissionPaymentModel();
        $this->installmentModel = new AdmissionInstallmentModel();
        $this->followupModel = new AdmissionFollowupModel();
        $this->statusLogModel = new AdmissionStatusLogModel();
        $this->batchAssignmentModel = new AdmissionBatchAssignmentModel();
        $this->batchAssignmentHistoryModel = new AdmissionBatchAssignmentHistoryModel();
        $this->batchModel = new BatchModel();
        $this->branchModel = new BranchModel();
        $this->userModel = new UserModel();
        $this->collegeModel = new CollegeModel();
        $this->queueService = service('admissionQueue');
        $this->admissionService = service('admissionService');
        $this->feeStructureService = service('feeStructure');
        $this->enquiryQueueService = service('enquiryQueue');
        $this->userAccessScope = service('userAccessScope');
        $this->delegationGuard = service('delegationGuard');
    }

    public function index(): string|RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady()) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        $queue = (string) ($this->request->getGet('queue') ?: 'admissions');
        $rows = $this->queueService->getRows($tenantId, $queue, $this->currentBranchContextId());
        $paginated = $this->paginateCollection($rows);

        return view('admissions/index', $this->buildShellViewData([
            'title' => 'Admissions',
            'pageTitle' => 'Admissions',
            'activeNav' => 'admissions',
            'admissionsSubnav' => 'admissions',
            'rows' => $paginated['items'],
            'pagination' => $paginated['pagination'],
            'currentQueue' => $queue,
            'canCreateAdmission' => service('permissions')->has('admissions.create'),
        ]));
    }

    public function create(): string|RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady('/enquiries')) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        $sourceEnquiry = $this->resolveSourceEnquiry($tenantId);

        if ($sourceEnquiry && $this->admissionModel->findByEnquiryId($tenantId, (int) $sourceEnquiry->id)) {
            $existing = $this->admissionModel->findByEnquiryId($tenantId, (int) $sourceEnquiry->id);
            return redirect()->to('/admissions/' . (int) $existing->id)
                ->with('message', 'This enquiry is already converted into an admission.');
        }

        return view('admissions/form', $this->buildFormViewData([
            'title' => 'Create Admission',
            'pageTitle' => 'Create Admission',
            'admissionsSubnav' => 'admissions',
            'formAction' => site_url('admissions'),
            'submitText' => 'Create admission',
            'sourceEnquiry' => $sourceEnquiry,
            'admission' => null,
        ]));
    }

    public function store(): RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady('/enquiries')) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        $sourceEnquiry = $this->resolveSourceEnquiry($tenantId);
        $payload = $this->collectPayload($sourceEnquiry);

        if ($errors = $this->validatePayload($payload, $tenantId)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $admissionId = $this->admissionService->createAdmission($tenantId, $payload, $sourceEnquiry);

        return redirect()->to('/admissions/' . $admissionId)->with('message', 'Admission created successfully.');
    }

    public function show(int $id): string|RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady()) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        $admission = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $admission) {
            return redirect()->to('/admissions')->with('error', 'That admission is no longer available in your current view.');
        }

        $hasBatchTable = db_connect()->tableExists('tenant_batches');
        $activeTab = (string) ($this->request->getGet('tab') ?: 'overview');
        if (! in_array($activeTab, ['overview', 'payments', 'installments', 'batch', 'followups', 'history'], true)) {
            $activeTab = 'overview';
        }

        return view('admissions/show', $this->buildShellViewData([
            'title' => $admission->student_name,
            'pageTitle' => $admission->student_name,
            'activeNav' => 'admissions',
            'admissionsSubnav' => 'admissions',
            'activeTab' => $activeTab,
            'admission' => $admission,
            'payments' => $this->getPaymentHistory($tenantId, (int) $admission->id),
            'installments' => $this->installmentModel->getForAdmission((int) $admission->id),
            'feeItems' => $this->feeSnapshotItemModel->getForAdmission((int) $admission->id),
            'followups' => $this->getFollowupHistory($tenantId, (int) $admission->id),
            'statusHistory' => $this->getStatusHistory($tenantId, (int) $admission->id),
            'currentBatchAssignment' => $hasBatchTable ? $this->getCurrentBatchAssignment($tenantId, (int) $admission->id) : null,
            'batchHistory' => $hasBatchTable ? $this->getBatchHistory($tenantId, (int) $admission->id) : [],
            'batchOptions' => $hasBatchTable ? $this->batchModel->getActiveOptions($tenantId, (int) ($admission->branch_id ?? 0)) : [],
            'hasBatchTable' => $hasBatchTable,
            'paymentModeOptions' => $this->getPaymentModeOptions(),
            'communicationModes' => service('masterData')->getEffectiveValues('mode_of_communication', $tenantId),
            'followupStatuses' => service('masterData')->getEffectiveValues('followup_status', $tenantId),
            'canCollectPayment' => service('permissions')->has('fees.create') && $admission->status !== 'cancelled',
            'canManageAdmission' => service('permissions')->has('admissions.edit'),
            'canManageBatch' => service('permissions')->has('admissions.edit') && $hasBatchTable,
            'canManageFollowups' => service('permissions')->has('admissions.edit') && $admission->status !== 'cancelled',
            'canDeleteFollowups' => service('permissions')->has('admissions.edit'),
            'canViewHistory' => service('permissions')->has('admissions.edit') || service('permissions')->has('admissions.cancel'),
            'canCancelAdmission' => service('permissions')->has('admissions.cancel') && $admission->status !== 'cancelled',
        ]));
    }

    public function collectPayment(int $id): RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady('/admissions')) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId())) {
            return redirect()->to('/admissions')->with('error', 'That admission is no longer available in your current view.');
        }

        try {
            $this->admissionService->collectPayment($tenantId, $id, [
                'amount' => $this->request->getPost('amount'),
                'payment_date' => (string) $this->request->getPost('payment_date'),
                'payment_mode' => (string) $this->request->getPost('payment_mode'),
                'transaction_reference' => (string) $this->request->getPost('transaction_reference'),
                'remarks' => (string) $this->request->getPost('remarks'),
            ]);
        } catch (\RuntimeException $exception) {
            return redirect()->to('/admissions/' . $id . '?tab=payments')->with('error', $exception->getMessage());
        }

        return redirect()->to('/admissions/' . $id . '?tab=payments')->with('message', 'Payment collected successfully.');
    }

    public function addFollowup(int $id): RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady('/admissions')) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId())) {
            return redirect()->to('/admissions')->with('error', 'That admission is no longer available in your current view.');
        }

        try {
            $this->admissionService->addFollowup($tenantId, $id, [
                'followup_status_id' => $this->request->getPost('followup_status_id'),
                'communication_mode_id' => $this->request->getPost('communication_mode_id'),
                'remarks' => (string) $this->request->getPost('remarks'),
                'next_followup_at' => (string) $this->request->getPost('next_followup_at'),
            ]);
        } catch (\RuntimeException $exception) {
            return redirect()->to('/admissions/' . $id . '?tab=followups')->with('error', $exception->getMessage());
        }

        return redirect()->to('/admissions/' . $id . '?tab=followups')->with('message', 'Admission follow-up added successfully.');
    }

    public function updateFollowup(int $id, int $followupId): RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady('/admissions')) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId())) {
            return redirect()->to('/admissions')->with('error', 'That admission is no longer available in your current view.');
        }

        try {
            $this->admissionService->updateFollowup($tenantId, $id, $followupId, [
                'followup_status_id' => $this->request->getPost('followup_status_id'),
                'communication_mode_id' => $this->request->getPost('communication_mode_id'),
                'remarks' => (string) $this->request->getPost('remarks'),
                'next_followup_at' => (string) $this->request->getPost('next_followup_at'),
            ]);
        } catch (\RuntimeException $exception) {
            return redirect()->to('/admissions/' . $id . '?tab=followups')->with('error', $exception->getMessage());
        }

        return redirect()->to('/admissions/' . $id . '?tab=followups')->with('message', 'Admission follow-up updated successfully.');
    }

    public function deleteFollowup(int $id, int $followupId): RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady('/admissions')) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId())) {
            return redirect()->to('/admissions')->with('error', 'That admission is no longer available in your current view.');
        }

        try {
            $this->admissionService->deleteFollowup($tenantId, $id, $followupId);
        } catch (\RuntimeException $exception) {
            return redirect()->to('/admissions/' . $id . '?tab=followups')->with('error', $exception->getMessage());
        }

        return redirect()->to('/admissions/' . $id . '?tab=followups')->with('message', 'Admission follow-up deleted successfully.');
    }

    public function assignBatch(int $id): RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady('/admissions')) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId())) {
            return redirect()->to('/admissions')->with('error', 'That admission is no longer available in your current view.');
        }

        try {
            $this->admissionService->assignBatch($tenantId, $id, [
                'batch_id' => $this->request->getPost('batch_id'),
                'remarks' => (string) $this->request->getPost('remarks'),
            ]);
        } catch (\RuntimeException $exception) {
            return redirect()->to('/admissions/' . $id . '?tab=batch')->with('error', $exception->getMessage());
        }

        return redirect()->to('/admissions/' . $id . '?tab=batch')->with('message', 'Batch updated successfully.');
    }

    public function hold(int $id): RedirectResponse
    {
        return $this->changeAdmissionStatus($id, 'on_hold', 'Admission moved to hold.');
    }

    public function cancel(int $id): RedirectResponse
    {
        return $this->changeAdmissionStatus($id, 'cancelled', 'Admission cancelled successfully.');
    }

    protected function buildFormViewData(array $data): array
    {
        $tenantId = (int) session()->get('tenant_id');
        service('collegeService')->ensureDefaultCollegeExists($tenantId);

        return $this->buildShellViewData(array_merge([
            'activeNav' => 'admissions',
            'admissionsSubnav' => 'admissions',
            'courses' => service('masterData')->getEffectiveValues('course', $tenantId),
            'colleges' => $this->collegeModel->getActiveOptions($tenantId, '', 500),
            'feeStructureOptionsUrl' => site_url('admissions/fee-structures/options'),
            'assignableBranches' => $this->getAssignableBranches(),
            'assignableUsers' => $this->getAssignableUsers($tenantId),
            'assignableUsersByBranch' => $this->getAssignableUsersByBranch($tenantId),
            'modeOfClassOptions' => [
                'classroom' => 'Classroom',
                'online' => 'Online',
                'hybrid' => 'Hybrid',
            ],
            'paymentModeOptions' => $this->getPaymentModeOptions(),
        ], $data));
    }

    protected function resolveSourceEnquiry(int $tenantId): ?object
    {
        $enquiryId = (int) ($this->request->getGet('enquiry_id') ?: $this->request->getPost('enquiry_id'));
        if ($enquiryId < 1) {
            return null;
        }

        $enquiry = $this->enquiryQueueService->findVisibleById($tenantId, $enquiryId, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $enquiry;
    }

    /**
     * @param object|null $sourceEnquiry
     * @return array<string, mixed>
     */
    protected function collectPayload(?object $sourceEnquiry = null): array
    {
        $defaultBranchId = $sourceEnquiry ? (int) ($sourceEnquiry->branch_id ?? 0) : $this->resolveDefaultCreateBranchId();
        $defaultAssignedUserId = $sourceEnquiry ? (int) ($sourceEnquiry->owner_user_id ?? 0) : $this->currentActorUserId();

        return [
            'enquiry_id' => $sourceEnquiry ? (int) $sourceEnquiry->id : (int) $this->request->getPost('enquiry_id'),
            'student_name' => trim((string) $this->request->getPost('student_name')),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'mobile' => trim((string) $this->request->getPost('mobile')),
            'whatsapp_number' => trim((string) $this->request->getPost('whatsapp_number')),
            'gender' => trim((string) $this->request->getPost('gender')),
            'city' => trim((string) $this->request->getPost('city')),
            'college_id' => (int) $this->request->getPost('college_id'),
            'course_id' => (int) $this->request->getPost('course_id'),
            'branch_id' => (int) ($this->request->getPost('branch_id') ?: $defaultBranchId),
            'assigned_user_id' => (int) ($this->request->getPost('assigned_user_id') ?: $defaultAssignedUserId),
            'mode_of_class' => trim((string) $this->request->getPost('mode_of_class')),
            'admission_date' => trim((string) $this->request->getPost('admission_date')) ?: date('Y-m-d\TH:i'),
            'remarks' => trim((string) $this->request->getPost('remarks')),
            'fee_structure_id' => (int) $this->request->getPost('fee_structure_id'),
            'discount_amount' => (float) $this->request->getPost('discount_amount'),
            'initial_payment_amount' => (float) $this->request->getPost('initial_payment_amount'),
            'payment_date' => trim((string) $this->request->getPost('payment_date')) ?: date('Y-m-d\TH:i'),
            'payment_mode' => trim((string) $this->request->getPost('payment_mode')),
            'transaction_reference' => trim((string) $this->request->getPost('transaction_reference')),
            'payment_remarks' => trim((string) $this->request->getPost('payment_remarks')),
            'installment_count' => (int) $this->request->getPost('installment_count'),
            'first_due_date' => trim((string) $this->request->getPost('first_due_date')),
            'installment_gap_days' => (int) $this->request->getPost('installment_gap_days'),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, string>
     */
    protected function validatePayload(array $payload, int $tenantId): array
    {
        $errors = [];

        if ($payload['student_name'] === '') {
            $errors[] = 'Student name is required.';
        }

        if ($payload['mobile'] === '') {
            $errors[] = 'Mobile number is required.';
        }

        if ((int) $payload['college_id'] < 1) {
            $errors[] = 'Choose college.';
        }

        if ((int) $payload['course_id'] < 1) {
            $errors[] = 'Choose course.';
        }

        if ((int) $payload['fee_structure_id'] < 1) {
            $errors[] = 'Choose a fee structure.';
        }

        if ((int) $payload['branch_id'] < 1) {
            $errors[] = 'Choose branch.';
        }

        if ((int) $payload['assigned_user_id'] < 1) {
            $errors[] = 'Choose the team member responsible for this admission.';
        }

        if ($payload['email'] !== '' && ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email must be valid.';
        }

        if (! $this->userAccessScope->canAssignBranches([(int) $payload['branch_id']])) {
            $errors[] = 'Selected branch is outside your allowed scope.';
        }

        if (! $this->userAccessScope->canAssignManager((int) $payload['assigned_user_id'])) {
            $errors[] = 'Selected team member is outside your allowed scope.';
        }

        if (! $this->isUserAssignableToBranch($tenantId, (int) $payload['assigned_user_id'], (int) $payload['branch_id'])) {
            $errors[] = 'Choose an employee from the selected branch.';
        }

        $feeStructure = $this->feeStructureService->findForTenant($tenantId, (int) $payload['fee_structure_id']);
        if (! $feeStructure || $feeStructure->status !== 'active') {
            $errors[] = 'Choose an active fee structure.';
            $gross = 0;
        } elseif ((int) $feeStructure->course_id !== (int) $payload['course_id']) {
            $errors[] = 'Fee structure must match the selected course.';
            $gross = 0;
        } else {
            $gross = (float) $feeStructure->total_amount;
        }

        $discount = (float) $payload['discount_amount'];
        $initialPayment = (float) $payload['initial_payment_amount'];
        $net = $gross - $discount;

        if ($gross <= 0) {
            $errors[] = 'Gross fees must be greater than zero.';
        }

        if ($discount < 0 || $discount > $gross) {
            $errors[] = 'Discount must stay between zero and gross fees.';
        }

        if ($initialPayment < 0 || $initialPayment > $net) {
            $errors[] = 'Initial payment cannot exceed the net fees.';
        }

        $remaining = $net - $initialPayment;
        if ($remaining > 0 && (int) $payload['installment_count'] < 1) {
            $errors[] = 'Add at least one installment for the remaining balance.';
        }

        if ($remaining > 0 && $payload['first_due_date'] === '') {
            $errors[] = 'Choose the first installment due date.';
        }

        if ($initialPayment > 0 && $payload['payment_mode'] === '') {
            $errors[] = 'Choose a payment mode for the first payment.';
        }

        return $errors;
    }

    protected function getAssignableBranches(): array
    {
        return $this->userAccessScope->filterAssignableBranches($this->branchModel->getActiveBranches());
    }

    protected function getAssignableUsers(int $tenantId): array
    {
        $users = $this->userAccessScope->getAllowedManagerOptions($tenantId);

        return array_values(array_filter($users, function (object $user) use ($tenantId): bool {
            return $this->delegationGuard->canAssignRoleForTenant($tenantId, (int) $user->role_id);
        }));
    }

    protected function getAssignableUsersByBranch(int $tenantId): array
    {
        $map = [];

        foreach ($this->getAssignableUsers($tenantId) as $user) {
            $map[(int) $user->id] = array_values(array_map(
                static fn(array $branch): int => (int) $branch['id'],
                $this->userModel->getBranches((int) $user->id)
            ));
        }

        return $map;
    }

    protected function isUserAssignableToBranch(int $tenantId, int $userId, int $branchId): bool
    {
        foreach ($this->getAssignableUsersByBranch($tenantId)[$userId] ?? [] as $allowedBranchId) {
            if ((int) $allowedBranchId === $branchId) {
                return true;
            }
        }

        return false;
    }

    protected function currentBranchContextId(): ?int
    {
        $branchId = session()->get('branch_id');
        return $branchId ? (int) $branchId : null;
    }

    protected function currentActorUserId(): ?int
    {
        $userId = session()->get('user_id');
        return $userId ? (int) $userId : null;
    }

    protected function resolveDefaultCreateBranchId(): ?int
    {
        $branchId = $this->currentBranchContextId();
        if ($branchId) {
            return $branchId;
        }

        $branchIds = $this->userAccessScope->getAssignedBranchIdsForActor();
        return $branchIds[0] ?? null;
    }

    protected function ensureAdmissionsSchemaReady(string $fallbackPath = '/admissions'): ?RedirectResponse
    {
        $requiredTables = [
            'admissions',
            'admission_fee_snapshots',
            'admission_installments',
            'admission_payments',
            'fee_structures',
            'fee_structure_items',
        ];

        foreach ($requiredTables as $table) {
            if (! db_connect()->tableExists($table)) {
                return redirect()->to($fallbackPath)->with(
                    'error',
                    'Admissions setup is not finished on this server yet. Please run the latest database migrations first.'
                );
            }
        }

        return null;
    }

    protected function getPaymentModeOptions(): array
    {
        return [
            'cash' => 'Cash',
            'upi' => 'UPI',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
        ];
    }

    protected function getPaymentHistory(int $tenantId, int $admissionId): array
    {
        return db_connect()->table('admission_payments p')
            ->select("p.*, TRIM(CONCAT(COALESCE(received_user.first_name, ''), ' ', COALESCE(received_user.last_name, ''))) AS received_by_name")
            ->join('users received_user', 'received_user.id = p.received_by', 'left')
            ->where('p.tenant_id', $tenantId)
            ->where('p.admission_id', $admissionId)
            ->orderBy('p.payment_date', 'DESC')
            ->orderBy('p.id', 'DESC')
            ->get()
            ->getResult();
    }

    protected function getFollowupHistory(int $tenantId, int $admissionId): array
    {
        return db_connect()->table('admission_followups f')
            ->select("f.*, status.label AS followup_status_label, mode.label AS communication_mode_label, TRIM(CONCAT(COALESCE(created_user.first_name, ''), ' ', COALESCE(created_user.last_name, ''))) AS created_by_name")
            ->join('master_data_values status', 'status.id = f.followup_status_id', 'left')
            ->join('master_data_values mode', 'mode.id = f.communication_mode_id', 'left')
            ->join('users created_user', 'created_user.id = f.created_by', 'left')
            ->where('f.tenant_id', $tenantId)
            ->where('f.admission_id', $admissionId)
            ->orderBy('f.created_at', 'DESC')
            ->orderBy('f.id', 'DESC')
            ->get()
            ->getResult();
    }

    protected function getStatusHistory(int $tenantId, int $admissionId): array
    {
        return db_connect()->table('admission_status_logs l')
            ->select("l.*, TRIM(CONCAT(COALESCE(changed_user.first_name, ''), ' ', COALESCE(changed_user.last_name, ''))) AS changed_by_name")
            ->join('users changed_user', 'changed_user.id = l.changed_by', 'left')
            ->where('l.tenant_id', $tenantId)
            ->where('l.admission_id', $admissionId)
            ->orderBy('l.created_at', 'DESC')
            ->orderBy('l.id', 'DESC')
            ->get()
            ->getResult();
    }

    protected function getCurrentBatchAssignment(int $tenantId, int $admissionId): ?object
    {
        return db_connect()->table('admission_batch_assignments a')
            ->select('a.*, batch.name AS batch_name, batch.code AS batch_code, batch.starts_on, batch.ends_on')
            ->join('tenant_batches batch', 'batch.id = a.batch_id', 'left')
            ->where('a.tenant_id', $tenantId)
            ->where('a.admission_id', $admissionId)
            ->where('a.status', 'active')
            ->orderBy('a.assigned_on', 'DESC')
            ->get()
            ->getRow();
    }

    protected function getBatchHistory(int $tenantId, int $admissionId): array
    {
        return db_connect()->table('admission_batch_assignment_history h')
            ->select("h.*, from_batch.name AS from_batch_name, to_batch.name AS to_batch_name, TRIM(CONCAT(COALESCE(moved_user.first_name, ''), ' ', COALESCE(moved_user.last_name, ''))) AS moved_by_name")
            ->join('tenant_batches from_batch', 'from_batch.id = h.from_batch_id', 'left')
            ->join('tenant_batches to_batch', 'to_batch.id = h.to_batch_id', 'left')
            ->join('users moved_user', 'moved_user.id = h.moved_by', 'left')
            ->where('h.tenant_id', $tenantId)
            ->where('h.admission_id', $admissionId)
            ->orderBy('h.moved_at', 'DESC')
            ->orderBy('h.id', 'DESC')
            ->get()
            ->getResult();
    }

    protected function changeAdmissionStatus(int $id, string $status, string $successMessage): RedirectResponse
    {
        if ($response = $this->ensureAdmissionsSchemaReady('/admissions')) {
            return $response;
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId())) {
            return redirect()->to('/admissions')->with('error', 'That admission is no longer available in your current view.');
        }

        $reason = trim((string) $this->request->getPost('reason'));
        $remarks = trim((string) $this->request->getPost('remarks'));
        if ($reason === '') {
            return redirect()->to('/admissions/' . $id . '?tab=history')->with('error', 'Reason is required.');
        }

        try {
            $this->admissionService->changeStatus($tenantId, $id, $status, [
                'reason' => $reason,
                'remarks' => $remarks,
            ]);
        } catch (\RuntimeException $exception) {
            return redirect()->to('/admissions/' . $id . '?tab=history')->with('error', $exception->getMessage());
        }

        return redirect()->to('/admissions/' . $id . '?tab=history')->with('message', $successMessage);
    }
}
