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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('variant_id')
                ->constrained('product_variants')
                ->restrictOnDelete();

            // SNAPSHOT FIELDS
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedDecimal('unit_price', 10, 2);
            $table->unsignedDecimal('line_discount', 10, 2)->default(0);
            $table->unsignedDecimal('line_total', 10, 2);

            $table->timestamps();

            // helpful indexes
            $table->index('order_id');
            $table->index('variant_id');

            // Optional: prevent duplicate variant rows within one order
            // $table->unique(['order_id','variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
