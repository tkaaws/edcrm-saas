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

    $routes->get('colleges', 'Colleges::index', ['filter' => ['feature:crm_core', 'privilege:colleges.view']]);
    $routes->get('colleges/options', 'Colleges::options', ['filter' => ['feature:crm_core', 'privilege:enquiries.view,enquiries.create,enquiries.edit,colleges.view']]);
    $routes->get('colleges/create', 'Colleges::create', ['filter' => ['feature:crm_core', 'privilege:colleges.create']]);
    $routes->post('colleges', 'Colleges::store', ['filter' => ['feature:crm_core', 'privilege:colleges.create']]);
    $routes->get('colleges/(:num)/edit', 'Colleges::edit/$1', ['filter' => ['feature:crm_core', 'privilege:colleges.edit']]);
    $routes->post('colleges/(:num)', 'Colleges::update/$1', ['filter' => ['feature:crm_core', 'privilege:colleges.edit']]);
    $routes->post('colleges/(:num)/delete', 'Colleges::delete/$1', ['filter' => ['feature:crm_core', 'privilege:colleges.delete']]);

    $routes->get('settings', 'Settings::index', ['filter' => ['feature:crm_core', 'privilege:settings.view']]);
    $routes->get('settings/enquiry', 'Settings::enquiry', ['filter' => ['feature:crm_core', 'privilege:settings.view']]);
    $routes->get('settings/master-data', 'MasterData::index', ['filter' => ['feature:crm_core', 'privilege:settings.view']]);
    $routes->post('settings/master-data/(:segment)', 'MasterData::storeValue/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/master-data/platform-value/(:num)/toggle', 'MasterData::togglePlatformValue/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/master-data/tenant-value/(:num)/status', 'MasterData::updateTenantValueStatus/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/master-data/tenant-value/(:num)/delete', 'MasterData::deleteTenantValue/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/profile', 'Settings::updateProfile', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/preferences', 'Settings::updatePreferences', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/catalog/(:segment)', 'Settings::updateCatalogCategory/$1', ['filter' => ['feature:crm_core', 'privilege:settings.edit']]);
    $routes->post('settings/email', 'Settings::updateEmailConfig', ['filter' => ['feature:crm_core', 'privilege:settings.smtp']]);
    $routes->post('settings/whatsapp', 'Settings::updateWhatsappConfig', ['filter' => ['feature:crm_core', 'privilege:settings.whatsapp']]);
});

$routes->group('impersonation', ['filter' => ['auth']], static function (RouteCollection $routes): void {
    $routes->post('start/(:num)', 'Impersonation::start/$1');
    $routes->post('stop', 'Impersonation::stop');
    $routes->post('stop-all', 'Impersonation::stopAll');
});

