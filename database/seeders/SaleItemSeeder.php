<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Seeder;

class SaleItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing sales and products
        $sales = Sale::all();
        $products = Product::all();

        if ($sales->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No sales or products found. Please run SaleSeeder and ProductSeeder first.');
            return;
        }

        $this->command->info('Creating sale items...');

        foreach ($sales as $sale) {
            // Create 1-5 items per sale
            $itemCount = fake()->numberBetween(1, 5);
            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            for ($i = 0; $i < $itemCount; $i++) {
                $product = $products->random();
                $quantity = fake()->numberBetween(1, 3);
                $price = $product->price;
                $discount = fake()->randomFloat(2, 0, $price * 0.2);
                $tax = ($price - $discount) * 0.1;
                $lineTotal = ($price - $discount + $tax) * $quantity;

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $price,
                    'quantity' => $quantity,
                    'discount' => $discount,
                    'tax' => $tax,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $price * $quantity;
                $totalDiscount += $discount * $quantity;
                $totalTax += $tax * $quantity;
            }

            // Update sale totals
            $sale->update([
                'items_count' => $itemCount,
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'tax' => $totalTax,
                'total' => $subtotal - $totalDiscount + $totalTax,
            ]);
        }

        // Create some specific scenario sale items
        $this->createBulkSaleItems();
        $this->createDiscountedSaleItems();

        $this->command->info('Sale items created successfully.');
    }

    /**
     * Create bulk quantity sale items
     */
    private function createBulkSaleItems(): void
    {
        $sales = Sale::query()->take(5)->get();
        $products = Product::query()->take(10)->get();

        foreach ($sales as $sale) {
            SaleItem::factory()
                ->bulkQuantity()
                ->create([
                    'sale_id' => $sale->id,
                    'product_id' => $products->random()->id,
                ]);
        }
    }

    /**
     * Create highly discounted sale items
     */
    private function createDiscountedSaleItems(): void
    {
        $sales = Sale::take(3)->get();
        $products = Product::take(5)->get();

        foreach ($sales as $sale) {
            SaleItem::factory()
                ->highDiscount()
                ->create([
                    'sale_id' => $sale->id,
                    'product_id' => $products->random()->id,
                ]);
        }
    }
}
