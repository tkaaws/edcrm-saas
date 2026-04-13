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
    $routes->get('platform/tenants', 'PlatformTenants::index');
    $routes->get('platform/tenants/create', 'PlatformTenants::create');
    $routes->post('platform/tenants', 'PlatformTenants::store');
});