$routes->group('enquiries', ['filter' => ['auth', 'tenant', 'suspension', 'feature:crm_core']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Enquiries::index', ['filter' => 'privilege:enquiries.view']);
    $routes->get('expired', 'Enquiries::expired', ['filter' => 'privilege:enquiries.view']);
    $routes->get('closed', 'Enquiries::closed', ['filter' => 'privilege:enquiries.view']);
    $routes->get('bulk-assign', 'Enquiries::bulkAssign', ['filter' => 'privilege:enquiries.bulk_assign']);
    $routes->post('bulk-assign', 'Enquiries::bulkAssignSubmit', ['filter' => 'privilege:enquiries.bulk_assign']);
    $routes->get('create', 'Enquiries::create', ['filter' => 'privilege:enquiries.create']);
    $routes->post('/', 'Enquiries::store', ['filter' => 'privilege:enquiries.create']);
    $routes->get('(:num)', 'Enquiries::show/$1', ['filter' => 'privilege:enquiries.view']);
    $routes->get('(:num)/edit', 'Enquiries::edit/$1', ['filter' => 'privilege:enquiries.edit']);
    $routes->post('(:num)', 'Enquiries::update/$1', ['filter' => 'privilege:enquiries.edit']);
    $routes->post('(:num)/contact', 'Enquiries::updateContactInfo/$1', ['filter' => 'privilege:enquiries.update_contact_info']);
    $routes->post('(:num)/college', 'Enquiries::updateCollegeInfo/$1', ['filter' => 'privilege:enquiries.update_college_info']);
    $routes->post('(:num)/followups', 'Enquiries::addFollowup/$1', ['filter' => 'privilege:followups.create']);
    $routes->get('(:num)/followups/(:num)/edit', 'Enquiries::editFollowup/$1/$2', ['filter' => 'privilege:followups.edit']);
    $routes->post('(:num)/followups/(:num)', 'Enquiries::updateFollowup/$1/$2', ['filter' => 'privilege:followups.edit']);
    $routes->post('(:num)/followups/(:num)/delete', 'Enquiries::deleteFollowup/$1/$2', ['filter' => 'privilege:followups.delete']);
    $routes->post('(:num)/close', 'Enquiries::close/$1', ['filter' => 'privilege:enquiries.close']);
    $routes->post('(:num)/reopen', 'Enquiries::reopen/$1', ['filter' => 'privilege:enquiries.reopen']);
    $routes->post('(:num)/assign', 'Enquiries::assign/$1', ['filter' => 'privilege:enquiries.reassign_in_edit,enquiries.expired_assign,enquiries.closed_assign']);
});

  $routes->group('admissions', ['filter' => ['auth', 'tenant', 'suspension', 'feature:admissions']], static function (RouteCollection $routes): void {
      $routes->get('/', 'Admissions::index', ['filter' => 'privilege:admissions.view,fees.view']);
      $routes->get('fee-structures', 'AdmissionFeeStructures::index', ['filter' => 'privilege:fees.view']);
      $routes->get('fee-structures/options', 'AdmissionFeeStructures::options', ['filter' => 'privilege:admissions.create']);
      $routes->post('fee-structures', 'AdmissionFeeStructures::store', ['filter' => 'privilege:fees.structure']);
      $routes->post('fee-structures/(:num)', 'AdmissionFeeStructures::update/$1', ['filter' => 'privilege:fees.structure']);
      $routes->post('fee-structures/(:num)/delete', 'AdmissionFeeStructures::delete/$1', ['filter' => 'privilege:fees.structure']);
      $routes->get('create', 'Admissions::create', ['filter' => 'privilege:admissions.create']);
      $routes->post('/', 'Admissions::store', ['filter' => 'privilege:admissions.create']);
      $routes->get('(:num)', 'Admissions::show/$1', ['filter' => 'privilege:admissions.view,fees.view']);
      $routes->post('(:num)/payments', 'Admissions::collectPayment/$1', ['filter' => 'privilege:fees.create']);
      $routes->post('(:num)/followups', 'Admissions::addFollowup/$1', ['filter' => 'privilege:admissions.edit']);
      $routes->post('(:num)/followups/(:num)', 'Admissions::updateFollowup/$1/$2', ['filter' => 'privilege:admissions.edit']);
      $routes->post('(:num)/followups/(:num)/delete', 'Admissions::deleteFollowup/$1/$2', ['filter' => 'privilege:admissions.edit']);
      $routes->post('(:num)/batch', 'Admissions::assignBatch/$1', ['filter' => 'privilege:admissions.edit']);
      $routes->post('(:num)/hold', 'Admissions::hold/$1', ['filter' => 'privilege:admissions.edit']);
      $routes->post('(:num)/cancel', 'Admissions::cancel/$1', ['filter' => 'privilege:admissions.cancel']);
  });
  
  $routes->group('batches', ['filter' => ['auth', 'tenant', 'suspension', 'feature:batch_management']], static function (RouteCollection $routes): void {
      $routes->get('/', 'Batches::index', ['filter' => 'privilege:batches.view']);
      $routes->post('/', 'Batches::store', ['filter' => 'privilege:batches.create']);
      $routes->post('(:num)', 'Batches::update/$1', ['filter' => 'privilege:batches.edit']);
      $routes->post('(:num)/delete', 'Batches::delete/$1', ['filter' => 'privilege:batches.delete']);
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
    $routes->post('master-data/initialize', 'PlatformMasterData::initializeDefaults');
    $routes->post('master-data/types', 'PlatformMasterData::storeType');
    $routes->post('master-data/types/(:num)/status', 'PlatformMasterData::updateTypeStatus/$1');
    $routes->post('master-data/values', 'PlatformMasterData::storeValue');
    $routes->post('master-data/values/(:num)/status', 'PlatformMasterData::updateValueStatus/$1');
});
