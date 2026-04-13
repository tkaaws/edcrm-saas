<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PrivilegesSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $privileges = [

            // -------------------------------------------------------
            // MODULE: users
            // -------------------------------------------------------
            ['code' => 'users.view',   'name' => 'View Users',   'module' => 'users'],
            ['code' => 'users.create', 'name' => 'Create Users', 'module' => 'users'],
            ['code' => 'users.edit',   'name' => 'Edit Users',   'module' => 'users'],
            ['code' => 'users.delete', 'name' => 'Delete Users', 'module' => 'users'],

            // -------------------------------------------------------
            // MODULE: branches
            // -------------------------------------------------------
            ['code' => 'branches.view',   'name' => 'View Branches',   'module' => 'branches'],
            ['code' => 'branches.create', 'name' => 'Create Branches', 'module' => 'branches'],
            ['code' => 'branches.edit',   'name' => 'Edit Branches',   'module' => 'branches'],
            ['code' => 'branches.delete', 'name' => 'Delete Branches', 'module' => 'branches'],

            // -------------------------------------------------------
            // MODULE: roles
            // -------------------------------------------------------
            ['code' => 'roles.view',   'name' => 'View Roles',   'module' => 'roles'],
            ['code' => 'roles.create', 'name' => 'Create Roles', 'module' => 'roles'],
            ['code' => 'roles.edit',   'name' => 'Edit Roles',   'module' => 'roles'],
            ['code' => 'roles.delete', 'name' => 'Delete Roles', 'module' => 'roles'],

            // -------------------------------------------------------
            // MODULE: settings
            // -------------------------------------------------------
            ['code' => 'settings.view',        'name' => 'View Settings',         'module' => 'settings'],
            ['code' => 'settings.edit',        'name' => 'Edit Settings',         'module' => 'settings'],
            ['code' => 'settings.smtp',        'name' => 'Manage SMTP Config',    'module' => 'settings'],
            ['code' => 'settings.whatsapp',    'name' => 'Manage WhatsApp Config','module' => 'settings'],

            // -------------------------------------------------------
            // MODULE: enquiries
            // -------------------------------------------------------
            ['code' => 'enquiries.view',        'name' => 'View Enquiries',         'module' => 'enquiries'],
            ['code' => 'enquiries.create',      'name' => 'Create Enquiries',       'module' => 'enquiries'],
            ['code' => 'enquiries.edit',        'name' => 'Edit Enquiries',         'module' => 'enquiries'],
            ['code' => 'enquiries.delete',      'name' => 'Delete Enquiries',       'module' => 'enquiries'],
            ['code' => 'enquiries.assign',      'name' => 'Assign Enquiries',       'module' => 'enquiries'],
            ['code' => 'enquiries.bulk_assign', 'name' => 'Bulk Assign Enquiries',  'module' => 'enquiries'],
            ['code' => 'enquiries.export',      'name' => 'Export Enquiries',       'module' => 'enquiries'],

            // -------------------------------------------------------
            // MODULE: followups
            // -------------------------------------------------------
            ['code' => 'followups.view',   'name' => 'View Followups',   'module' => 'followups'],
            ['code' => 'followups.create', 'name' => 'Create Followups', 'module' => 'followups'],
            ['code' => 'followups.edit',   'name' => 'Edit Followups',   'module' => 'followups'],

            // -------------------------------------------------------
            // MODULE: admissions
            // -------------------------------------------------------
            ['code' => 'admissions.view',    'name' => 'View Admissions',    'module' => 'admissions'],
            ['code' => 'admissions.create',  'name' => 'Create Admissions',  'module' => 'admissions'],
            ['code' => 'admissions.edit',    'name' => 'Edit Admissions',    'module' => 'admissions'],
            ['code' => 'admissions.delete',  'name' => 'Delete Admissions',  'module' => 'admissions'],
            ['code' => 'admissions.approve', 'name' => 'Approve Admissions', 'module' => 'admissions'],
            ['code' => 'admissions.cancel',  'name' => 'Cancel Admissions',  'module' => 'admissions'],

            // -------------------------------------------------------
            // MODULE: fees
            // -------------------------------------------------------
            ['code' => 'fees.view',           'name' => 'View Fees',            'module' => 'fees'],
            ['code' => 'fees.create',         'name' => 'Create Fee Records',   'module' => 'fees'],
            ['code' => 'fees.edit',           'name' => 'Edit Fee Records',     'module' => 'fees'],
            ['code' => 'fees.delete',         'name' => 'Delete Fee Records',   'module' => 'fees'],
            ['code' => 'fees.receipts',       'name' => 'View/Print Receipts',  'module' => 'fees'],
            ['code' => 'fees.discount',       'name' => 'Apply Discounts',      'module' => 'fees'],
            ['code' => 'fees.structure',      'name' => 'Manage Fee Structure', 'module' => 'fees'],

            // -------------------------------------------------------
            // MODULE: tickets
            // -------------------------------------------------------
            ['code' => 'tickets.view',   'name' => 'View Tickets',   'module' => 'tickets'],
            ['code' => 'tickets.create', 'name' => 'Create Tickets', 'module' => 'tickets'],
            ['code' => 'tickets.edit',   'name' => 'Edit Tickets',   'module' => 'tickets'],
            ['code' => 'tickets.close',  'name' => 'Close Tickets',  'module' => 'tickets'],
            ['code' => 'tickets.delete', 'name' => 'Delete Tickets', 'module' => 'tickets'],

            // -------------------------------------------------------
            // MODULE: placement
            // -------------------------------------------------------
            ['code' => 'placement.view',       'name' => 'View Placement',          'module' => 'placement'],
            ['code' => 'placement.manage',     'name' => 'Manage Placement',        'module' => 'placement'],
            ['code' => 'placement.jobs',       'name' => 'Manage Jobs',             'module' => 'placement'],
            ['code' => 'placement.interviews', 'name' => 'Manage Interview Calls',  'module' => 'placement'],
            ['code' => 'placement.mock',       'name' => 'Manage Mock Interviews',  'module' => 'placement'],
            ['code' => 'placement.college',    'name' => 'Manage College Connect',  'module' => 'placement'],

            // -------------------------------------------------------
            // MODULE: batches
            // -------------------------------------------------------
            ['code' => 'batches.view',   'name' => 'View Batches',   'module' => 'batches'],
            ['code' => 'batches.create', 'name' => 'Create Batches', 'module' => 'batches'],
            ['code' => 'batches.edit',   'name' => 'Edit Batches',   'module' => 'batches'],
            ['code' => 'batches.delete', 'name' => 'Delete Batches', 'module' => 'batches'],

            // -------------------------------------------------------
            // MODULE: students
            // -------------------------------------------------------
            ['code' => 'students.view',       'name' => 'View Students',       'module' => 'students'],
            ['code' => 'students.edit',       'name' => 'Edit Students',       'module' => 'students'],
            ['code' => 'students.attendance', 'name' => 'Manage Attendance',   'module' => 'students'],
            ['code' => 'students.export',     'name' => 'Export Students',     'module' => 'students'],

            // -------------------------------------------------------
            // MODULE: reports
            // -------------------------------------------------------
            ['code' => 'reports.view',     'name' => 'View Basic Reports',    'module' => 'reports'],
            ['code' => 'reports.advanced', 'name' => 'View Advanced Reports', 'module' => 'reports'],
            ['code' => 'reports.export',   'name' => 'Export Reports',        'module' => 'reports'],

            // -------------------------------------------------------
            // MODULE: whatsapp
            // -------------------------------------------------------
            ['code' => 'whatsapp.view',   'name' => 'View WhatsApp Logs', 'module' => 'whatsapp'],
            ['code' => 'whatsapp.send',   'name' => 'Send WhatsApp',      'module' => 'whatsapp'],

            // -------------------------------------------------------
            // MODULE: billing (tenant owner only)
            // -------------------------------------------------------
            ['code' => 'billing.view',   'name' => 'View Billing & Subscription', 'module' => 'billing'],
            ['code' => 'billing.manage', 'name' => 'Manage Billing & Payments',   'module' => 'billing'],

            // -------------------------------------------------------
            // MODULE: audit
            // -------------------------------------------------------
            ['code' => 'audit.view', 'name' => 'View Audit Logs', 'module' => 'audit'],
        ];

        foreach ($privileges as &$p) {
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }

        // Insert in chunks — skip if already seeded
        $existing = $this->db->table('privileges')->countAllResults();
        if ($existing > 0) {
            echo "PrivilegesSeeder: already seeded, skipping.\n";
            return;
        }

        $this->db->table('privileges')->insertBatch($privileges);
        echo "PrivilegesSeeder: inserted " . count($privileges) . " privileges.\n";
    }
}
