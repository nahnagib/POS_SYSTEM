<?php

namespace App\Services;

use App\Models\Order;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

class ReceiptPrinter
{
    public function printOrder(Order $order): void
    {
        $driver = env('PRINTER_DRIVER', 'network');
        $printer = $this->connect($driver);

        $shop   = env('PRINTER_SHOP_NAME', 'Luxury Perfumes');
        $footer = env('PRINTER_FOOTER', 'شكراً لزيارتكم');

        try {
            // --- Header ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text($shop . "\n");
            $printer->setEmphasis(false);
            $printer->text("Invoice: {$order->invoice_no}\n");
            $printer->text("Date: " . $order->order_date->format('Y-m-d H:i') . "\n");
            $printer->text(str_repeat('-', 32) . "\n");

            // --- Items ---
            foreach ($order->details as $d) {
                $name = $this->trim($d->product_name . ($d->variant_name ? " ({$d->variant_name})" : ''), 32);
                $printer->text($name . "\n");

                $qtyPrice  = sprintf("%dx%.2f", $d->quantity, $d->unit_price);
                $lineTotal = number_format($d->line_total, 2);
                $printer->text($this->rightAlign($qtyPrice, $lineTotal, 32) . "\n");

                if ($d->line_discount > 0) {
                    $printer->text($this->rightAlign("  - Discount", number_format($d->line_discount, 2), 32) . "\n");
                }
            }

            $printer->text(str_repeat('-', 32) . "\n");

            // --- Totals ---
            $printer->text($this->labelMoney("Subtotal", $order->sub_total));
            if ($order->discount > 0) {
                $printer->text($this->labelMoney("Discount", $order->discount));
            }
            if ($order->vat > 0) {
                $printer->text($this->labelMoney("VAT", $order->vat));
            }
            $printer->setEmphasis(true);
            $printer->text($this->labelMoney("TOTAL", $order->total));
            $printer->setEmphasis(false);

            $printer->text($this->labelValue("Payment", strtoupper($order->payment_method)));
            $printer->text($this->labelValue("Status", strtoupper($order->payment_status)));

            $printer->text(str_repeat('-', 32) . "\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text($footer . "\n");

            $printer->feed(2);
            $printer->cut();
        } finally {
            $printer->close();
        }
    }

    private function connect(string $driver): Printer
    {
        if ($driver === 'network') {
            $connector = new NetworkPrintConnector(env('PRINTER_IP'), (int)env('PRINTER_PORT', 9100));
            return new Printer($connector);
        }

        // USB/raw: Linux = /dev/usb/lp0, Windows = "smb://PC/Printer"
        $device = env('PRINTER_DEVICE', '/dev/usb/lp0');
        $connector = new FilePrintConnector($device);
        return new Printer($connector);
    }

    private function labelMoney(string $label, float $amount, int $width = 32): string
    {
        $v = number_format($amount, 2) . " LYD";
        return $this->rightAlign($label, $v, $width) . "\n";
    }

    private function labelValue(string $label, string $value, int $width = 32): string
    {
        return $this->rightAlign($label, $value, $width) . "\n";
    }

    private function rightAlign(string $left, string $right, int $width): string
    {
        $left  = $this->trim($left, $width);
        $right = $this->trim($right, $width);
        $spaces = max(1, $width - mb_strlen($left) - mb_strlen($right));
        return $left . str_repeat(' ', $spaces) . $right;
    }

    private function trim(string $s, int $max): string
    {
        return mb_strlen($s) > $max ? (mb_substr($s, 0, $max-1) . '…') : $s;
    }
}
