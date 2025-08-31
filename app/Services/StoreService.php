<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class StoreService extends BaseService
{
    protected string $cachePrefix = 'store:';

    protected int $cacheTime = 3600;

    public function __construct()
    {
        // Set cache prefix for this service
    }

    /**
     * Get all stores with optional filters
     */
    public function getAllStores(array $filters = []): Collection
    {
        $query = Store::query()
            ->withCount(['products', 'sales'])
            ->with(['products', 'sales']);

        if (isset($filters['with_contact']) && $filters['with_contact']) {
            $query->withContact();
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Create a new store
     */
    public function createStore(array $data): Store
    {
        $this->logInfo('Creating new store', ['name' => $data['name']]);

        $store = Store::query()->create($data);

        // Clear cache
        $this->forget('all');

        $this->logInfo('Store created successfully', ['store_id' => $store->id]);

        return $store;
    }

    /**
     * Update an existing store
     */
    public function updateStore(Store $store, array $data): Store
    {
        $this->logInfo('Updating store', ['store_id' => $store->id]);

        $store->update($data);

        // Clear cache
        $this->forget('all');
        $this->forget("store:$store->id");

        $this->logInfo('Store updated successfully', ['store_id' => $store->id]);

        return $store->fresh();
    }

    /**
     * Delete a store
     *
     * @throws Exception
     */
    public function deleteStore(Store $store): bool
    {
        $this->logInfo('Deleting store', ['store_id' => $store->id]);

        try {
            DB::beginTransaction();

            // Check if store has any sales
            if ($store->sales()->exists()) {
                $this->logWarning('Cannot delete store with existing sales', ['store_id' => $store->id]);
                throw new Exception('Cannot delete store that has sales records.');
            }

            // Detach all products from the store
            $store->products()->detach();

            // Delete the store
            $store->delete();

            DB::commit();

            // Clear cache
            $this->forget('all');
            $this->forget("store:$store->id");

            $this->logInfo('Store deleted successfully', ['store_id' => $store->id]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Failed to delete store', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get store with detailed information
     */
    public function getStoreDetails(Store $store): array
    {
        return $this->remember("store:$store->id:details", function () use ($store): array {
            $store->load(['products', 'sales']);

            return [
                'store' => $store,
                'total_products' => $store->total_products,
                'total_stock' => $store->total_stock,
                'low_stock_products' => $store->low_stock_products,
                'total_sales_amount' => $store->total_sales_amount,
                'recent_sales' => $store->sales()
                    ->latest()
                    ->take(5)
                    ->with(['customer', 'payments'])
                    ->get(),
            ];
        });
    }

    /**
     * Add products to a store
     *
     * @throws Exception
     */
    public function addProductsToStore(Store $store, array $products): void
    {
        $this->logInfo('Adding products to store', [
            'store_id' => $store->id,
            'product_count' => count($products),
        ]);

        try {
            DB::beginTransaction();

            foreach ($products as $productData) {
                $productId = $productData['product_id'];
                $stock = $productData['stock'] ?? 0;
                $lowStockThreshold = $productData['low_stock_threshold'] ?? 5;

                // Check if product exists
                if (! Product::query()->find($productId)) {
                    throw new Exception("Product with ID $productId not found.");
                }

                // Check if product is already in store
                if ($store->products()->where('product_id', $productId)->exists()) {
                    // Update existing
                    $store->products()->updateExistingPivot($productId, [
                        'stock' => $stock,
                        'low_stock_threshold' => $lowStockThreshold,
                    ]);
                } else {
                    // Add new
                    $store->products()->attach($productId, [
                        'stock' => $stock,
                        'low_stock_threshold' => $lowStockThreshold,
                    ]);
                }
            }

            DB::commit();

            // Clear cache
            $this->forget("store:$store->id:details");

            $this->logInfo('Products added to store successfully', ['store_id' => $store->id]);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Failed to add products to store', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get stores analytics
     */
    public function getStoresAnalytics(): array
    {
        return $this->remember('analytics', function (): array {
            $stores = Store::query()->withCount(['products', 'sales'])
                ->withSum('sales', 'total')
                ->get();

            return [
                'total_stores' => $stores->count(),
                'stores_with_contact' => $stores->filter(fn ($store): bool => $store->hasContact())->count(),
                'total_products_across_stores' => $stores->sum('products_count'),
                'total_sales_amount' => $stores->sum('sales_sum_total') ?? 0,
                'average_products_per_store' => $stores->count() > 0
                    ? round($stores->sum('products_count') / $stores->count(), 2)
                    : 0,
                'stores_summary' => $stores->map(fn ($store): array => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'products_count' => $store->products_count,
                    'sales_count' => $store->sales_count,
                    'total_sales_amount' => $store->sales_sum_total ?? 0,
                ]),
            ];
        }, 1800); // Cache for 30 minutes
    }

    /**
     * Validate store before operations
     */
    public function validateStoreForSale(Store $store): array
    {
        $issues = [];

        if (! $store->hasContact()) {
            $issues[] = 'Store has no contact information (phone or email)';
        }

        if ($store->products()->count() === 0) {
            $issues[] = 'Store has no products assigned';
        }

        $lowStockCount = $store->low_stock_products->count();
        if ($lowStockCount > 0) {
            $issues[] = "Store has $lowStockCount products with low stock";
        }

        return $issues;
    }
}
