<?php

namespace App\Controllers;

use App\Models\BranchModel;
use App\Models\CollegeModel;
use App\Models\EnquiryAssignmentHistoryModel;
use App\Models\EnquiryFollowupModel;
use App\Models\EnquiryModel;
use App\Models\EnquiryStatusLogModel;
use App\Models\UserModel;
use App\Services\DelegationGuardService;
use App\Services\EnquiryQueueService;
use App\Services\UserAccessScopeService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Enquiries extends BaseController
{
    protected EnquiryModel $enquiryModel;
    protected BranchModel $branchModel;
    protected UserModel $userModel;
    protected CollegeModel $collegeModel;
    protected EnquiryAssignmentHistoryModel $assignmentHistoryModel;
    protected EnquiryStatusLogModel $statusLogModel;
    protected EnquiryFollowupModel $followupModel;
    protected EnquiryQueueService $queueService;
    protected UserAccessScopeService $userAccessScope;
    protected DelegationGuardService $delegationGuard;

    public function __construct()
    {
        $this->enquiryModel = new EnquiryModel();
        $this->branchModel = new BranchModel();
        $this->userModel = new UserModel();
        $this->collegeModel = new CollegeModel();
        $this->assignmentHistoryModel = new EnquiryAssignmentHistoryModel();
        $this->statusLogModel = new EnquiryStatusLogModel();
        $this->followupModel = new EnquiryFollowupModel();
        $this->queueService = service('enquiryQueue');
        $this->userAccessScope = service('userAccessScope');
        $this->delegationGuard = service('delegationGuard');
    }

    public function index(): string
    {
        return $this->renderQueuePage((string) ($this->request->getGet('tab') ?: 'enquiries'));
    }

    public function expired(): string
    {
        return $this->renderQueuePage('expired');
    }

    public function closed(): string
    {
        return $this->renderQueuePage('closed');
    }

    public function bulkAssign(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $filters = $this->collectBulkAssignFilters();
        $rows = $this->filterBulkAssignRows(
            $this->queueService->getVisibleRows($tenantId, $this->currentBranchContextId()),
            $filters
        );

        foreach ($rows as $row) {
            $this->decorateEnquiryRow($row);
            $row->mobile_display = $this->formatMobile((string) $row->mobile);
        }

        return view('enquiries/bulk_assign', $this->buildShellViewData([
            'title' => 'Bulk Assign Enquiries',
            'pageTitle' => 'Bulk Assign Enquiries',
            'activeNav' => 'enquiries',
            'rows' => $rows,
            'filters' => $filters,
            'sources' => service('masterData')->getEffectiveValues('enquiry_source', $tenantId),
            'courses' => service('masterData')->getEffectiveValues('course', $tenantId),
            'assignableBranches' => $this->getAssignableBranches(),
            'assignableUsers' => $this->getAssignableUsers($tenantId),
            'assignableUsersByBranch' => $this->getAssignableUsersByBranch($tenantId),
        ]));
    }

    public function create(): string
    {
        return view('enquiries/form', $this->buildFormViewData([
            'title' => 'Add Enquiry',
            'pageTitle' => 'Add Enquiry',
            'formAction' => site_url('enquiries'),
            'submitText' => 'Create enquiry',
            'enquiry' => null,
            'showAssignmentSection' => false,
        ]));
    }

    public function store()
    {
        $tenantId = (int) session()->get('tenant_id');
        $data = $this->collectPayload();

        if ($errors = $this->validatePayload($data, $tenantId)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $branchId = $data['branch_id'] ?: ((int) session()->get('branch_id') ?: null);
        $ownerUserId = $data['owner_user_id'] ?: ((int) session()->get('user_id') ?: null);
        $hasInitialFollowup = $this->hasInitialFollowup($data);
        $initialFollowupAt = $hasInitialFollowup ? date('Y-m-d H:i:s') : null;

        $enquiryId = $this->enquiryModel->insertWithActor([
            'tenant_id'         => $tenantId,
            'branch_id'         => $branchId,
            'owner_user_id'     => $ownerUserId,
            'assigned_on'       => $ownerUserId ? date('Y-m-d H:i:s') : null,
            'student_name'      => $data['student_name'],
            'email'             => $data['email'] ?: null,
            'mobile'            => $data['mobile'],
            'whatsapp_number'   => $data['whatsapp_number'] ?: null,
            'source_id'         => $data['source_id'] ?: null,
            'college_id'        => $data['college_id'] ?: null,
            'qualification_id'  => $data['qualification_id'] ?: null,
            'primary_course_id' => $data['primary_course_id'] ?: null,
            'city'              => $data['city'] ?: null,
            'notes'             => $data['notes'] ?: null,
            'lifecycle_status'  => $hasInitialFollowup ? 'active' : 'new',
            'last_followup_at'  => $initialFollowupAt,
            'next_followup_at'  => $data['next_followup_at'] ?: null,
        ]);

        $this->statusLogModel->insertWithActor([
            'tenant_id'    => $tenantId,
            'enquiry_id'   => (int) $enquiryId,
            'from_status'  => null,
            'to_status'    => $hasInitialFollowup ? 'active' : 'new',
            'reason'       => $hasInitialFollowup ? 'Enquiry created with initial follow-up' : 'Enquiry created',
            'changed_by'   => session()->get('user_id') ?: null,
        ]);

        if ($ownerUserId || $branchId) {
            $this->assignmentHistoryModel->insertWithActor([
                'tenant_id'       => $tenantId,
                'enquiry_id'      => (int) $enquiryId,
                'from_branch_id'  => null,
                'to_branch_id'    => $branchId,
                'from_user_id'    => null,
                'to_user_id'      => $ownerUserId,
                'assigned_by'     => session()->get('user_id') ?: null,
                'assignment_type' => 'manual',
                'reason'          => 'Initial enquiry ownership',
                'assigned_on'     => date('Y-m-d H:i:s'),
            ]);
        }

        if ($hasInitialFollowup) {
            $this->followupModel->insertWithActor([
                'tenant_id'             => $tenantId,
                'enquiry_id'            => (int) $enquiryId,
                'branch_id'             => $branchId,
                'owner_user_id'         => $ownerUserId,
                'communication_type_id' => null,
                'followup_outcome_id'   => null,
                'remarks'               => $data['notes'] !== '' ? $data['notes'] : 'Initial enquiry follow-up captured.',
                'next_followup_at'      => $data['next_followup_at'] ?: null,
                'is_system_generated'   => 0,
            ]);
        }

        return redirect()->to('/enquiries/' . (int) $enquiryId)->with('message', 'Enquiry created successfully.');
    }

    public function show(int $id): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->decorateEnquiryRow($enquiry);
        $enquiry->mobile_display = $this->formatMobile((string) $enquiry->mobile);
        $enquiry->whatsapp_display = $enquiry->whatsapp_number ? $this->formatMobile((string) $enquiry->whatsapp_number) : '-';
        $canViewFollowups = service('permissions')->has('followups.view');
        $canEditFollowups = service('permissions')->has('followups.edit');
        $canDeleteFollowups = service('permissions')->has('followups.delete');

        return view('enquiries/show', $this->buildShellViewData([
            'title' => $enquiry->student_name,
            'pageTitle' => $enquiry->student_name,
            'activeNav' => 'enquiries',
            'enquiry' => $enquiry,
            'canEditEnquiry' => service('permissions')->has('enquiries.edit') && $enquiry->lifecycle_status !== 'admitted',
            'canEditContactInfo' => service('permissions')->has('enquiries.update_contact_info') && $enquiry->lifecycle_status !== 'admitted',
            'canEditCollegeInfo' => service('permissions')->has('enquiries.update_college_info') && $enquiry->lifecycle_status !== 'admitted',
            'canCloseEnquiry' => service('permissions')->has('enquiries.close') && in_array($enquiry->lifecycle_status, ['new', 'active'], true),
            'canReopenEnquiry' => service('permissions')->has('enquiries.reopen') && $enquiry->lifecycle_status === 'closed',
            'canAssignFromDetail' => $this->canAssignFromDetail($enquiry),
            'assignableBranches' => $this->getAssignableBranches(),
            'assignableUsers' => $this->getAssignableUsers($tenantId),
            'assignableUsersByBranch' => $this->getAssignableUsersByBranch($tenantId),
            'closeReasons' => service('masterData')->getEffectiveValues('enquiry_closure_reason', $tenantId),
            'followupStatuses' => service('masterData')->getEffectiveValues('followup_status', $tenantId),
            'communicationModes' => service('masterData')->getEffectiveValues('mode_of_communication', $tenantId),
            'sources' => service('masterData')->getEffectiveValues('enquiry_source', $tenantId),
            'qualifications' => service('masterData')->getEffectiveValues('lead_qualification', $tenantId),
            'courses' => service('masterData')->getEffectiveValues('course', $tenantId),
            'colleges' => $this->collegeModel->getActiveOptions($tenantId, '', 500),
            'followupHistory' => $canViewFollowups ? $this->getFollowupHistory((int) $enquiry->id) : [],
            'canViewFollowups' => $canViewFollowups,
            'canAddFollowup' => service('permissions')->has('followups.create') && in_array($enquiry->lifecycle_status, ['new', 'active'], true),
            'canEditFollowups' => $canEditFollowups,
            'canDeleteFollowups' => $canDeleteFollowups,
            'canViewHistory' => service('permissions')->has('enquiries.activity_view'),
            'historyEvents' => service('permissions')->has('enquiries.activity_view') ? $this->getAuditHistory((int) $enquiry->id) : [],
        ]));
    }

    public function edit(int $id): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('enquiries/form', $this->buildFormViewData([
            'title' => 'Edit Enquiry',
            'pageTitle' => 'Edit Enquiry',
            'formAction' => site_url('enquiries/' . $id),
            'submitText' => 'Save changes',
            'enquiry' => $enquiry,
            'showAssignmentSection' => $this->canReassignInEdit($enquiry),
            'assignableBranches' => $this->getAssignableBranches(),
            'assignableUsers' => $this->getAssignableUsers($tenantId),
            'assignableUsersByBranch' => $this->getAssignableUsersByBranch($tenantId),
        ]));
    }

    public function update(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        $data = $this->collectPayload();
        if ($errors = $this->validatePayload($data, $tenantId, true)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $update = [
            'student_name'      => $data['student_name'],
            'email'             => $data['email'] ?: null,
            'mobile'            => $data['mobile'],
            'whatsapp_number'   => $data['whatsapp_number'] ?: null,
            'source_id'         => $data['source_id'] ?: null,
            'college_id'        => $data['college_id'] ?: null,
            'qualification_id'  => $data['qualification_id'] ?: null,
            'primary_course_id' => $data['primary_course_id'] ?: null,
            'city'              => $data['city'] ?: null,
            'notes'             => $data['notes'] ?: null,
            'next_followup_at'  => $data['next_followup_at'] ?: null,
            'lifecycle_status'  => in_array($enquiry->lifecycle_status, ['new', 'active'], true)
                ? ($data['next_followup_at'] ? 'active' : 'new')
                : $enquiry->lifecycle_status,
        ];

        if ($this->canReassignInEdit($enquiry)) {
            $newBranchId = $data['branch_id'] ?: null;
            $newOwnerId = $data['owner_user_id'] ?: null;

            if ($newBranchId !== (int) ($enquiry->branch_id ?? 0) || $newOwnerId !== (int) ($enquiry->owner_user_id ?? 0)) {
                $update['branch_id'] = $newBranchId;
                $update['owner_user_id'] = $newOwnerId;
                $update['assigned_on'] = date('Y-m-d H:i:s');

                $this->assignmentHistoryModel->insertWithActor([
                    'tenant_id'       => $tenantId,
                    'enquiry_id'      => (int) $enquiry->id,
                    'from_branch_id'  => $enquiry->branch_id ?: null,
                    'to_branch_id'    => $newBranchId,
                    'from_user_id'    => $enquiry->owner_user_id ?: null,
                    'to_user_id'      => $newOwnerId,
                    'assigned_by'     => session()->get('user_id') ?: null,
                    'assignment_type' => 'manual',
                    'reason'          => 'Updated from enquiry edit',
                    'assigned_on'     => date('Y-m-d H:i:s'),
                ]);

                $this->createSystemAssignmentFollowup(
                    $tenantId,
                    (int) $enquiry->id,
                    $newBranchId ?: (int) ($enquiry->branch_id ?? 0),
                    $newOwnerId ?: (int) ($enquiry->owner_user_id ?? 0),
                    'Enquiry reassigned from edit enquiry.'
                );
            }
        }

        $this->enquiryModel->updateWithActor((int) $enquiry->id, $update);

        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'Enquiry updated successfully.');
    }

    public function updateContactInfo(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('permissions')->has('enquiries.update_contact_info') || $enquiry->lifecycle_status === 'admitted') {
            return redirect()->back()->with('error', 'You do not have access to update contact details for this enquiry.');
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $mobile = trim((string) $this->request->getPost('mobile'));
        $whatsappNumber = trim((string) $this->request->getPost('whatsapp_number'));

        if ($mobile === '') {
            return redirect()->back()->withInput()->with('error', 'Mobile number is required.');
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Email must be a valid email address.');
        }

        $this->enquiryModel->updateWithActor((int) $enquiry->id, [
            'email' => $email !== '' ? $email : null,
            'mobile' => $mobile,
            'whatsapp_number' => $whatsappNumber !== '' ? $whatsappNumber : null,
        ]);

        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'Contact details updated successfully.');
    }

    public function updateCollegeInfo(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('permissions')->has('enquiries.update_college_info') || $enquiry->lifecycle_status === 'admitted') {
            return redirect()->back()->with('error', 'You do not have access to update college details for this enquiry.');
        }

        $collegeId = (int) $this->request->getPost('college_id');
        $city = trim((string) $this->request->getPost('city'));

        if ($collegeId < 1) {
            return redirect()->back()->withInput()->with('error', 'Choose a college before saving.');
        }

        $this->enquiryModel->updateWithActor((int) $enquiry->id, [
            'college_id' => $collegeId,
            'city' => $city !== '' ? $city : null,
        ]);

        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'College details updated successfully.');
    }

    public function close(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('permissions')->has('enquiries.close')) {
            return redirect()->back()->with('error', 'You do not have access to close enquiries.');
        }

        $reasonId = (int) $this->request->getPost('closed_reason_id');
        $remarks = trim((string) $this->request->getPost('closed_remarks'));

        if ($reasonId < 1) {
            return redirect()->back()->withInput()->with('error', 'Choose a close reason before closing the enquiry.');
        }

        $this->enquiryModel->updateWithActor((int) $enquiry->id, [
            'lifecycle_status' => 'closed',
            'closed_reason_id' => $reasonId,
            'closed_remarks'   => $remarks ?: null,
            'closed_at'        => date('Y-m-d H:i:s'),
            'closed_by'        => session()->get('user_id') ?: null,
        ]);

        $this->statusLogModel->insertWithActor([
            'tenant_id'   => $tenantId,
            'enquiry_id'  => (int) $enquiry->id,
            'from_status' => $enquiry->lifecycle_status,
            'to_status'   => 'closed',
            'reason'      => $remarks !== '' ? $remarks : 'Closed from enquiry detail',
            'changed_by'  => session()->get('user_id') ?: null,
        ]);

        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'Enquiry closed successfully.');
    }

    public function reopen(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('permissions')->has('enquiries.reopen')) {
            return redirect()->back()->with('error', 'You do not have access to reopen enquiries.');
        }

        $this->enquiryModel->updateWithActor((int) $enquiry->id, [
            'lifecycle_status' => 'active',
            'closed_reason_id' => null,
            'closed_remarks'   => null,
            'closed_at'        => null,
            'closed_by'        => null,
        ]);

        $this->statusLogModel->insertWithActor([
            'tenant_id'   => $tenantId,
            'enquiry_id'  => (int) $enquiry->id,
            'from_status' => $enquiry->lifecycle_status,
            'to_status'   => 'active',
            'reason'      => 'Enquiry reopened',
            'changed_by'  => session()->get('user_id') ?: null,
        ]);

        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'Enquiry reopened successfully.');
    }

    public function assign(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! $this->canAssignFromDetail($enquiry)) {
            return redirect()->back()->with('error', 'You do not have access to assign this enquiry from here.');
        }

        $branchId = (int) $this->request->getPost('branch_id');
        $ownerUserId = (int) $this->request->getPost('owner_user_id');
        $comment = trim((string) $this->request->getPost('assignment_comment'));

        if ($branchId < 1 || $ownerUserId < 1) {
            return redirect()->back()->withInput()->with('error', 'Choose both branch and assigned to before saving.');
        }

        if (! $this->isUserAssignableToBranch($tenantId, $ownerUserId, $branchId)) {
            return redirect()->back()->withInput()->with('error', 'Choose an employee from the selected branch.');
        }

        $this->enquiryModel->updateWithActor((int) $enquiry->id, [
            'branch_id'     => $branchId,
            'owner_user_id' => $ownerUserId,
            'assigned_on'   => date('Y-m-d H:i:s'),
        ]);

        $this->assignmentHistoryModel->insertWithActor([
            'tenant_id'       => $tenantId,
            'enquiry_id'      => (int) $enquiry->id,
            'from_branch_id'  => $enquiry->branch_id ?: null,
            'to_branch_id'    => $branchId,
            'from_user_id'    => $enquiry->owner_user_id ?: null,
            'to_user_id'      => $ownerUserId,
            'assigned_by'     => session()->get('user_id') ?: null,
            'assignment_type' => 'manual',
            'reason'          => 'Reassigned from enquiry detail',
            'assigned_on'     => date('Y-m-d H:i:s'),
        ]);

        $this->createSystemAssignmentFollowup(
            $tenantId,
            (int) $enquiry->id,
            $branchId,
            $ownerUserId,
            $comment !== '' ? $comment : 'Enquiry reassigned from enquiry detail.'
        );

        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'Enquiry assignment updated.');
    }

    public function addFollowup(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('permissions')->has('followups.create')) {
            return redirect()->back()->with('error', 'You do not have access to add follow-ups.');
        }

        if (! in_array($enquiry->lifecycle_status, ['new', 'active'], true)) {
            return redirect()->back()->with('error', 'Follow-ups can only be added to active enquiries.');
        }

        $communicationTypeId = (int) $this->request->getPost('communication_type_id');
        $followupOutcomeId = (int) $this->request->getPost('followup_outcome_id');
        $remarks = trim((string) $this->request->getPost('remarks'));
        $nextFollowupAt = trim((string) $this->request->getPost('next_followup_at'));

        if ($communicationTypeId < 1) {
            return redirect()->back()->withInput()->with('error', 'Choose the communication mode for this follow-up.');
        }

        if ($followupOutcomeId < 1) {
            return redirect()->back()->withInput()->with('error', 'Choose the follow-up outcome.');
        }

        if ($remarks === '') {
            return redirect()->back()->withInput()->with('error', 'Add follow-up remarks before saving.');
        }

        $followupId = $this->followupModel->insertWithActor([
            'tenant_id'             => $tenantId,
            'enquiry_id'            => (int) $enquiry->id,
            'branch_id'             => $enquiry->branch_id ?: null,
            'owner_user_id'         => $enquiry->owner_user_id ?: null,
            'communication_type_id' => $communicationTypeId,
            'followup_outcome_id'   => $followupOutcomeId,
            'remarks'               => $remarks,
            'next_followup_at'      => $nextFollowupAt !== '' ? $nextFollowupAt : null,
            'is_system_generated'   => 0,
        ]);

        $enquiryUpdate = [
            'last_followup_at' => date('Y-m-d H:i:s'),
            'next_followup_at' => $nextFollowupAt !== '' ? $nextFollowupAt : null,
        ];

        if ($enquiry->lifecycle_status === 'new') {
            $enquiryUpdate['lifecycle_status'] = 'active';

            $this->statusLogModel->insertWithActor([
                'tenant_id'   => $tenantId,
                'enquiry_id'  => (int) $enquiry->id,
                'from_status' => 'new',
                'to_status'   => 'active',
                'reason'      => 'Follow-up added',
                'changed_by'  => session()->get('user_id') ?: null,
            ]);
        }

        $this->enquiryModel->updateWithActor((int) $enquiry->id, $enquiryUpdate);

        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'Follow-up added successfully.');
    }

    public function editFollowup(int $id, int $followupId): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('permissions')->has('followups.edit')) {
            return redirect()->to('/enquiries/' . $id)->with('error', 'You do not have access to edit follow-ups.');
        }

        $followup = $this->findFollowupForEnquiry($tenantId, $id, $followupId);
        if (! $followup) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('enquiries/followup_form', $this->buildShellViewData([
            'title' => 'Edit Follow-up',
            'pageTitle' => 'Edit Follow-up',
            'activeNav' => 'enquiries',
            'enquiry' => $enquiry,
            'followup' => $followup,
            'communicationModes' => service('masterData')->getEffectiveValues('mode_of_communication', $tenantId),
            'followupStatuses' => service('masterData')->getEffectiveValues('followup_status', $tenantId),
        ]));
    }

    public function updateFollowup(int $id, int $followupId)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('permissions')->has('followups.edit')) {
            return redirect()->to('/enquiries/' . $id)->with('error', 'You do not have access to edit follow-ups.');
        }

        $followup = $this->findFollowupForEnquiry($tenantId, $id, $followupId);
        if (! $followup) {
            throw PageNotFoundException::forPageNotFound();
        }

        $communicationTypeId = (int) $this->request->getPost('communication_type_id');
        $followupOutcomeId = (int) $this->request->getPost('followup_outcome_id');
        $remarks = trim((string) $this->request->getPost('remarks'));
        $nextFollowupAt = trim((string) $this->request->getPost('next_followup_at'));

        if ($communicationTypeId < 1) {
            return redirect()->back()->withInput()->with('error', 'Choose the communication mode for this follow-up.');
        }

        if ($followupOutcomeId < 1) {
            return redirect()->back()->withInput()->with('error', 'Choose the follow-up outcome.');
        }

        if ($remarks === '') {
            return redirect()->back()->withInput()->with('error', 'Add follow-up remarks before saving.');
        }

        $this->followupModel->updateWithActor($followupId, [
            'communication_type_id' => $communicationTypeId,
            'followup_outcome_id' => $followupOutcomeId,
            'remarks' => $remarks,
            'next_followup_at' => $nextFollowupAt !== '' ? $nextFollowupAt : null,
        ]);

        $this->refreshEnquiryFollowupSnapshot((int) $enquiry->id);
        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'Follow-up updated successfully.');
    }

    public function deleteFollowup(int $id, int $followupId)
    {
        $tenantId = (int) session()->get('tenant_id');
        $enquiry = $this->queueService->findVisibleById($tenantId, $id, $this->currentBranchContextId());
        if (! $enquiry) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('permissions')->has('followups.delete')) {
            return redirect()->to('/enquiries/' . $id)->with('error', 'You do not have access to delete follow-ups.');
        }

        $followup = $this->findFollowupForEnquiry($tenantId, $id, $followupId);
        if (! $followup) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->followupModel->delete($followupId);
        $this->refreshEnquiryFollowupSnapshot((int) $enquiry->id);
        return redirect()->to('/enquiries/' . (int) $enquiry->id)->with('message', 'Follow-up deleted successfully.');
    }

    public function bulkAssignSubmit()
    {
        $tenantId = (int) session()->get('tenant_id');
        $selectedIds = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('enquiry_ids')), static fn(int $id): bool => $id > 0)));
        $branchId = (int) $this->request->getPost('branch_id');
        $ownerUserId = (int) $this->request->getPost('owner_user_id');
        $comment = trim((string) $this->request->getPost('assignment_comment'));

        if ($selectedIds === []) {
            return redirect()->back()->withInput()->with('error', 'Select at least one enquiry for bulk assignment.');
        }

        if ($branchId < 1 || $ownerUserId < 1) {
            return redirect()->back()->withInput()->with('error', 'Choose both branch and assigned to before saving bulk assignment.');
        }

        if (! $this->userAccessScope->canAssignBranches([$branchId])) {
            return redirect()->back()->withInput()->with('error', 'Selected branch is outside your allowed scope.');
        }

        if (! $this->userAccessScope->canAssignManager($ownerUserId)) {
            return redirect()->back()->withInput()->with('error', 'Selected assignee is outside your allowed scope.');
        }

        if (! $this->isUserAssignableToBranch($tenantId, $ownerUserId, $branchId)) {
            return redirect()->back()->withInput()->with('error', 'Choose an employee from the selected branch.');
        }

        $visibleRows = $this->queueService->getVisibleRows($tenantId, $this->currentBranchContextId());
        $visibleById = [];
        foreach ($visibleRows as $row) {
            $visibleById[(int) $row->id] = $row;
        }

        $batchId = 'bulk-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $updated = 0;

        foreach ($selectedIds as $enquiryId) {
            if (! isset($visibleById[$enquiryId])) {
                continue;
            }

            $enquiry = $visibleById[$enquiryId];
            if ($enquiry->lifecycle_status === 'admitted') {
                continue;
            }

            if ((int) ($enquiry->branch_id ?? 0) === $branchId && (int) ($enquiry->owner_user_id ?? 0) === $ownerUserId) {
                continue;
            }

            $this->enquiryModel->updateWithActor($enquiryId, [
                'branch_id'     => $branchId,
                'owner_user_id' => $ownerUserId,
                'assigned_on'   => date('Y-m-d H:i:s'),
            ]);

            $this->assignmentHistoryModel->insertWithActor([
                'tenant_id'       => $tenantId,
                'enquiry_id'      => $enquiryId,
                'from_branch_id'  => $enquiry->branch_id ?: null,
                'to_branch_id'    => $branchId,
                'from_user_id'    => $enquiry->owner_user_id ?: null,
                'to_user_id'      => $ownerUserId,
                'assigned_by'     => session()->get('user_id') ?: null,
                'assignment_type' => 'bulk_manual',
                'reason'          => 'Bulk assigned from enquiry workspace',
                'bulk_batch_id'   => $batchId,
                'assigned_on'     => date('Y-m-d H:i:s'),
            ]);

            $this->createSystemAssignmentFollowup(
                $tenantId,
                $enquiryId,
                $branchId,
                $ownerUserId,
                $comment !== '' ? $comment : 'Enquiry reassigned from bulk assign.'
            );

            $updated++;
        }

        if ($updated === 0) {
            return redirect()->back()->withInput()->with('error', 'No selected enquiries needed reassignment.');
        }

        return redirect()->to('/enquiries/bulk-assign')->with('message', $updated . ' enquiries reassigned successfully.');
    }

    protected function renderQueuePage(string $tab): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $tab = in_array($tab, ['enquiries', 'today', 'missed', 'fresh', 'expired', 'closed'], true) ? $tab : 'enquiries';
        $rows = $this->queueService->getRows($tenantId, $tab, $this->currentBranchContextId());
        $editableRowsById = [];

        foreach ($rows as $row) {
            $this->decorateEnquiryRow($row);
            $row->mobile_display = $this->formatMobile((string) $row->mobile);
            if (in_array($row->lifecycle_status, ['new', 'active'], true)) {
                $editableRowsById[(int) $row->id] = $this->queueService->findVisibleById($tenantId, (int) $row->id, $this->currentBranchContextId()) ?: $row;
            }
        }

        return view('enquiries/index', $this->buildShellViewData([
            'title' => 'Enquiries',
            'pageTitle' => $tab === 'expired' ? 'Expired Enquiries' : ($tab === 'closed' ? 'Closed Enquiries' : 'Enquiries'),
            'activeNav' => 'enquiries',
            'rows' => $rows,
            'currentTab' => $tab,
            'sources' => service('masterData')->getEffectiveValues('enquiry_source', $tenantId),
            'qualifications' => service('masterData')->getEffectiveValues('lead_qualification', $tenantId),
            'courses' => service('masterData')->getEffectiveValues('course', $tenantId),
            'colleges' => $this->collegeModel->getActiveOptions($tenantId, '', 500),
            'assignableBranches' => $this->getAssignableBranches(),
            'assignableUsers' => $this->getAssignableUsers($tenantId),
            'assignableUsersByBranch' => $this->getAssignableUsersByBranch($tenantId),
            'editableRowsById' => $editableRowsById,
        ]));
    }

    protected function collectBulkAssignFilters(): array
    {
        return [
            'search' => trim((string) $this->request->getGet('search')),
            'status' => trim((string) $this->request->getGet('status')),
            'source_id' => (int) $this->request->getGet('source_id'),
            'primary_course_id' => (int) $this->request->getGet('primary_course_id'),
            'branch_id' => (int) $this->request->getGet('branch_id'),
            'owner_user_id' => (int) $this->request->getGet('owner_user_id'),
        ];
    }

    protected function filterBulkAssignRows(array $rows, array $filters): array
    {
        $search = strtolower($filters['search']);
        $status = $filters['status'];

        return array_values(array_filter($rows, static function (object $row) use ($filters, $search, $status): bool {
            if ($search !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($row->student_name ?? ''),
                    (string) ($row->mobile ?? ''),
                    (string) ($row->source_display ?? ''),
                    (string) ($row->course_display ?? ''),
                    (string) ($row->branch_display ?? ''),
                    (string) ($row->owner_display ?? ''),
                ]));

                if (! str_contains($haystack, $search)) {
                    return false;
                }
            }

            if (($filters['source_id'] ?? 0) > 0 && (int) ($row->source_id ?? 0) !== (int) $filters['source_id']) {
                return false;
            }

            if (($filters['primary_course_id'] ?? 0) > 0 && (int) ($row->primary_course_id ?? 0) !== (int) $filters['primary_course_id']) {
                return false;
            }

            if (($filters['branch_id'] ?? 0) > 0 && (int) ($row->branch_id ?? 0) !== (int) $filters['branch_id']) {
                return false;
            }

            if (($filters['owner_user_id'] ?? 0) > 0 && (int) ($row->owner_user_id ?? 0) !== (int) $filters['owner_user_id']) {
                return false;
            }

            if ($status === '' || $status === 'all') {
                return true;
            }

            return match ($status) {
                'active' => in_array($row->lifecycle_status, ['new', 'active'], true) && empty($row->is_expired),
                'expired' => ! empty($row->is_expired),
                'closed' => $row->lifecycle_status === 'closed',
                'admitted' => $row->lifecycle_status === 'admitted',
                default => true,
            };
        }));
    }

    protected function buildFormViewData(array $data): array
    {
        $tenantId = (int) session()->get('tenant_id');
        service('collegeService')->ensureDefaultCollegeExists($tenantId);

        return $this->buildShellViewData(array_merge([
            'activeNav' => 'enquiries',
            'sources' => service('masterData')->getEffectiveValues('enquiry_source', $tenantId),
            'qualifications' => service('masterData')->getEffectiveValues('lead_qualification', $tenantId),
            'courses' => service('masterData')->getEffectiveValues('course', $tenantId),
            'colleges' => $this->collegeModel->getActiveOptions($tenantId, '', 500),
            'assignableBranches' => $this->getAssignableBranches(),
            'assignableUsers' => $this->getAssignableUsers($tenantId),
            'assignableUsersByBranch' => $this->getAssignableUsersByBranch($tenantId),
        ], $data));
    }

    protected function getAssignableBranches(): array
    {
        $branches = $this->branchModel->getActiveBranches();
        return $this->userAccessScope->filterAssignableBranches($branches);
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

    protected function canReassignInEdit(object $enquiry): bool
    {
        return in_array($enquiry->lifecycle_status, ['new', 'active'], true)
            && service('permissions')->has('enquiries.reassign_in_edit');
    }

    protected function canAssignFromDetail(object $enquiry): bool
    {
        if ($enquiry->lifecycle_status === 'closed') {
            return service('permissions')->has('enquiries.closed_assign');
        }

        if (! empty($enquiry->is_expired)) {
            return service('permissions')->has('enquiries.expired_assign');
        }

        return false;
    }

    protected function collectPayload(): array
    {
        return [
            'student_name'      => trim((string) $this->request->getPost('student_name')),
            'email'             => strtolower(trim((string) $this->request->getPost('email'))),
            'mobile'            => trim((string) $this->request->getPost('mobile')),
            'whatsapp_number'   => trim((string) $this->request->getPost('whatsapp_number')),
            'source_id'         => (int) $this->request->getPost('source_id'),
            'college_id'        => (int) $this->request->getPost('college_id'),
            'qualification_id'  => (int) $this->request->getPost('qualification_id'),
            'primary_course_id' => (int) $this->request->getPost('primary_course_id'),
            'city'              => trim((string) $this->request->getPost('city')),
            'notes'             => trim((string) $this->request->getPost('notes')),
            'branch_id'         => (int) $this->request->getPost('branch_id'),
            'owner_user_id'     => (int) $this->request->getPost('owner_user_id'),
            'next_followup_at'  => trim((string) $this->request->getPost('next_followup_at')),
        ];
    }

    protected function validatePayload(array $data, int $tenantId, bool $isUpdate = false): array
    {
        $errors = [];

        if ($data['student_name'] === '') {
            $errors[] = 'Student name is required.';
        }

        if ($data['mobile'] === '') {
            $errors[] = 'Mobile number is required.';
        }

        if ($data['source_id'] < 1) {
            $errors[] = 'Choose enquiry source.';
        }

        if ($data['primary_course_id'] < 1) {
            $errors[] = 'Choose course.';
        }

        if ($data['college_id'] < 1) {
            $errors[] = 'Choose college.';
        }

        if ($data['email'] !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email must be a valid email address.';
        }

        if ($isUpdate && $data['branch_id'] > 0 && ! $this->userAccessScope->canAssignBranches([$data['branch_id']])) {
            $errors[] = 'Selected branch is outside your allowed scope.';
        }

        if ($isUpdate && $data['owner_user_id'] > 0 && ! $this->userAccessScope->canAssignManager($data['owner_user_id'])) {
            $errors[] = 'Selected assignee is outside your allowed scope.';
        }

        if ($isUpdate && $data['branch_id'] > 0 && $data['owner_user_id'] > 0 && ! $this->isUserAssignableToBranch($tenantId, $data['owner_user_id'], $data['branch_id'])) {
            $errors[] = 'Choose an employee from the selected branch.';
        }

        return $errors;
    }

    protected function hasInitialFollowup(array $data): bool
    {
        return $data['notes'] !== '' || $data['next_followup_at'] !== '';
    }

    protected function currentBranchContextId(): ?int
    {
        $branchId = session()->get('branch_id');
        return $branchId ? (int) $branchId : null;
    }

    protected function formatMobile(string $mobile): string
    {
        if (service('permissions')->has('enquiries.view_mobile_number')) {
            return $mobile;
        }

        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        if ($digits === '') {
            return $mobile;
        }

        if (strlen($digits) <= 4) {
            return str_repeat('x', strlen($digits));
        }

        return substr($digits, 0, 2) . str_repeat('x', max(strlen($digits) - 4, 0)) . substr($digits, -2);
    }

    protected function getFollowupHistory(int $enquiryId): array
    {
        return db_connect()->table('enquiry_followups followups')
            ->select([
                'followups.*',
                'mode.label AS communication_mode_label',
                'status.label AS followup_outcome_label',
                "TRIM(CONCAT(COALESCE(created_by_user.first_name, ''), ' ', COALESCE(created_by_user.last_name, ''))) AS created_by_name",
            ])
            ->join('master_data_values mode', 'mode.id = followups.communication_type_id', 'left')
            ->join('master_data_values status', 'status.id = followups.followup_outcome_id', 'left')
            ->join('users created_by_user', 'created_by_user.id = followups.created_by', 'left')
            ->where('followups.enquiry_id', $enquiryId)
            ->orderBy('followups.created_at', 'DESC')
            ->get()
            ->getResult();
    }

    protected function getAuditHistory(int $enquiryId): array
    {
        $rows = db_connect()->table('audit_logs logs')
            ->select([
                'logs.*',
                "TRIM(CONCAT(COALESCE(actor.first_name, ''), ' ', COALESCE(actor.last_name, ''))) AS actor_name",
            ])
            ->join('users actor', 'actor.id = logs.user_id', 'left')
            ->groupStart()
                ->whereIn('logs.entity_type', ['enquiry', 'enquiry_followup', 'enquiry_assignment', 'enquiry_status'])
                ->where('logs.entity_id', $enquiryId)
            ->groupEnd()
            ->orderBy('logs.created_at', 'DESC')
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            $row->actor_display = trim((string) ($row->actor_name ?? '')) ?: 'System';
            $row->changes = [];

            $oldValues = $this->decodeAuditJson($row->old_values ?? null);
            $newValues = $this->decodeAuditJson($row->new_values ?? null);
            $fieldNames = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

            foreach ($fieldNames as $field) {
                $row->changes[] = (object) [
                    'field' => $this->labelAuditField((string) $field),
                    'old_value' => $this->formatAuditValue((string) $field, $oldValues[$field] ?? null),
                    'new_value' => $this->formatAuditValue((string) $field, $newValues[$field] ?? null),
                ];
            }
        }

        return $rows;
    }

    protected function decodeAuditJson(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function labelAuditField(string $field): string
    {
        return match ($field) {
            'owner_user_id' => 'Assigned to',
            'branch_id' => 'Branch',
            'assigned_on' => 'Assigned on',
            'student_name' => 'Student name',
            'whatsapp_number' => 'WhatsApp number',
            'source_id' => 'Enquiry source',
            'college_id' => 'College',
            'qualification_id' => 'Lead stage',
            'primary_course_id' => 'Course',
            'closed_reason_id' => 'Close reason',
            'closed_remarks' => 'Close remarks',
            'last_followup_at' => 'Last follow-up',
            'next_followup_at' => 'Next follow-up',
            'closed_at' => 'Closed on',
            'closed_by' => 'Closed by',
            'admitted_at' => 'Admitted on',
            'lifecycle_status' => 'Status',
            'communication_type_id' => 'Communication mode',
            'followup_outcome_id' => 'Follow-up outcome',
            'is_system_generated' => 'System generated',
            'from_branch_id' => 'From branch',
            'to_branch_id' => 'To branch',
            'from_user_id' => 'From user',
            'to_user_id' => 'To user',
            'assignment_type' => 'Assignment type',
            'reason' => 'Reason',
            'bulk_batch_id' => 'Bulk batch',
            'from_status' => 'From status',
            'to_status' => 'To status',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }

    protected function formatAuditValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return match ($field) {
            'source_id', 'qualification_id', 'primary_course_id', 'closed_reason_id', 'communication_type_id', 'followup_outcome_id' => $this->resolveMasterValueLabel((int) $value),
            'college_id' => $this->resolveCollegeLabel((int) $value),
            'branch_id', 'from_branch_id', 'to_branch_id' => $this->resolveBranchLabel((int) $value),
            'owner_user_id', 'closed_by', 'from_user_id', 'to_user_id' => $this->resolveUserLabel((int) $value),
            'lifecycle_status' => ucfirst((string) $value),
            'assignment_type' => ucwords(str_replace('_', ' ', (string) $value)),
            'is_system_generated' => (int) $value === 1 ? 'Yes' : 'No',
            'from_status', 'to_status' => $value === null || $value === '' ? '-' : ucfirst((string) $value),
            'last_followup_at', 'next_followup_at', 'closed_at', 'admitted_at' => date('d M Y h:i A', strtotime((string) $value)),
            default => (string) $value,
        };
    }

    protected function resolveMasterValueLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('master_data_values')->select('label')->where('id', $id)->get()->getRow();

        return $row->label ?? '-';
    }

    protected function resolveCollegeLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('colleges')->select('name')->where('id', $id)->get()->getRow();

        return $row->name ?? '-';
    }

    protected function resolveBranchLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('tenant_branches')->select('name')->where('id', $id)->get()->getRow();

        return $row->name ?? '-';
    }

    protected function resolveUserLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('users')
            ->select("TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) AS name")
            ->where('id', $id)
            ->get()
            ->getRow();

        return trim((string) ($row->name ?? '')) ?: '-';
    }

    protected function findFollowupForEnquiry(int $tenantId, int $enquiryId, int $followupId): ?object
    {
        return $this->followupModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('enquiry_id', $enquiryId)
            ->where('id', $followupId)
            ->first();
    }

    protected function refreshEnquiryFollowupSnapshot(int $enquiryId): void
    {
        $latest = $this->followupModel->withoutTenantScope()
            ->where('enquiry_id', $enquiryId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        $enquiry = $this->enquiryModel->withoutTenantScope()->find($enquiryId);
        if (! $enquiry) {
            return;
        }

        $update = [
            'last_followup_at' => $latest?->created_at,
            'next_followup_at' => $latest?->next_followup_at,
        ];

        if (in_array($enquiry->lifecycle_status, ['new', 'active'], true)) {
            $update['lifecycle_status'] = $latest ? 'active' : 'new';
        }

        $this->enquiryModel->updateWithActor($enquiryId, $update);
    }

    protected function decorateEnquiryRow(object $row): void
    {
        if (($row->lifecycle_status ?? '') === 'admitted') {
            $row->display_status = 'Admitted';
            return;
        }

        if (($row->lifecycle_status ?? '') === 'closed') {
            $row->display_status = 'Closed';
            return;
        }

        if (! empty($row->is_expired)) {
            $row->display_status = 'Expired';
            return;
        }

        $row->display_status = 'Active';
    }

    protected function createSystemAssignmentFollowup(int $tenantId, int $enquiryId, int $branchId, int $ownerUserId, string $comment): void
    {
        $this->followupModel->insertWithActor([
            'tenant_id' => $tenantId,
            'enquiry_id' => $enquiryId,
            'branch_id' => $branchId,
            'owner_user_id' => $ownerUserId,
            'communication_type_id' => null,
            'followup_outcome_id' => null,
            'remarks' => $comment,
            'next_followup_at' => null,
            'is_system_generated' => 1,
        ]);

        $this->refreshEnquiryFollowupSnapshot($enquiryId);
    }
}
