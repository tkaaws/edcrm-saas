<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $firstName = session()->get('user_first_name');
        $lastName  = session()->get('user_last_name');
        $roleCode  = session()->get('user_role_code');
        $tenantId  = session()->get('tenant_id');
        $branchId  = session()->get('branch_id');

        return view('dashboard/index', [
            'title'           => 'Dashboard',
            'pageTitle'       => 'Operations Dashboard',
            'firstName'       => $firstName,
            'roleCode'        => $roleCode,
            'tenantId'        => $tenantId,
            'branchId'        => $branchId,
            'tenantLabel'     => $tenantId ? 'Tenant #' . $tenantId : 'Tenant not loaded',
            'branchLabel'     => $branchId ? 'Branch #' . $branchId : 'Branch not assigned',
            'roleLabel'       => $roleCode ?: 'Role pending',
            'userDisplayName' => trim((string) $firstName . ' ' . (string) $lastName) ?: 'EDCRM User',
            'userEmail'       => session()->get('user_email'),
        ]);
    }
}
