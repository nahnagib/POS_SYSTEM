<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\ReceiptPrinter;

class ReceiptTest extends Command
{
    // php artisan receipt:test {orderId?} {--last}
    protected $signature = 'receipt:test {orderId?} {--last : Print the most recent order}';
    protected $description = 'Print a thermal receipt for an order using ReceiptPrinter service';

    public function handle(ReceiptPrinter $printer): int
    {
        $orderId = $this->argument('orderId');
        $useLast = (bool) $this->option('last');

        if ($useLast) {
            $order = Order::with('details')->latest('order_date')->first();
            if (!$order) {
                $this->error('No orders found.');
                return self::FAILURE;
            }
            $this->info("Printing most recent order: #{$order->id} ({$order->invoice_no})");
        } else {
            if (!$orderId || !is_numeric($orderId)) {
                $this->error('Provide an {orderId} or use --last.');
                return self::FAILURE;
            }
            $order = Order::with('details')->find($orderId);
            if (!$order) {
                $this->error("Order {$orderId} not found.");
                return self::FAILURE;
            }
            $this->info("Printing order: #{$order->id} ({$order->invoice_no})");
        }

        try {
            $printer->printOrder($order);
            $this->info('Receipt sent to printer.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Printing failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

