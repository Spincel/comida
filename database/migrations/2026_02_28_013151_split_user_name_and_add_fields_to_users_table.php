<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('second_last_name')->nullable();
            $table->string('employee_number')->nullable()->unique();
            $table->string('username')->nullable()->unique();
        });

        // Populate existing names into first_name for backward compatibility during transition
        DB::statement('UPDATE users SET first_name = name WHERE name IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'second_last_name', 'employee_number', 'username']);
            $table->string('name')->nullable(false)->change();
        });
    }
};
