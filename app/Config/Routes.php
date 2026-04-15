<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('auth', static function (RouteCollection $routes): void {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::loginPost');
    $routes->get('logout', 'Auth::logout');
    $routes->get('forgot-password', 'Auth::forgotPassword');
    $routes->post('forgot-password', 'Auth::forgotPasswordPost');
    $routes->get('reset-password/(:segment)', 'Auth::resetPassword/$1');
    $routes->post('reset-password', 'Auth::resetPasswordPost');
    $routes->get('change-password', 'Auth::changePassword', ['filter' => 'auth']);
    $routes->post('change-password', 'Auth::changePasswordPost', ['filter' => 'auth']);
});

$routes->get('dashboard', 'Dashboard::index', [
    'filter' => ['auth', 'tenant', 'suspension', 'feature:crm_core'],
]);

$routes->get('billing', 'Billing::index', [
    'filter' => ['auth', 'tenant', 'suspension', 'feature:crm_core', 'privilege:billing.view,billing.manage'],
]);

$routes->group('', ['filter' => ['auth', 'tenant', 'suspension']], static function (RouteCollection $routes): void {
    $routes->get('users', 'Users::index', ['filter' => ['feature:crm_core', 'privilege:users.view']]);
    $routes->get('users/create', 'Users::create', ['filter' => ['feature:crm_core', 'privilege:users.create']]);
    $routes->post('users', 'Users::store', ['filter' => ['feature:crm_core', 'privilege:users.create']]);
    $routes->get('users/(:num)/edit', 'Users::edit/$1', ['filter' => ['feature:crm_core', 'privilege:users.edit']]);
    $routes->post('users/(:num)', 'Users::update/$1', ['filter' => ['feature:crm_core', 'privilege:users.edit']]);
    $routes->post('users/(:num)/status', 'Users::updateStatus/$1', ['filter' => ['feature:crm_core', 'privilege:users.edit']]);

    $routes->get('branches', 'Branches::index', ['filter' => ['feature:crm_core', 'privilege:branches.view']]);
    $routes->get('branches/create', 'Branches::create', ['filter' => ['feature:crm_core', 'privilege:branches.create']]);
    $routes->post('branches', 'Branches::store', ['filter' => ['feature:crm_core', 'privilege:branches.create']]);
    $routes->get('branches/(:num)/edit', 'Branches::edit/$1', ['filter' => ['feature:crm_core', 'privilege:branches.edit']]);
    $routes->post('branches/(:num)', 'Branches::update/$1', ['filter' => ['feature:crm_core', 'privilege:branches.edit']]);
    $routes->post('branches/(:num)/status', 'Branches::updateStatus/$1', ['filter' => ['feature:crm_core', 'privilege:branches.edit']]);
    $routes->get('branches/(:num)/settings', 'BranchSettings::index/$1', ['filter' => ['feature:crm_core', 'privilege:branches.edit']]);
    $routes->post('branches/(:num)/settings/(:segment)', 'BranchSettings::updateCategory/$1/$2', ['filter' => ['feature:crm_core', 'privilege:branches.edit']]);

    $routes->get('roles', 'Roles::index', ['filter' => ['feature:crm_core', 'privilege:roles.view']]);
    $routes->get('roles/create', 'Roles::create', ['filter' => ['feature:crm_core', 'privilege:roles.create']]);
    $routes->post('roles', 'Roles::store', ['filter' => ['feature:crm_core', 'privilege:roles.create']]);
    $routes->get('roles/(:num)/edit', 'Roles::edit/$1', ['filter' => ['feature:crm_core', 'privilege:roles.edit']]);
    $routes->post('roles/(:num)', 'Roles::update/$1', ['filter' => ['feature:crm_core', 'privilege:roles.edit']]);
    $routes->post('roles/(:num)/status', 'Roles::updateStatus/$1', ['filter' => ['feature:crm_core', 'privilege:roles.edit']]);

    $routes->get('settings', 'Settings::index', ['filter' => ['feature:crm_core', 'privilege:settings.view']]);
    $routes->get('settings/master-data', 'MasterData::index', ['filter' => ['feature:crm_core', 'privilege:settings.view']]);
    $routes->post('settings/master-data/(:segment)', 'MasterData::storeValue/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/master-data/platform-value/(:num)/toggle', 'MasterData::togglePlatformValue/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/master-data/tenant-value/(:num)/status', 'MasterData::updateTenantValueStatus/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/profile', 'Settings::updateProfile', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/preferences', 'Settings::updatePreferences', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/catalog/(:segment)', 'Settings::updateCatalogCategory/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/email', 'Settings::updateEmailConfig', ['filter' => ['feature:crm_core', 'privilege:settings.smtp']]);
    $routes->post('settings/whatsapp', 'Settings::updateWhatsappConfig', ['filter' => ['feature:crm_core', 'privilege:settings.whatsapp']]);
});

