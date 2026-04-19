<?php

namespace App\Controllers;

use App\Controllers\Concerns\PaginatesCollections;
use App\Models\MasterDataTypeModel;
use App\Models\MasterDataValueModel;

class PlatformMasterData extends BaseController
{
    use PaginatesCollections;

    protected MasterDataTypeModel $typeModel;
    protected MasterDataValueModel $valueModel;

    public function __construct()
    {
        $this->typeModel = new MasterDataTypeModel();
        $this->valueModel = new MasterDataValueModel();
    }

    public function index(): string
    {
        $types = $this->typeModel->orderBy('sort_order', 'ASC')
                                 ->orderBy('name', 'ASC')
                                 ->findAll();

        $selectedTypeCode = (string) ($this->request->getGet('type') ?: ($types[0]->code ?? ''));
        $selectedType = $selectedTypeCode !== '' ? $this->typeModel->findByCode($selectedTypeCode) : null;
        $values = $selectedType
            ? $this->valueModel->where('type_id', $selectedType->id)
                               ->where('scope_type', 'platform')
                               ->orderBy('sort_order', 'ASC')
                               ->orderBy('label', 'ASC')
                               ->findAll()
            : [];
        $paginated = $this->paginateCollection($values);

        return view('platform/master_data/index', $this->buildShellViewData([
            'title'            => 'Platform Business Lookup Data',
            'pageTitle'        => 'Platform Business Lookup Data',
            'activeNav'        => 'platform_master_data',
            'tenantLabel'      => 'Platform scope',
            'branchLabel'      => 'Catalog management',
            'roleLabel'        => 'Standardization',
            'types'            => $types,
            'selectedType'     => $selectedType,
            'selectedTypeCode' => $selectedTypeCode,
            'allValues'        => $values,
            'values'           => $paginated['items'],
            'pagination'       => $paginated['pagination'],
        ]));
    }

    public function initializeDefaults()
    {
        $this->callSeeder('MasterDataCatalogSeeder');

        return redirect()->to('/platform/master-data')->with('message', 'Default lookup lists initialized.');
    }

    public function storeType()
    {
        $payload = [
            'code'                              => $this->normalizeCode((string) $this->request->getPost('code') ?: (string) $this->request->getPost('name')),
            'name'                              => trim((string) $this->request->getPost('name')),
            'description'                       => trim((string) $this->request->getPost('description')),
            'module_code'                       => trim((string) $this->request->getPost('module_code')),
            'status'                            => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
            'allow_platform_entries'            => 1,
            'allow_tenant_entries'              => $this->request->getPost('allow_tenant_entries') ? 1 : 0,
            'allow_tenant_hide_platform_values' => $this->request->getPost('allow_tenant_hide_platform_values') ? 1 : 0,
            'strict_reporting_catalog'          => $this->request->getPost('strict_reporting_catalog') ? 1 : 0,
            'supports_hierarchy'                => $this->request->getPost('supports_hierarchy') ? 1 : 0,
            'sort_order'                        => (int) $this->request->getPost('sort_order'),
        ];

        $errors = [];
        if ($payload['name'] === '') {
            $errors[] = 'Type name is required.';
        }
        if ($payload['code'] === '') {
            $errors[] = 'Type code is required.';
        }
        if ($payload['module_code'] === '') {
            $errors[] = 'Module code is required.';
        }
        if ($this->typeModel->findByCode($payload['code'])) {
            $errors[] = 'Type code already exists.';
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->typeModel->insert($payload);

        return redirect()->to('/platform/master-data?type=' . $payload['code'])->with('message', 'Lookup list created.');
    }

    public function updateTypeStatus(int $id)
    {
        $type = $this->typeModel->find($id);
        if (! $type) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->typeModel->update($id, [
            'status' => $type->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->to('/platform/master-data?type=' . $type->code)->with('message', 'Lookup list status updated.');
    }

    public function storeValue()
    {
        $typeId = (int) $this->request->getPost('type_id');
        $type = $this->typeModel->find($typeId);
        if (! $type) {
            return redirect()->back()->with('error', 'Lookup list not found.');
        }

        $payload = [
            'type_id'         => $typeId,
            'scope_type'      => 'platform',
            'tenant_id'       => null,
            'parent_value_id' => $this->request->getPost('parent_value_id') ? (int) $this->request->getPost('parent_value_id') : null,
            'code'            => $this->normalizeCode((string) $this->request->getPost('code') ?: (string) $this->request->getPost('label')),
            'label'           => trim((string) $this->request->getPost('label')),
            'description'     => trim((string) $this->request->getPost('description')),
            'sort_order'      => (int) $this->request->getPost('sort_order'),
            'is_system'       => $this->request->getPost('is_system') ? 1 : 0,
            'status'          => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
            'metadata_json'   => $this->normalizeMetadata((string) $this->request->getPost('metadata_json')),
            'created_by'      => session()->get('user_id') ?: null,
            'updated_by'      => session()->get('user_id') ?: null,
        ];

        $errors = [];
        if ($payload['label'] === '') {
            $errors[] = 'Value label is required.';
        }
        if ($payload['code'] === '') {
            $errors[] = 'Value code is required.';
        }
        if ($this->valueModel->codeExistsForScope($typeId, 'platform', $payload['code'])) {
            $errors[] = 'Value code already exists for this type.';
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->valueModel->insert($payload);

        return redirect()->to('/platform/master-data?type=' . $type->code)->with('message', 'Platform value created.');
    }

    public function updateValueStatus(int $id)
    {
        $value = $this->valueModel->find($id);
        if (! $value) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $type = $this->typeModel->find((int) $value->type_id);

        $this->valueModel->update($id, [
            'status'     => $value->status === 'active' ? 'inactive' : 'active',
            'updated_by' => session()->get('user_id') ?: null,
        ]);

        return redirect()->to('/platform/master-data?type=' . ($type->code ?? ''))->with('message', 'Lookup value status updated.');
    }

    protected function normalizeCode(string $value): string
    {
        $code = strtolower(trim($value));
        $code = preg_replace('/[^a-z0-9]+/', '_', $code) ?? '';
        return trim($code, '_');
    }

    protected function normalizeMetadata(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    protected function callSeeder(string $class): void
    {
        /** @var \CodeIgniter\Database\Seeder $seeder */
        $seeder = \Config\Database::seeder();
        $seeder->call($class);
    }
}
