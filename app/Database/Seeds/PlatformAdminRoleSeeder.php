<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * PlatformAdminRoleSeeder
 *
 * One-time migration for existing installations.
 *
 * - Finds the demo-institute tenant (slug = 'demo-institute')
 * - Adds the 'platform_admin' system role to that tenant (if not already present)
 * - Updates demo@edcrm.in to use the platform_admin role
 *
 * Safe to re-run — all operations are idempotent.
 */
class PlatformAdminRoleSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ----------------------------------------------------------------
        // 1. Resolve the demo-institute tenant
        // ----------------------------------------------------------------
        $tenant = $this->db->table('tenants')->where('slug', 'demo-institute')->get()->getRow();

        if (! $tenant) {
            echo "PlatformAdminRoleSeeder: demo-institute tenant not found — skipping.\n";
            return;
        }

        $tenantId = (int) $tenant->id;
        echo "PlatformAdminRoleSeeder: found tenant id={$tenantId}\n";

        // ----------------------------------------------------------------
        // 2. Create platform_admin role if it does not exist
        // ----------------------------------------------------------------
        $existingRole = $this->db->table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', 'platform_admin')
            ->get()->getRow();

        if ($existingRole) {
            $platformAdminRoleId = (int) $existingRole->id;
            echo "PlatformAdminRoleSeeder: platform_admin role already exists id={$platformAdminRoleId}\n";
        } else {
            $this->db->table('user_roles')->insert([
                'tenant_id'  => $tenantId,
                'name'       => 'Platform Admin',
                'code'       => 'platform_admin',
                'is_system'  => 1,
                'status'     => 'active',
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $platformAdminRoleId = $this->db->insertID();
            echo "PlatformAdminRoleSeeder: created platform_admin role id={$platformAdminRoleId}\n";
        }

        // ----------------------------------------------------------------
        // 3. Update demo@edcrm.in to the platform_admin role
        // ----------------------------------------------------------------
        $user = $this->db->table('users')
            ->where('tenant_id', $tenantId)
            ->where('email', 'demo@edcrm.in')
            ->get()->getRow();

        if (! $user) {
            echo "PlatformAdminRoleSeeder: demo@edcrm.in not found under tenant {$tenantId} — skipping.\n";
            return;
        }

        if ((int) $user->role_id === $platformAdminRoleId) {
            echo "PlatformAdminRoleSeeder: demo@edcrm.in already has platform_admin role — nothing to do.\n";
            return;
        }

        $this->db->table('users')
            ->where('id', $user->id)
            ->update([
                'role_id'    => $platformAdminRoleId,
                'updated_at' => $now,
            ]);

        echo "PlatformAdminRoleSeeder: updated demo@edcrm.in to platform_admin role.\n";
        echo "---\n";
        echo "Platform admin login  →  demo@edcrm.in  /  Demo@1234\n";
    }
}
