<?php

namespace App\Controllers;

use App\Controllers\Concerns\PaginatesCollections;
use App\Models\BatchModel;
use App\Models\BranchModel;
use App\Services\UserAccessScopeService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Batches extends BaseController
{
    use PaginatesCollections;

    protected BatchModel $batchModel;
    protected BranchModel $branchModel;
    protected UserAccessScopeService $userAccessScope;

    public function __construct()
    {
        $this->batchModel = new BatchModel();
        $this->branchModel = new BranchModel();
        $this->userAccessScope = service('userAccessScope');
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $rows = $this->batchModel->getAdminGrid($tenantId);
        $paginated = $this->paginateCollection($rows);

        return view('batches/index', $this->buildShellViewData([
            'title' => 'Batches',
            'pageTitle' => 'Batches',
            'activeNav' => 'batches',
            'batches' => $paginated['items'],
            'pagination' => $paginated['pagination'],
            'assignableBranches' => $this->getAssignableBranches(),
        ]));
    }

    public function store()
    {
        $tenantId = (int) session()->get('tenant_id');
        $payload = $this->collectPayload();

        if ($errors = $this->validatePayload($payload, $tenantId)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->batchModel->insertWithActor([
            'tenant_id' => $tenantId,
            'branch_id' => $payload['branch_id'],
            'name' => $payload['name'],
            'code' => $payload['code'] !== '' ? $payload['code'] : null,
            'starts_on' => $payload['starts_on'] !== '' ? $payload['starts_on'] : null,
            'ends_on' => $payload['ends_on'] !== '' ? $payload['ends_on'] : null,
            'capacity' => $payload['capacity'] > 0 ? $payload['capacity'] : null,
            'status' => $payload['status'],
            'notes' => $payload['notes'] !== '' ? $payload['notes'] : null,
        ]);

        return redirect()->to('/batches')->with('message', 'Batch created successfully.');
    }

    public function update(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $batch = $this->batchModel->findForTenant($id);
        if (! $batch) {
            throw PageNotFoundException::forPageNotFound();
        }

        $payload = $this->collectPayload();
        if ($errors = $this->validatePayload($payload, $tenantId, $id)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->batchModel->updateWithActor($id, [
            'branch_id' => $payload['branch_id'],
            'name' => $payload['name'],
            'code' => $payload['code'] !== '' ? $payload['code'] : null,
            'starts_on' => $payload['starts_on'] !== '' ? $payload['starts_on'] : null,
            'ends_on' => $payload['ends_on'] !== '' ? $payload['ends_on'] : null,
            'capacity' => $payload['capacity'] > 0 ? $payload['capacity'] : null,
            'status' => $payload['status'],
            'notes' => $payload['notes'] !== '' ? $payload['notes'] : null,
        ]);

        return redirect()->to('/batches')->with('message', 'Batch updated successfully.');
    }

    public function delete(int $id)
    {
        $batch = $this->batchModel->findForTenant($id);
        if (! $batch) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->batchModel->countAssignedAdmissions($id) > 0) {
            return redirect()->to('/batches')->with('error', 'This batch is assigned to admissions and cannot be removed yet.');
        }

        $this->batchModel->delete($id);
        return redirect()->to('/batches')->with('message', 'Batch removed successfully.');
    }

    protected function collectPayload(): array
    {
        return [
            'branch_id' => (int) $this->request->getPost('branch_id'),
            'name' => trim((string) $this->request->getPost('name')),
            'code' => strtoupper(trim((string) $this->request->getPost('code'))),
            'starts_on' => trim((string) $this->request->getPost('starts_on')),
            'ends_on' => trim((string) $this->request->getPost('ends_on')),
            'capacity' => (int) $this->request->getPost('capacity'),
            'status' => trim((string) $this->request->getPost('status')),
            'notes' => trim((string) $this->request->getPost('notes')),
        ];
    }

    protected function validatePayload(array $payload, int $tenantId, ?int $ignoreId = null): array
    {
        $errors = [];

        if ($payload['name'] === '') {
            $errors[] = 'Batch name is required.';
        }

        if ($payload['branch_id'] < 1) {
            $errors[] = 'Choose branch.';
        } elseif (! $this->userAccessScope->canAssignBranches([(int) $payload['branch_id']])) {
            $errors[] = 'Selected branch is outside your allowed scope.';
        }

        if ($payload['status'] === '' || ! in_array($payload['status'], ['active', 'inactive', 'completed'], true)) {
            $errors[] = 'Choose a valid batch status.';
        }

        if ($payload['ends_on'] !== '' && $payload['starts_on'] !== '' && $payload['ends_on'] < $payload['starts_on']) {
            $errors[] = 'End date cannot be before start date.';
        }

        if ($payload['code'] !== '') {
            $existing = $this->batchModel->withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('code', $payload['code']);
            if ($ignoreId !== null) {
                $existing->where('id !=', $ignoreId);
            }
            if ($existing->countAllResults() > 0) {
                $errors[] = 'Batch code already exists.';
            }
        }

        return $errors;
    }

    protected function getAssignableBranches(): array
    {
        return $this->userAccessScope->filterAssignableBranches($this->branchModel->getActiveBranches());
    }
}
