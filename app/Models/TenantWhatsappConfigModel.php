<?php

namespace App\Models;

class TenantWhatsappConfigModel extends BaseModel
{
    protected $table      = 'tenant_whatsapp_configs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'provider_name',
        'api_base_url',
        'api_key_encrypted',
        'sender_id',
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
