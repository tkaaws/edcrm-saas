<?php

namespace App\Models;

class TenantSettingsModel extends BaseModel
{
    protected $table      = 'tenant_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'branding_name',
        'logo_path',
        'favicon_path',
        'default_timezone',
        'default_currency_code',
        'locale_code',
        'branch_visibility_mode',
        'enquiry_visibility_mode',
        'admission_visibility_mode',
    ];

    public function findByTenant(int $tenantId): ?object
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->first();
    }
}
