<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        return view('dashboard/index', $this->buildShellViewData([
            'title'           => 'Dashboard',
            'pageTitle'       => 'Operations Dashboard',
            'activeNav'       => 'dashboard',
        ]));
    }
}
