<?php

namespace App\Controllers;

use App\Models\MasterDataTypeModel;
use App\Models\MasterDataValueModel;
use App\Models\TenantMasterDataOverrideModel;

class MasterData extends BaseController
{
    protected MasterDataTypeModel $typeModel;
    protected MasterDataValueModel $valueModel;
    protected TenantMasterDataOverrideModel $overrideModel;

    public function __construct()
    {
        $this->typeModel = new MasterDataTypeModel();
        $this->valueModel = new MasterDataValueModel();
        $this->overrideModel = new TenantMasterDataOverrideModel();
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $types = $this->typeModel->where('status', 'active')
                                 ->orderBy('sort_order', 'ASC')
                                 ->orderBy('name', 'ASC')
                                 ->findAll();

        $selectedTypeCode = (string) ($this->request->getGet('type') ?: ($types[0]->code ?? ''));
        $selectedType = $selectedTypeCode !== '' ? $this->typeModel->findByCode($selectedTypeCode) : null;
        $effectiveValues = $selectedType ? service('masterData')->getEffectiveValues($selectedTypeCode, $tenantId) : [];
        $platformValues = $selectedType ? service('masterData')->getPlatformValues($selectedTypeCode) : [];
        $tenantValues = $selectedType ? service('masterData')->getTenantValues($selectedTypeCode, $tenantId) : [];
        $overrideMap = $selectedType
            ? $this->overrideModel->getOverrideMapForTenant(
                $tenantId,
                array_map(static fn(object $row): int => (int) $row->id, $platformValues)
            )
            : [];

        return view('master_data/index', $this->buildShellViewData([
            'title'            => 'Master Data',
            'pageTitle'        => 'Master Data',
            'activeNav'        => 'master_data',
            'types'            => $types,
            'selectedType'     => $selectedType,
            'selectedTypeCode' => $selectedTypeCode,
            'effectiveValues'  => $effectiveValues,
            'platformValues'   => $platformValues,
            'tenantValues'     => $tenantValues,
            'overrideMap'      => $overrideMap,
        ]));
    }

    public function storeValue(string $typeCode)
    {
        $tenantId = (int) session()->get('tenant_id');

        try {
            service('masterData')->createTenantValue($typeCode, $tenantId, [
                'code'        => (string) $this->request->getPost('code'),
                'label'       => (string) $this->request->getPost('label'),
                'description' => (string) $this->request->getPost('description'),
                'sort_order'  => (int) $this->request->getPost('sort_order'),
                'status'      => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
                'metadata'    => $this->decodeMetadata((string) $this->request->getPost('metadata_json')),
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/settings/master-data?type=' . $typeCode)->with('message', 'Custom master value created.');
    }

    public function togglePlatformValue(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $value = $this->valueModel->find($id);
        if (! $value) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $override = $this->overrideModel
            ->where('tenant_id', $tenantId)
            ->where('master_data_value_id', $id)
            ->first();

        try {
            if ($override && (int) $override->is_visible !== 1) {
                service('masterData')->showPlatformValue($tenantId, $id);
                $message = 'Platform value restored for this tenant.';
            } else {
                service('masterData')->hidePlatformValue($tenantId, $id);
                $message = 'Platform value hidden for this tenant.';
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $type = $this->typeModel->find((int) $value->type_id);
        return redirect()->to('/settings/master-data?type=' . ($type->code ?? ''))->with('message', $message);
    }

    public function updateTenantValueStatus(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $value = $this->valueModel->findTenantValue($id, $tenantId);
        if (! $value) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->valueModel->update($id, [
            'status'     => $value->status === 'active' ? 'inactive' : 'active',
            'updated_by' => session()->get('user_id') ?: null,
        ]);

        $type = $this->typeModel->find((int) $value->type_id);
        return redirect()->to('/settings/master-data?type=' . ($type->code ?? ''))->with('message', 'Tenant master value status updated.');
    }

    protected function decodeMetadata(string $raw): mixed
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $raw];
    }
}
