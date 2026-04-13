<?php

namespace App\Models;

class TenantEmailConfigModel extends BaseModel
{
    protected $table      = 'tenant_email_configs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'provider_name',
        'from_name',
        'from_email',
        'host',
        'port',
        'username',
        'password_encrypted',
        'encryption',
        'is_default',
        'status',
    ];

    public function findDefaultForTenant(int $tenantId): ?object
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->orderBy('is_default', 'DESC')
                    ->orderBy('id', 'ASC')
                    ->first();
    }
}
