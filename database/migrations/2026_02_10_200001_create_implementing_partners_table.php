<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create implementing_partners table — the backbone for multi-tenancy.
 * Every user, group, training session, etc. will belong to an IP.
 */
class CreateImplementingPartnersTable extends Migration
{
    public function up()
    {
        Schema::create('implementing_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);                           // e.g. "KADP", "ECO", "GARD"
            $table->string('short_name', 50)->nullable();           // Abbreviated code
            $table->string('slug', 100)->unique()->nullable();      // URL-safe slug
            $table->text('description')->nullable();
            $table->string('logo', 255)->nullable();                // Path to logo

            // Agreement & project info
            $table->string('loa', 150)->nullable();                 // Letter of Agreement
            $table->string('project_code', 100)->nullable();        // e.g. UNJP/UGA/068/EC

            // Contact info
            $table->string('contact_person', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('address')->nullable();

            // Coverage
            $table->string('region', 100)->nullable();              // e.g. Karamoja
            $table->json('districts')->nullable();                  // JSON array of district IDs/names

            // Status
            $table->string('status', 20)->default('active');        // active, inactive, suspended
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('short_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('implementing_partners');
    }
}
