<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sale;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

final class ReceiptService extends BaseService
{
    protected string $cachePrefix = 'receipts:';

    /**
     * Generate receipt content for a sale
     */
    public function generateReceipt(Sale $sale, array $options = []): array
    {
        $receiptSettings = $this->getReceiptSettings();
        $storeSettings = $this->getStoreSettings();

        // Load sale with all required relationships
        $sale->load(['items.product', 'customer', 'cashier', 'payments', 'store']);

        // Ensure we have minimum required data
        if (! $sale->cashier) {
            // Create a default cashier if none exists
            $sale->setRelation('cashier', (object) [
                'id' => 0,
                'name' => 'Staff',
            ]);
        }

        // Ensure we have items with proper data
        foreach ($sale->items as $item) {
            if (! $item->product_name && $item->product) {
                $item->product_name = $item->product->name;
            }
            if (! $item->product_name) {
                $item->product_name = 'Unknown Product';
            }
            if (! $item->price) {
                $item->price = 0;
            }
            if (! $item->quantity) {
                $item->quantity = 1;
            }
            if (! $item->line_total) {
                $item->line_total = $item->price * $item->quantity;
            }
        }

        // Ensure we have totals
        if (! $sale->subtotal) {
            $sale->subtotal = $sale->items->sum('line_total');
        }
        if (! $sale->total) {
            $sale->total = $sale->subtotal - ($sale->discount ?? 0) + ($sale->tax ?? 0);
        }

        $receiptData = [
            'sale' => $sale,
            'store' => (object) $storeSettings, // Convert to object for compatibility
            'settings' => $receiptSettings,
            'generated_at' => now(),
            'receipt_number' => $this->generateReceiptNumber($sale),
            'format' => $options['format'] ?? 'thermal',
            'width' => $options['width'] ?? 58, // 58mm thermal paper default
        ];

        return [
            'data' => $receiptData,
            'html' => $this->generateHtmlReceipt($receiptData),
            'thermal' => $this->generateThermalReceipt($receiptData),
            'pdf_url' => $this->generatePdfReceipt($receiptData),
        ];
    }

