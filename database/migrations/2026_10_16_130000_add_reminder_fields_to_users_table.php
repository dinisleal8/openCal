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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('meal_reminders_enabled')->default(false);
            $table->string('meal_reminder_time', 5)->default('12:00');
            $table->boolean('water_reminders_enabled')->default(false);
            $table->string('water_reminder_time', 5)->default('15:00');
            $table->boolean('weigh_in_reminders_enabled')->default(false);
            $table->string('weigh_in_reminder_time', 5)->default('08:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'meal_reminders_enabled',
                'meal_reminder_time',
                'water_reminders_enabled',
                'water_reminder_time',
                'weigh_in_reminders_enabled',
                'weigh_in_reminder_time',
            ]);
        });
    }
};
