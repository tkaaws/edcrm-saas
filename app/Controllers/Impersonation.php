<?php

namespace App\Controllers;

class Impersonation extends BaseController
{
    public function start(int $targetUserId)
    {
        try {
            service('impersonation')->start(
                $targetUserId,
                trim((string) $this->request->getPost('reason')) ?: null
            );
        } catch (\Throwable $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->to('/dashboard')->with('message', 'Impersonation started successfully.');
    }

    public function stop()
    {
        try {
            service('impersonation')->stop();
        } catch (\Throwable $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        $roleCode = (string) session()->get('user_role_code');
        $redirect = $roleCode === 'platform_admin' ? '/platform/tenants' : '/dashboard';

        return redirect()->to($redirect)->with('message', 'Returned to your original account.');
    }
}
