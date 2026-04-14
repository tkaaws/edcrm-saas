<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PipelineValidation extends Migration
{
    public function up()
    {
        // No-op migration used to verify the deploy pipeline runs migrations safely.
    }

    public function down()
    {
        // No-op rollback.
    }
}
