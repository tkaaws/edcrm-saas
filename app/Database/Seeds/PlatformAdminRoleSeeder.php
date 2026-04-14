<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Normalises demo identities for existing installations.
 *
 * - Ensures a global platform_admin role exists (tenant_id = NULL)
 * - Creates or updates platform@edcrm.in as a tenantless platform admin
 * - Restores demo tenant owner identity as owner@demo.edcrm.in under demo tenant
 */
class PlatformAdminRoleSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $tenant = $this->db->table('tenants')->where('slug', 'demo-institute')->get()->getRow();

        if (! $tenant) {
            echo "PlatformAdminRoleSeeder: demo-institute tenant not found - skipping.\n";
            return;
        }

        $tenantId = (int) $tenant->id;
        echo "PlatformAdminRoleSeeder: found tenant id={$tenantId}\n";

        $existingRole = $this->db->table('user_roles')
            ->where('tenant_id', null)
            ->where('code', 'platform_admin')
            ->get()
            ->getRow();

        if ($existingRole) {
            $platformAdminRoleId = (int) $existingRole->id;
            echo "PlatformAdminRoleSeeder: global platform_admin role already exists id={$platformAdminRoleId}\n";
        } else {
            $this->db->table('user_roles')->insert([
                'tenant_id'  => null,
                'name'       => 'Platform Admin',
                'code'       => 'platform_admin',
                'is_system'  => 1,
                'status'     => 'active',
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $platformAdminRoleId = (int) $this->db->insertID();
            echo "PlatformAdminRoleSeeder: created global platform_admin role id={$platformAdminRoleId}\n";
        }

        $platformUser = $this->db->table('users')
            ->where('email', 'platform@edcrm.in')
            ->get()
            ->getRow();

        if ($platformUser) {
            $this->db->table('users')
                ->where('id', $platformUser->id)
                ->update([
                    'tenant_id'   => null,
                    'role_id'     => $platformAdminRoleId,
                    'username'    => 'platform_admin',
                    'first_name'  => 'Platform',
                    'last_name'   => 'Admin',
                    'department'  => 'Platform',
                    'designation' => 'Platform Admin',
                    'is_active'   => 1,
                    'updated_at'  => $now,
                ]);
            echo "PlatformAdminRoleSeeder: normalized existing platform@edcrm.in user.\n";
        } else {
            $legacyDemoUser = $this->db->table('users')
                ->where('email', 'demo@edcrm.in')
                ->get()
                ->getRow();

            if ($legacyDemoUser) {
                $this->db->table('users')
                    ->where('id', $legacyDemoUser->id)
                    ->update([
                        'tenant_id'   => null,
                        'role_id'     => $platformAdminRoleId,
                        'username'    => 'platform_admin',
                        'email'       => 'platform@edcrm.in',
                        'first_name'  => 'Platform',
                        'last_name'   => 'Admin',
                        'department'  => 'Platform',
                        'designation' => 'Platform Admin',
                        'is_active'   => 1,
                        'updated_at'  => $now,
                    ]);
                echo "PlatformAdminRoleSeeder: converted legacy demo@edcrm.in into platform admin.\n";
            } else {
                $this->db->table('users')->insert([
                    'tenant_id'           => null,
                    'role_id'             => $platformAdminRoleId,
                    'employee_code'       => 'EMP-001',
                    'username'            => 'platform_admin',
                    'email'               => 'platform@edcrm.in',
                    'first_name'          => 'Platform',
                    'last_name'           => 'Admin',
                    'mobile_number'       => '+910000000000',
                    'whatsapp_number'     => null,
                    'department'          => 'Platform',
                    'designation'         => 'Platform Admin',
                    'password_hash'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
                    'is_active'           => 1,
                    'must_reset_password' => 0,
                    'last_login_at'       => null,
                    'last_login_ip'       => null,
                    'created_by'          => null,
                    'updated_by'          => null,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
                echo "PlatformAdminRoleSeeder: created platform@edcrm.in user.\n";
            }
        }

        $tenantOwnerRole = $this->db->table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', 'tenant_owner')
            ->get()
            ->getRow();

        if ($tenantOwnerRole) {
            $tenantOwner = $this->db->table('users')
                ->where('tenant_id', $tenantId)
                ->where('email', 'owner@demo.edcrm.in')
                ->get()
                ->getRow();

            if (! $tenantOwner) {
                $this->db->table('users')->insert([
                    'tenant_id'           => $tenantId,
                    'role_id'             => (int) $tenantOwnerRole->id,
                    'employee_code'       => 'OWN-001',
                    'username'            => 'demo_owner',
                    'email'               => 'owner@demo.edcrm.in',
                    'first_name'          => 'Demo',
                    'last_name'           => 'Owner',
                    'mobile_number'       => '+910000000001',
                    'whatsapp_number'     => null,
                    'department'          => 'Management',
                    'designation'         => 'Tenant Owner',
                    'password_hash'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
                    'is_active'           => 1,
                    'must_reset_password' => 0,
                    'last_login_at'       => null,
                    'last_login_ip'       => null,
                    'created_by'          => null,
                    'updated_by'          => null,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
                echo "PlatformAdminRoleSeeder: created owner@demo.edcrm.in tenant owner.\n";
            }
        }

        echo "---\n";
        echo "Platform admin login  ->  platform@edcrm.in  /  Demo@1234\n";
        echo "Tenant owner login    ->  owner@demo.edcrm.in  /  Demo@1234\n";
    }
}