    /**
     * Print receipt to thermal printer
     */
    public function printReceipt(Sale $sale, array $options = []): bool
    {
        try {
            $receipt = $this->generateReceipt($sale, $options);

            // Get printer settings
            $printerSettings = $this->getPrinterSettings();

            if (! $printerSettings['enabled']) {
                return false;
            }

            // Send to printer based on connection type
            return match ($printerSettings['connection_type']) {
                'usb' => $this->printToUSB($receipt['thermal'], $printerSettings),
                'network' => $this->printToNetwork($receipt['thermal'], $printerSettings),
                'bluetooth' => $this->printToBluetooth(),
                default => false,
            };
        } catch (Exception $e) {
            $this->logError('Receipt printing failed', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Save receipt to storage for later retrieval
     */
    public function saveReceipt(Sale $sale): string
    {
        $receipt = $this->generateReceipt($sale);
        $filename = "receipts/{$sale->store_id}/{$sale->created_at->format('Y/m')}/receipt-{$sale->id}.html";

        Storage::disk('local')->put($filename, $receipt['html']);

        return $filename;
    }

    /**
     * Get saved receipt
     */
    public function getStoredReceipt(Sale $sale): ?string
    {
        $filename = "receipts/{$sale->store_id}/{$sale->created_at->format('Y/m')}/receipt-{$sale->id}.html";

        if (Storage::disk('local')->exists($filename)) {
            return Storage::disk('local')->get($filename);
        }

        return null;
    }

    /**
     * Generate HTML receipt for web display
     */
    private function generateHtmlReceipt(array $data): string
    {
        return View::make('receipts.html', $data)->render();
    }

    /**
     * Generate thermal printer compatible receipt
     */
    private function generateThermalReceipt(array $data): string
    {
        $receipt = '';
        $width = $data['width'];
        $sale = $data['sale'];
        $store = $data['store'];
        $settings = $data['settings'];

        // Header
        $receipt .= $this->centerText($store->name, $width)."\n";
        if ($store->address) {
            $receipt .= $this->centerText($store->address, $width)."\n";
        }
        if ($store->phone) {
            $receipt .= $this->centerText("Tel: {$store->phone}", $width)."\n";
        }
        $receipt .= str_repeat('-', $width)."\n";

        // Sale info
        $receipt .= "Receipt: {$data['receipt_number']}\n";
        $receipt .= 'Date: '.$sale->created_at->format('Y-m-d H:i:s')."\n";
        $receipt .= "Cashier: {$sale->cashier->name}\n";
        if ($sale->customer) {
            $receipt .= "Customer: {$sale->customer->name}\n";
        }
        $receipt .= str_repeat('-', $width)."\n";

        // Items
        foreach ($sale->items as $item) {
            $receipt .= $this->formatReceiptLine(
                $item->product_name,
                "{$item->quantity} x ".number_format($item->price, 2),
                number_format($item->line_total, 2),
                $width
            );
        }

        $receipt .= str_repeat('-', $width)."\n";

        // Totals
        $receipt .= $this->formatReceiptLine('Subtotal:', '', number_format($sale->subtotal, 2), $width);
        if ($sale->discount > 0) {
            $receipt .= $this->formatReceiptLine('Discount:', '', '-'.number_format($sale->discount, 2), $width);
        }
        if ($sale->tax > 0) {
            $receipt .= $this->formatReceiptLine('Tax:', '', number_format($sale->tax, 2), $width);
        }
        $receipt .= str_repeat('=', $width)."\n";
        $receipt .= $this->formatReceiptLine('TOTAL:', '', number_format($sale->total, 2), $width, true);
        $receipt .= str_repeat('=', $width)."\n";

        // Payment info
        $receipt .= "\nPayment: ".ucfirst(is_string($sale->payment_method) ? $sale->payment_method : $sale->payment_method->value)."\n";

        // Header/Thank you message
        if ($settings['header'] ?? null) {
            $lines = explode('\n', $settings['header']);
            foreach ($lines as $line) {
                $receipt .= "\n".$this->centerText($line, $width)."\n";
            }
        } else {
            $receipt .= "\n".$this->centerText('Thank you for your purchase!', $width)."\n";
        }

        // Loyalty points if customer exists
        if ($sale->customer && $sale->customer->loyalty_points > 0) {
            $receipt .= "\nLoyalty Points: {$sale->customer->loyalty_points}\n";
        }

        // Footer
        if ($settings['footer'] ?? null) {
            $lines = explode('\n', $settings['footer']);
            foreach ($lines as $line) {
                $receipt .= "\n".$this->centerText($line, $width)."\n";
            }
        } // Feed paper

        return $receipt."\n\n\n";
    }

    /**
     * Generate PDF receipt and return URL
     */
    private function generatePdfReceipt(array $data): string
    {
        // For now, return a placeholder URL
        // In production, you'd use a PDF library like DomPDF or TCPDF
        return "/api/receipts/{$data['sale']->id}/pdf";
    }

    /**
     * Format a line for thermal receipt
     */
    private function formatReceiptLine(string $left, string $middle, string $right, int $width, bool $bold = false): string
    {
        $availableWidth = $width - mb_strlen($right);
        $leftAndMiddle = $left.($middle !== '' && $middle !== '0' ? " {$middle}" : '');

        if (mb_strlen($leftAndMiddle) > $availableWidth - 1) {
            $leftAndMiddle = mb_substr($leftAndMiddle, 0, $availableWidth - 4).'...';
        }

        $padding = $availableWidth - mb_strlen($leftAndMiddle);
        $line = $leftAndMiddle.str_repeat(' ', $padding).$right;

        return $bold ? mb_strtoupper($line) : $line."\n";
    }

    /**
     * Center text for thermal receipt
     */
    private function centerText(string $text, int $width): string
    {
        $padding = ($width - mb_strlen($text)) / 2;

        return str_repeat(' ', max(0, floor($padding))).$text;
    }

    /**
     * Generate unique receipt number
     */
    private function generateReceiptNumber(Sale $sale): string
    {
        $storeId = $sale->store_id ?? 1; // Default to store 1 if not set
        $date = $sale->created_at ? $sale->created_at->format('ymd') : now()->format('ymd');
        $saleId = mb_str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT);

        return "R-{$storeId}{$date}{$saleId}";
    }

    /**
     * Get store settings from database
     */
    private function getStoreSettings(): array
    {
        return $this->remember('store_settings', function () {
            $storeSetting = Setting::where('key', 'store_info')->first();

            if ($storeSetting && $storeSetting->value) {
                return $storeSetting->value;
            }

            // Default store settings if not found
            return [
                'name' => 'SuperMarket POS',
                'address' => '123 Main Street, Anytown, ST 12345',
                'phone' => '+1-555-0123',
                'email' => 'info@supermarketpos.com',
            ];
        }, 3600);
    }

    /**
     * Get receipt settings from database
     */
    private function getReceiptSettings(): array
    {
        return $this->remember('receipt_settings', function () {
            $receiptSetting = Setting::where('key', 'receipt_settings')->first();

            if ($receiptSetting && $receiptSetting->value) {
                return $receiptSetting->value;
            }

            // Default receipt settings if not found
            return [
                'header' => 'Thank you for shopping with us!',
                'footer' => 'Please come again!\nReturn policy: 30 days with receipt',
                'showLogo' => true,
            ];
        }, 3600);
    }

    /**
     * Print to USB thermal printer
     */
    private function printToUSB(string $content, array $settings): bool
    {
        $device = $settings['usb_device'] ?? '/dev/usb/lp0';

        if (! file_exists($device)) {
            return false;
        }

        try {
            file_put_contents($device, $content);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Print to network thermal printer
     */
    private function printToNetwork(string $content, array $settings): bool
    {
        $ip = $settings['network_ip'] ?? '';
        $port = $settings['network_port'] ?? 9100;

        if (! $ip) {
            return false;
        }

        try {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($socket, $ip, $port);
            socket_write($socket, $content);
            socket_close($socket);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Print to Bluetooth thermal printer
     */
    private function printToBluetooth(): bool
    {
        // Bluetooth printing would require platform-specific implementation
        // This is a placeholder for future implementation
        return false;
    }

    /**
     * Get printer settings
     */
    private function getPrinterSettings(): array
    {
        return $this->remember('printer_settings', fn () => Setting::whereIn('key', [
            'printer_enabled',
            'printer_connection_type',
            'printer_usb_device',
            'printer_network_ip',
            'printer_network_port',
            'printer_paper_width',
            'printer_auto_print',
        ])
            ->pluck('value', 'key')
            ->mapWithKeys(function ($value, $key) {
                // Convert string booleans to actual booleans
                if (in_array($value, ['true', 'false'])) {
                    $value = $value === 'true';
                }

                return [str_replace('printer_', '', $key) => $value];
            })
            ->toArray(), 300);
    }
}
