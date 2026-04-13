<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter
 *
 * Blocks unauthenticated access to protected routes.
 * Redirects to login if no valid session.
 * Enforces must_reset_password before any other page.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        if (! session()->get('user_id')) {
            return redirect()->to('/auth/login')->with('error', 'Please log in to continue.');
        }

        // Force password change before any other action
        if (session()->get('must_reset_password')) {
            $currentPath = '/' . ltrim($request->getUri()->getPath(), '/');
            if ($currentPath !== '/auth/change-password') {
                return redirect()->to('/auth/change-password')
                                 ->with('message', 'You must change your password before continuing.');
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
