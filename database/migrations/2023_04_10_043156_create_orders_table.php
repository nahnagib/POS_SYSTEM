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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // WHO & WHEN
            $table->dateTime('order_date')->useCurrent(); // default now()
            $table->string('invoice_no')->unique();       // e.g., INV-000001
            $table->foreignId('user_id')                  // cashier
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // STATUS
            $table->enum('order_status', ['paid','void','refunded'])->default('paid');
            $table->enum('payment_status', ['paid','partial','unpaid'])->default('paid');
            $table->enum('payment_method', ['cash','edfali','mobicash','local_service'])->default('cash');

            // TOTALS (unsigned)
            $table->unsignedInteger('total_items');          // sum of quantities
            $table->unsignedDecimal('sub_total', 10, 2);
            $table->unsignedDecimal('discount', 10, 2)->default(0);
            $table->unsignedDecimal('vat', 10, 2)->default(0);
            $table->unsignedDecimal('total', 10, 2);         // grand total

            $table->timestamps();

            // helpful indexes
            $table->index('order_date');
            $table->index('user_id');
            $table->index(['payment_method','order_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
