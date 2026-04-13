<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        return view('dashboard/index', [
            'title'      => 'Dashboard',
            'firstName'  => session()->get('user_first_name'),
            'roleCode'   => session()->get('user_role_code'),
            'tenantId'   => session()->get('tenant_id'),
            'branchId'   => session()->get('branch_id'),
        ]);
    }
}
