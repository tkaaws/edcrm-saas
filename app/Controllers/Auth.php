<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TenantModel;
use App\Services\AuthService;

/**
 * Auth Controller
 *
 * Handles: login, logout, forgot password, reset password,
 *          forced password change (must_reset_password).
 */
class Auth extends BaseController
{
    protected AuthService $auth;
    protected TenantModel $tenantModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->auth        = service('auth');
        $this->tenantModel = new TenantModel();
    }

    // ------------------------------------------------------------------
    // LOGIN
    // ------------------------------------------------------------------

    public function login(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login', [
            'title' => 'Login',
        ]);
    }

    public function loginPost(): \CodeIgniter\HTTP\RedirectResponse
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Resolve tenant from domain/slug — v1: single tenant via config
        $tenantId = $this->resolveTenantId();

        if (! $tenantId) {
            return redirect()->back()->withInput()->with('error', 'Invalid tenant. Please contact support.');
        }

        $result = $this->auth->attempt($tenantId, $email, $password);

        return match ($result) {
            AuthService::LOGIN_SUCCESS      => redirect()->to('/dashboard'),
            AuthService::LOGIN_MUST_RESET   => redirect()->to('/auth/change-password'),
            AuthService::LOGIN_INACTIVE     => redirect()->back()->withInput()
                                                ->with('error', 'Your account is inactive. Contact your administrator.'),
            AuthService::LOGIN_INVALID      => redirect()->back()->withInput()
                                                ->with('error', 'Invalid email or password.'),
            default                         => redirect()->back()->withInput()
                                                ->with('error', 'Login failed. Please try again.'),
        };
    }

    // ------------------------------------------------------------------
    // LOGOUT
    // ------------------------------------------------------------------

    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        $this->auth->logout();
        return redirect()->to('/auth/login')->with('message', 'You have been logged out.');
    }

    // ------------------------------------------------------------------
    // FORGOT PASSWORD
    // ------------------------------------------------------------------

    public function forgotPassword(): string
    {
        return view('auth/forgot_password', ['title' => 'Forgot Password']);
    }

    public function forgotPasswordPost(): \CodeIgniter\HTTP\RedirectResponse
    {
        $rules = ['email' => 'required|valid_email'];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $tenantId = $this->resolveTenantId();
        $email    = $this->request->getPost('email');

        // Always true — prevents user enumeration
        $this->auth->forgotPassword((int) $tenantId, $email);

        return redirect()->to('/auth/forgot-password')
                         ->with('message', 'If that email exists, a reset link has been sent.');
    }

    // ------------------------------------------------------------------
    // RESET PASSWORD
    // ------------------------------------------------------------------

    public function resetPassword(string $token = ''): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (empty($token)) {
            return redirect()->to('/auth/login')->with('error', 'Invalid reset link.');
        }

        $user = $this->auth->validateResetToken($token);

        if (! $user) {
            return redirect()->to('/auth/forgot-password')
                             ->with('error', 'This reset link has expired or already been used. Please request a new one.');
        }

        return view('auth/reset_password', [
            'title' => 'Reset Password',
            'token' => $token,
        ]);
    }

    public function resetPasswordPost(): \CodeIgniter\HTTP\RedirectResponse
    {
        $rules = [
            'token'            => 'required',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $token    = $this->request->getPost('token');
        $password = $this->request->getPost('password');

        $result = $this->auth->resetPassword($token, $password);

        if ($result === true) {
            return redirect()->to('/auth/login')
                             ->with('message', 'Password reset successful. Please log in.');
        }

        $errorMessages = [
            'invalid_token'   => 'This reset link has expired or already been used.',
            'password_reused' => 'You cannot reuse a recent password. Please choose a different one.',
        ];

        return redirect()->back()->withInput()
                         ->with('error', $errorMessages[$result] ?? 'Password reset failed.');
    }

    // ------------------------------------------------------------------
    // FORCED PASSWORD CHANGE (must_reset_password)
    // ------------------------------------------------------------------

    public function changePassword(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! session()->get('user_id')) {
            return redirect()->to('/auth/login');
        }

        return view('auth/change_password', ['title' => 'Change Password']);
    }

    public function changePasswordPost(): \CodeIgniter\HTTP\RedirectResponse
    {
        $rules = [
            'current_password' => 'required',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId          = session()->get('user_id');
        $currentPassword = $this->request->getPost('current_password');
        $newPassword     = $this->request->getPost('password');

        $result = $this->auth->changePassword($userId, $currentPassword, $newPassword);

        if ($result === true) {
            return redirect()->to('/dashboard')->with('message', 'Password changed successfully.');
        }

        $errorMessages = [
            'wrong_current_password' => 'Current password is incorrect.',
            'password_reused'        => 'You cannot reuse a recent password.',
            'user_not_found'         => 'User not found.',
        ];

        return redirect()->back()->withInput()
                         ->with('error', $errorMessages[$result] ?? 'Password change failed.');
    }

    // ------------------------------------------------------------------
    // HELPER
    // ------------------------------------------------------------------

    /**
     * Resolve tenant ID for login.
     * v1: reads from APP_TENANT_ID in .env (single-tenant deployment).
     * Future: resolve from subdomain or slug in URL.
     */
    protected function resolveTenantId(): ?int
    {
        $tenantId = env('APP_TENANT_ID');

        if ($tenantId) {
            return (int) $tenantId;
        }

        // Fallback: first active tenant (dev/demo mode only)
        $tenant = $this->tenantModel->where('status', 'active')->first();
        return $tenant ? (int) $tenant->id : null;
    }
}
