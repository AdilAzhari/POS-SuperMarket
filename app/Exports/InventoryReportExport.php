<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class InventoryReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        $query = Product::with(['category', 'supplier'])
            ->where('is_active', true);

        return $query->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Name',
            'SKU',
            'Category',
            'Supplier',
            'Current Stock',
            'Low Stock Threshold',
            'Cost Price',
            'Selling Price',
            'Status',
        ];
    }

    public function map($product): array
    {
        $stockStatus = 'In Stock';
        if ($product->stock === 0) {
            $stockStatus = 'Out of Stock';
        } elseif ($product->stock <= $product->low_stock_threshold) {
            $stockStatus = 'Low Stock';
        }

        return [
            $product->id,
            $product->name,
            $product->sku,
            $product->category->name ?? 'N/A',
            $product->supplier->name ?? 'N/A',
            $product->stock,
            $product->low_stock_threshold,
            number_format($product->cost_price ?? 0, 2),
            number_format($product->price, 2),
            $stockStatus,
        ];
    }
}
