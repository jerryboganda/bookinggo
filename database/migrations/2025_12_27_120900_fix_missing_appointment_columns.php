<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('appointments', 'attachment')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('attachment')->nullable()->after('appointment_status');
            });
        }
        
        if (!Schema::hasColumn('appointments', 'custom_field')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->longText('custom_field')->nullable()->after('attachment');
            });
        }
        
        if (!Schema::hasColumn('appointments', 'name')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('name')->nullable()->after('staff_id');
            });
        }
        
        if (!Schema::hasColumn('appointments', 'email')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('email')->nullable()->after('name');
            });
        }
        
        if (!Schema::hasColumn('appointments', 'contact')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('contact')->nullable()->after('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'attachment')) {
                $table->dropColumn('attachment');
            }
            if (Schema::hasColumn('appointments', 'custom_field')) {
                $table->dropColumn('custom_field');
            }
            if (Schema::hasColumn('appointments', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('appointments', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('appointments', 'contact')) {
                $table->dropColumn('contact');
            }
        });
    }
};
