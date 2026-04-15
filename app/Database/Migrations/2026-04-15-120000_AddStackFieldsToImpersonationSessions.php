<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStackFieldsToImpersonationSessions extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('parent_session_id', 'impersonation_sessions')) {
            $this->forge->addColumn('impersonation_sessions', [
                'parent_session_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'target_user_id',
                ],
            ]);
        }

        if (! $this->db->fieldExists('root_actor_user_id', 'impersonation_sessions')) {
            $this->forge->addColumn('impersonation_sessions', [
                'root_actor_user_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'parent_session_id',
                ],
            ]);
        }

        if (! $this->db->fieldExists('depth', 'impersonation_sessions')) {
            $this->forge->addColumn('impersonation_sessions', [
                'depth' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 1,
                    'after'      => 'root_actor_user_id',
                ],
            ]);
        }

        $this->db->query('UPDATE impersonation_sessions SET root_actor_user_id = actor_user_id WHERE root_actor_user_id IS NULL');
        $this->db->query('UPDATE impersonation_sessions SET depth = 1 WHERE depth IS NULL OR depth = 0');
    }

    public function down()
    {
        if ($this->db->fieldExists('depth', 'impersonation_sessions')) {
            $this->forge->dropColumn('impersonation_sessions', 'depth');
        }

        if ($this->db->fieldExists('root_actor_user_id', 'impersonation_sessions')) {
            $this->forge->dropColumn('impersonation_sessions', 'root_actor_user_id');
        }

        if ($this->db->fieldExists('parent_session_id', 'impersonation_sessions')) {
            $this->forge->dropColumn('impersonation_sessions', 'parent_session_id');
        }
    }
}