$routes->group('impersonation', ['filter' => ['auth']], static function (RouteCollection $routes): void {
    $routes->post('start/(:num)', 'Impersonation::start/$1');
    $routes->post('stop', 'Impersonation::stop');
});

$routes->group('enquiries', ['filter' => ['auth', 'tenant', 'suspension', 'feature:crm_core']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Enquiries::index');
});

$routes->group('admissions', ['filter' => ['auth', 'tenant', 'suspension', 'feature:admissions']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Admissions::index');
});

$routes->group('batches', ['filter' => ['auth', 'tenant', 'suspension', 'feature:batch_management']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Batches::index');
});

$routes->group('service', ['filter' => ['auth', 'tenant', 'suspension', 'feature:service_tickets']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Service::index');
});

$routes->group('placement', ['filter' => ['auth', 'tenant', 'suspension', 'feature:placement']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Placement::index');
});

$routes->group('reports', ['filter' => ['auth', 'tenant', 'suspension', 'feature:advanced_reports']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Reports::index');
});

$routes->group('platform', ['filter' => ['auth', 'platform_admin']], static function (RouteCollection $routes): void {
    $routes->get('tenants', 'PlatformTenants::index');
    $routes->get('tenants/create', 'PlatformTenants::create');
    $routes->post('tenants', 'PlatformTenants::store');
    $routes->get('tenants/(:num)', 'PlatformTenants::show/$1');
    $routes->get('tenants/(:num)/policy', 'PlatformTenantPolicies::index/$1');
    $routes->post('tenants/(:num)/status', 'PlatformTenants::updateStatus/$1');
    $routes->post('tenants/(:num)/plan', 'PlatformTenants::updatePlan/$1');
    $routes->post('tenants/(:num)/policy/(:segment)/(:segment)', 'PlatformTenantPolicies::updateCategory/$1/$2/$3');

    $routes->get('tenants/(:num)/edit', 'PlatformTenants::edit/$1');
    $routes->post('tenants/(:num)/update', 'PlatformTenants::update/$1');
    $routes->post('tenants/(:num)/delete', 'PlatformTenants::delete/$1');

    $routes->get('plans', 'PlatformPlans::index');
    $routes->get('plans/create', 'PlatformPlans::create');
    $routes->post('plans', 'PlatformPlans::store');
    $routes->get('plans/(:num)', 'PlatformPlans::show/$1');
    $routes->post('plans/(:num)/price', 'PlatformPlans::updatePrice/$1');
    $routes->post('plans/(:num)/feature', 'PlatformPlans::updateFeature/$1');
    $routes->post('plans/(:num)/limit', 'PlatformPlans::updateLimit/$1');
    $routes->post('plans/(:num)/delete', 'PlatformPlans::delete/$1');

    $routes->get('subscriptions', 'PlatformSubscriptions::index');
    $routes->post('subscriptions/attach', 'PlatformSubscriptions::attach');
    $routes->get('subscriptions/(:num)', 'PlatformSubscriptions::show/$1');
    $routes->post('subscriptions/(:num)/status', 'PlatformSubscriptions::updateStatus/$1');
    $routes->post('subscriptions/(:num)/switch-plan', 'PlatformSubscriptions::switchPlan/$1');
    $routes->post('subscriptions/(:num)/override', 'PlatformSubscriptions::setOverride/$1');
    $routes->post('subscriptions/(:num)/delete', 'PlatformSubscriptions::delete/$1');

    $routes->get('master-data', 'PlatformMasterData::index');
    $routes->post('master-data/types', 'PlatformMasterData::storeType');
    $routes->post('master-data/types/(:num)/status', 'PlatformMasterData::updateTypeStatus/$1');
    $routes->post('master-data/values', 'PlatformMasterData::storeValue');
    $routes->post('master-data/values/(:num)/status', 'PlatformMasterData::updateValueStatus/$1');
});
