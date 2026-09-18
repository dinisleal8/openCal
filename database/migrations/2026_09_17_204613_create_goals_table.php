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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sex');
            $table->date('birth_date');
            $table->unsignedSmallInteger('height_cm');
            $table->string('activity_level');
            $table->string('goal_type');
            $table->decimal('weekly_rate_kg', 4, 2)->default(0.50);
            $table->decimal('target_weight_kg', 5, 2)->nullable();
            $table->unsignedSmallInteger('water_goal_ml')->default(2000);
            $table->unsignedTinyInteger('protein_pct')->default(30);
            $table->unsignedTinyInteger('carbs_pct')->default(40);
            $table->unsignedTinyInteger('fat_pct')->default(30);
            $table->boolean('include_band_calories')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('goals');
    }
};
