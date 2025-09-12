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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            $table->decimal('size_ml', 6, 2)->nullable();     // 35.00 / 100.00
            $table->string('sku')->nullable()->unique();
            $table->string('barcode', 20)->nullable()->unique(); // we’ll auto-generate if null

            $table->decimal('price', 10, 2)->default(0);      // selling price
            $table->decimal('cost_price', 10, 2)->nullable(); // optional
            $table->unsignedInteger('stock')->default(0); // current stock

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('low_stock_threshold')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'size_ml']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
