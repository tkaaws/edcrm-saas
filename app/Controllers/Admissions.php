<?php

namespace App\Controllers;

use App\Controllers\Concerns\PaginatesCollections;
use App\Models\AdmissionFeeSnapshotItemModel;
use App\Models\AdmissionFeeSnapshotModel;
use App\Models\AdmissionFollowupModel;
use App\Models\AdmissionInstallmentModel;
use App\Models\AdmissionModel;
use App\Models\AdmissionPaymentModel;
use App\Models\AdmissionStatusLogModel;
use App\Models\BranchModel;
use App\Models\CollegeModel;
use App\Models\UserModel;
use App\Services\AdmissionQueueService;
use App\Services\AdmissionService;
use App\Services\DelegationGuardService;
use App\Services\EnquiryQueueService;
use App\Services\UserAccessScopeService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Exceptions\PageNotFoundException;

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
    protected BranchModel $branchModel;
    protected UserModel $userModel;
    protected CollegeModel $collegeModel;
    protected AdmissionQueueService $queueService;
    protected AdmissionService $admissionService;
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
        $this->branchModel = new BranchModel();
        $this->userModel = new UserModel();
        $this->collegeModel = new CollegeModel();
        $this->queueService = service('admissionQueue');
        $this->admissionService = service('admissionService');
        $this->enquiryQueueService = service('enquiryQueue');
        $this->userAccessScope = service('userAccessScope');
        $this->delegationGuard = service('delegationGuard');
    }

    public function index(): string
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

        return view('admissions/show', $this->buildShellViewData([
            'title' => $admission->student_name,
            'pageTitle' => $admission->student_name,
            'activeNav' => 'admissions',
            'admission' => $admission,
            'payments' => $this->paymentModel->getForAdmission((int) $admission->id),
            'installments' => $this->installmentModel->getForAdmission((int) $admission->id),
            'feeItems' => $this->feeSnapshotItemModel->getForAdmission((int) $admission->id),
            'followups' => $this->followupModel->where('admission_id', (int) $admission->id)->orderBy('created_at', 'DESC')->findAll(),
            'statusHistory' => $this->statusLogModel->where('admission_id', (int) $admission->id)->orderBy('created_at', 'DESC')->findAll(),
        ]));
    }

    protected function buildFormViewData(array $data): array
    {
        $tenantId = (int) session()->get('tenant_id');
        service('collegeService')->ensureDefaultCollegeExists($tenantId);

        return $this->buildShellViewData(array_merge([
            'activeNav' => 'admissions',
            'courses' => service('masterData')->getEffectiveValues('course', $tenantId),
            'colleges' => $this->collegeModel->getActiveOptions($tenantId, '', 500),
            'assignableBranches' => $this->getAssignableBranches(),
            'assignableUsers' => $this->getAssignableUsers($tenantId),
            'assignableUsersByBranch' => $this->getAssignableUsersByBranch($tenantId),
            'modeOfClassOptions' => [
                'classroom' => 'Classroom',
                'online' => 'Online',
                'hybrid' => 'Hybrid',
            ],
            'paymentModeOptions' => [
                'cash' => 'Cash',
                'upi' => 'UPI',
                'card' => 'Card',
                'bank_transfer' => 'Bank Transfer',
                'cheque' => 'Cheque',
            ],
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
            'enquiry_id'              => $sourceEnquiry ? (int) $sourceEnquiry->id : (int) $this->request->getPost('enquiry_id'),
            'student_name'            => trim((string) $this->request->getPost('student_name')),
            'email'                   => strtolower(trim((string) $this->request->getPost('email'))),
            'mobile'                  => trim((string) $this->request->getPost('mobile')),
            'whatsapp_number'         => trim((string) $this->request->getPost('whatsapp_number')),
            'gender'                  => trim((string) $this->request->getPost('gender')),
            'city'                    => trim((string) $this->request->getPost('city')),
            'college_id'              => (int) $this->request->getPost('college_id'),
            'course_id'               => (int) $this->request->getPost('course_id'),
            'branch_id'               => (int) ($this->request->getPost('branch_id') ?: $defaultBranchId),
            'assigned_user_id'        => (int) ($this->request->getPost('assigned_user_id') ?: $defaultAssignedUserId),
            'mode_of_class'           => trim((string) $this->request->getPost('mode_of_class')),
            'admission_date'          => trim((string) $this->request->getPost('admission_date')) ?: date('Y-m-d\TH:i'),
            'remarks'                 => trim((string) $this->request->getPost('remarks')),
            'fee_plan_label'          => trim((string) $this->request->getPost('fee_plan_label')),
            'fee_item_label'          => trim((string) $this->request->getPost('fee_item_label')),
            'gross_amount'            => (float) $this->request->getPost('gross_amount'),
            'discount_amount'         => (float) $this->request->getPost('discount_amount'),
            'initial_payment_amount'  => (float) $this->request->getPost('initial_payment_amount'),
            'payment_date'            => trim((string) $this->request->getPost('payment_date')) ?: date('Y-m-d\TH:i'),
            'payment_mode'            => trim((string) $this->request->getPost('payment_mode')),
            'transaction_reference'   => trim((string) $this->request->getPost('transaction_reference')),
            'payment_remarks'         => trim((string) $this->request->getPost('payment_remarks')),
            'installment_count'       => (int) $this->request->getPost('installment_count'),
            'first_due_date'          => trim((string) $this->request->getPost('first_due_date')),
            'installment_gap_days'    => (int) $this->request->getPost('installment_gap_days'),
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

        $gross = (float) $payload['gross_amount'];
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

        if ($payload['fee_plan_label'] === '') {
            $errors[] = 'Fee plan label is required.';
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
}
