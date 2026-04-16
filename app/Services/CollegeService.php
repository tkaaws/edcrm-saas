<?php

namespace App\Services;

use App\Models\CollegeModel;

class CollegeService
{
    protected CollegeModel $collegeModel;

    public function __construct()
    {
        $this->collegeModel = new CollegeModel();
    }

    public function ensureDefaultCollegeExists(int $tenantId): object
    {
        $existing = $this->collegeModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'ASC')
            ->first();

        if ($existing) {
            return $existing;
        }

        $collegeId = $this->collegeModel->withoutTenantScope()->insert([
            'tenant_id'   => $tenantId,
            'name'        => 'Test College',
            'city_name'   => 'Pune',
            'state_name'  => 'Maharashtra',
            'status'      => 'active',
            'created_by'  => null,
            'updated_by'  => null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], true);

        return $this->collegeModel->withoutTenantScope()->find((int) $collegeId);
    }

    public function searchOptions(int $tenantId, string $search = '', int $limit = 20): array
    {
        $this->ensureDefaultCollegeExists($tenantId);
        $rows = $this->collegeModel->getActiveOptions($tenantId, $search, $limit);

        return array_map(static function (object $college): array {
            return [
                'id'    => (int) $college->id,
                'label' => trim($college->name . ' - ' . $college->city_name . ', ' . $college->state_name),
            ];
        }, $rows);
    }
}
