<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MasterDataCatalogSeeder extends Seeder
{
    public function run()
    {
        $this->call('MasterDataTypesSeeder');
        $this->call('MasterDataValuesSeeder');
    }
}
