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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('barcode', 64)->nullable();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->decimal('calories_kcal_per_100g', 7, 1)->default(0);
            $table->decimal('protein_g_per_100g', 6, 1)->default(0);
            $table->decimal('carbs_g_per_100g', 6, 1)->default(0);
            $table->decimal('fat_g_per_100g', 6, 1)->default(0);
            $table->string('source')->default('barcode');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('barcode');
            $table->index(['user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('products');
    }
};
