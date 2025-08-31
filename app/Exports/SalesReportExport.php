<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly array $dates, private readonly ?int $storeId, private $user) {}

    public function collection(): Collection
    {
        $query = Sale::with(['customer', 'cashier', 'store'])
            ->whereBetween('created_at', $this->dates)
            ->where('status', 'completed');

        if ($this->storeId && ! $this->user->isAdmin()) {
            $query->where('store_id', $this->storeId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Sale ID',
            'Date',
            'Store',
            'Cashier',
            'Customer',
            'Total Amount',
            'Tax Amount',
            'Payment Method',
            'Status',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->created_at->format('Y-m-d H:i:s'),
            $sale->store->name ?? 'N/A',
            $sale->cashier->name ?? 'N/A',
            $sale->customer->name ?? 'Walk-in Customer',
            number_format($sale->total, 2),
            number_format($sale->tax_amount ?? 0, 2),
            $sale->payment_method ? (is_object($sale->payment_method) ? $sale->payment_method->value : ucfirst((string) $sale->payment_method)) : 'N/A',
            $sale->status ? (is_object($sale->status) ? $sale->status->value : ucfirst((string) $sale->status)) : 'N/A',
        ];
    }
}
