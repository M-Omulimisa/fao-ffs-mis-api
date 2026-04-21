<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add VSLA Onboarding Fields to Users Table
 * 
 * Purpose: Support the VSLA group onboarding process with role tracking
 * and onboarding progress management.
 * 
 * New Fields:
 * - is_group_admin: Tracks if user is a VSLA group administrator
 * - is_group_secretary: Tracks if user is a VSLA group secretary
 * - is_group_treasurer: Tracks if user is a VSLA group treasurer  
 * - onboarding_step: Tracks current onboarding progress
 * - onboarding_completed_at: Timestamp when onboarding was completed
 * - last_onboarding_step_at: Last time user progressed in onboarding
 */
class AddVslaOnboardingFieldsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // VSLA Role Management Fields
                if (!Schema::hasColumn('users', 'is_group_admin')) {
                    $table->enum('is_group_admin', ['Yes', 'No'])
                        ->default('No')
                        ->comment('Is this user a VSLA group administrator?');
                }

                if (!Schema::hasColumn('users', 'is_group_secretary')) {
                    $table->enum('is_group_secretary', ['Yes', 'No'])
                        ->default('No')
                        ->comment('Is this user a VSLA group secretary?');
                }

                if (!Schema::hasColumn('users', 'is_group_treasurer')) {
                    $table->enum('is_group_treasurer', ['Yes', 'No'])
                        ->default('No')
                        ->comment('Is this user a VSLA group treasurer?');
                }

                // Onboarding Progress Tracking
                if (!Schema::hasColumn('users', 'onboarding_step')) {
                    $table->enum('onboarding_step', [
                        'not_started',      // User hasn't started onboarding
                        'step_1_welcome',   // Completed welcome screen
                        'step_2_terms',     // Accepted terms and privacy policy
                        'step_3_registration', // User account created
                        'step_4_group',     // VSLA group created
                        'step_5_members',   // Secretary and treasurer registered
                        'step_6_cycle',     // Savings cycle configured
                        'step_7_complete'   // Onboarding completed
                    ])
                        ->default('not_started')
                        ->comment('Current step in onboarding process');
                }

                if (!Schema::hasColumn('users', 'onboarding_completed_at')) {
                    $table->timestamp('onboarding_completed_at')
                        ->nullable()
                        ->comment('When user completed onboarding');
                }

                if (!Schema::hasColumn('users', 'last_onboarding_step_at')) {
                    $table->timestamp('last_onboarding_step_at')
                        ->nullable()
                        ->comment('Last time user progressed in onboarding');
                }

                // Add indexes for performance
                if (Schema::hasColumn('users', 'is_group_admin')) {
                    $table->index('is_group_admin');
                }
                if (Schema::hasColumn('users', 'onboarding_step')) {
                    $table->index('onboarding_step');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_group_admin')) {
                $table->dropColumn('is_group_admin');
            }
            if (Schema::hasColumn('users', 'is_group_secretary')) {
                $table->dropColumn('is_group_secretary');
            }
            if (Schema::hasColumn('users', 'is_group_treasurer')) {
                $table->dropColumn('is_group_treasurer');
            }
            if (Schema::hasColumn('users', 'onboarding_step')) {
                $table->dropColumn('onboarding_step');
            }
            if (Schema::hasColumn('users', 'onboarding_completed_at')) {
                $table->dropColumn('onboarding_completed_at');
            }
            if (Schema::hasColumn('users', 'last_onboarding_step_at')) {
                $table->dropColumn('last_onboarding_step_at');
            }
        });
    }
}
