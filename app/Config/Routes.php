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
    'filter' => ['auth', 'tenant', 'suspension'],
]);

// Tenant billing summary — auth + tenant isolation + suspension (read-only page, warnings still shown)
$routes->get('billing', 'Billing::index', ['filter' => ['auth', 'tenant', 'suspension']]);

// Tenant-scoped operational routes — auth + tenant isolation + suspension enforcement
$routes->group('', ['filter' => ['auth', 'tenant', 'suspension']], static function (RouteCollection $routes): void {
    $routes->get('users', 'Users::index');
    $routes->get('users/create', 'Users::create');
    $routes->post('users', 'Users::store');
    $routes->get('users/(:num)/edit', 'Users::edit/$1');
    $routes->post('users/(:num)', 'Users::update/$1');
    $routes->post('users/(:num)/status', 'Users::updateStatus/$1');
    $routes->get('branches', 'Branches::index');
    $routes->get('branches/create', 'Branches::create');
    $routes->post('branches', 'Branches::store');
    $routes->get('branches/(:num)/edit', 'Branches::edit/$1');
    $routes->post('branches/(:num)', 'Branches::update/$1');
    $routes->post('branches/(:num)/status', 'Branches::updateStatus/$1');
    $routes->get('roles', 'Roles::index');
    $routes->get('roles/create', 'Roles::create');
    $routes->post('roles', 'Roles::store');
    $routes->get('roles/(:num)/edit', 'Roles::edit/$1');
    $routes->post('roles/(:num)', 'Roles::update/$1');
    $routes->post('roles/(:num)/status', 'Roles::updateStatus/$1');
    $routes->get('settings', 'Settings::index');
    $routes->post('settings/profile', 'Settings::updateProfile');
    $routes->post('settings/preferences', 'Settings::updatePreferences');
    $routes->post('settings/email', 'Settings::updateEmailConfig');
    $routes->post('settings/whatsapp', 'Settings::updateWhatsappConfig');
});

// Feature-gated module routes — auth + tenant + suspension + feature gate
// Each group is locked behind its feature_catalog code.
// Add module controllers here as Phase 2 is built out.

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

// Platform admin routes — auth + platform_admin guard (no tenant or suspension filters)
$routes->group('platform', ['filter' => ['auth', 'platform_admin']], static function (RouteCollection $routes): void {
    $routes->get('tenants', 'PlatformTenants::index');
    $routes->get('tenants/create', 'PlatformTenants::create');
    $routes->post('tenants', 'PlatformTenants::store');
    $routes->get('tenants/(:num)', 'PlatformTenants::show/$1');
    $routes->post('tenants/(:num)/status', 'PlatformTenants::updateStatus/$1');

    // Tenants (edit/update/delete)
    $routes->get('tenants/(:num)/edit', 'PlatformTenants::edit/$1');
    $routes->post('tenants/(:num)/update', 'PlatformTenants::update/$1');
    $routes->post('tenants/(:num)/delete', 'PlatformTenants::delete/$1');

    // Plans
    $routes->get('plans', 'PlatformPlans::index');
    $routes->get('plans/create', 'PlatformPlans::create');
    $routes->post('plans', 'PlatformPlans::store');
    $routes->get('plans/(:num)', 'PlatformPlans::show/$1');
    $routes->post('plans/(:num)/price', 'PlatformPlans::updatePrice/$1');
    $routes->post('plans/(:num)/feature', 'PlatformPlans::updateFeature/$1');
    $routes->post('plans/(:num)/limit', 'PlatformPlans::updateLimit/$1');
    $routes->post('plans/(:num)/delete', 'PlatformPlans::delete/$1');

    // Subscriptions
    $routes->get('subscriptions', 'PlatformSubscriptions::index');
    $routes->post('subscriptions/attach', 'PlatformSubscriptions::attach');
    $routes->get('subscriptions/(:num)', 'PlatformSubscriptions::show/$1');
    $routes->post('subscriptions/(:num)/status', 'PlatformSubscriptions::updateStatus/$1');
    $routes->post('subscriptions/(:num)/override', 'PlatformSubscriptions::setOverride/$1');
    $routes->post('subscriptions/(:num)/delete', 'PlatformSubscriptions::delete/$1');
});
