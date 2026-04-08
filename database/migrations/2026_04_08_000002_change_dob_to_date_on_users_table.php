<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Change dob column from TIMESTAMP to DATE.
 * TIMESTAMP cannot store dates before 1970-01-01; DATE handles 1000-01-01 to 9999-12-31.
 */
class ChangeDobToDateOnUsersTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `users` MODIFY `dob` DATE NULL DEFAULT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `users` MODIFY `dob` TIMESTAMP NULL DEFAULT NULL");
    }
}
