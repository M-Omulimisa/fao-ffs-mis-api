<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialFundToVslaMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vsla_meetings', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('vsla_meetings', 'social_fund_contributions_data')) {
                $table->json('social_fund_contributions_data')->nullable()->after('loan_repayments_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vsla_meetings', function (Blueprint $table) {
            if (Schema::hasColumn('vsla_meetings', 'social_fund_contributions_data')) {
                $table->dropColumn('social_fund_contributions_data');
            }
        });
    }
}
