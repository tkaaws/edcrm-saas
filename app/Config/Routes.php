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
});
