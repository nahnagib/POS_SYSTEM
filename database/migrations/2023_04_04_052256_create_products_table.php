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

            // Base product info (no price/stock/barcode here)
            $table->string('name');                    // e.g., "Dior Sauvage"
            $table->string('brand')->nullable();
            $table->string('sku')->nullable()->unique();

            // Relations (optional/nullable to keep POS fast)
            $table->foreignId('category_id')->nullable()
                  ->constrained('categories')->nullOnDelete()->cascadeOnUpdate();

            // Alerts / flags
            $table->boolean('track_stock')->default(true);
            $table->unsignedInteger('low_stock_threshold')->default(2);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['name', 'brand']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
