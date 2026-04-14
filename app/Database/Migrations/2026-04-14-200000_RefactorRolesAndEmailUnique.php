<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * RefactorRolesAndEmailUnique
 *
 * 1. Rename tenant_roles → user_roles
 *    MySQL automatically updates all FK constraints that reference the table.
 *
 * 2. Drop the composite unique index (tenant_id, email) on users.
 *    Add a platform-wide unique index on (email) so login can be
 *    done by email alone without a tenant slug.
 */
class RefactorRolesAndEmailUnique extends Migration
{
    public function up()
    {
        // ----------------------------------------------------------------
        // 1. Rename tenant_roles → user_roles
        //    MySQL updates FK references automatically on RENAME TABLE.
        // ----------------------------------------------------------------
        $this->db->query('RENAME TABLE tenant_roles TO user_roles');

        // ----------------------------------------------------------------
        // 2. Make users.email globally unique
        //    Drop the old composite unique key (tenant_id + email),
        //    add a simple unique key on email alone.
        // ----------------------------------------------------------------

        // Drop old composite unique keys (CI4 forge names them automatically)
        // Try both possible names the forge may have generated
        foreach (['users_tenant_id_email_unique', 'users_tenant_id_username_unique'] as $idx) {
            try {
                $this->db->query("ALTER TABLE users DROP INDEX `{$idx}`");
            } catch (\Throwable $e) {
                // Index may have a different name — skip silently, handled below
            }
        }

        // Drop by column pattern as fallback (show index, find and drop)
        $indexes = $this->db->query("SHOW INDEX FROM users WHERE Column_name = 'email' AND Non_unique = 0")->getResultArray();
        foreach ($indexes as $idx) {
            if ($idx['Key_name'] !== 'PRIMARY') {
                $this->db->query("ALTER TABLE users DROP INDEX `{$idx['Key_name']}`");
            }
        }

        // Add platform-wide unique constraint on email
        $this->db->query('ALTER TABLE users ADD UNIQUE KEY `users_email_unique` (`email`)');
    }

    public function down()
    {
        // Restore unique key structure
        try { $this->db->query('ALTER TABLE users DROP INDEX `users_email_unique`'); } catch (\Throwable $e) {}
        $this->db->query('ALTER TABLE users ADD UNIQUE KEY `users_tenant_id_email_unique` (`tenant_id`, `email`)');

        // Rename back
        $this->db->query('RENAME TABLE user_roles TO tenant_roles');
    }
}
