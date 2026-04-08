<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make meeting_frequency nullable with default 'Weekly' on ffs_groups.
 * Prevents "cannot be null" errors when the API/frontend omits the field.
 */
class MakeMeetingFrequencyNullableOnFfsGroups extends Migration
{
    public function up()
    {
        // Change enum to nullable string with default (enum ALTER is problematic in MySQL)
        DB::statement("ALTER TABLE `ffs_groups` MODIFY `meeting_frequency` VARCHAR(20) DEFAULT 'Weekly'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `ffs_groups` MODIFY `meeting_frequency` ENUM('Weekly','Bi-weekly','Monthly') DEFAULT 'Weekly'");
    }
}
