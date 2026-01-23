<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLoanRepaymentsToVslaMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vsla_meetings', function (Blueprint $table) {
            // Add total_loans_repaid after total_loans_disbursed
            $table->decimal('total_loans_repaid', 15, 2)->default(0)->after('total_loans_disbursed');
            
            // Add loan_repayments_data JSON field after loans_data
            $table->json('loan_repayments_data')->nullable()->after('loans_data');
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
            $table->dropColumn(['total_loans_repaid', 'loan_repayments_data']);
        });
    }
}
