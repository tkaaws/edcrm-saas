<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * DatabaseSeeder
 *
 * Master seeder — runs all seeders in the correct order.
 * Run with: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('PrivilegesSeeder');
        $this->call('DemoDataSeeder');
        $this->call('BillingCatalogSeeder');
    }
}
