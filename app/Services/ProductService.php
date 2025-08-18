<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductService extends BaseService
{
    protected string $cachePrefix = 'products:';

    public function getPaginatedProducts(int $perPage = 20): LengthAwarePaginator
    {
        $this->logInfo('Fetching paginated products', ['per_page' => $perPage]);

        return $this->remember(
            "paginated:$perPage",
            fn() => Product::with(['category', 'supplier', 'stores'])
                ->paginate($perPage)
        );
    }

    public function getProductById(int $id): Product
    {
        $this->logInfo('Fetching product by ID', ['product_id' => $id]);

        return $this->remember(
            "product:$id",
            fn() => Product::with(['category', 'supplier', 'stores'])->findOrFail($id)
        );
    }

    public function createProduct(array $data): Product
    {
        $this->logInfo('Creating new product', ['name' => $data['name'] ?? 'Unknown']);

        $product = Product::query()->create($data);

        $this->clearProductCaches();

        $this->logInfo('Product created successfully', [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku
        ]);

        return $product->load(['category', 'supplier', 'stores']);
    }

    public function updateProduct(int $id, array $data): Product
    {
        $this->logInfo('Updating product', ['product_id' => $id]);

        $product = Product::query()->findOrFail($id);
        $product->update($data);

        $this->forget("product:$id");
        $this->clearProductCaches();

        $this->logInfo('Product updated successfully', [
            'product_id' => $product->id,
            'name' => $product->name
        ]);

        return $product->fresh()->load(['category', 'supplier', 'stores']);
    }

    public function deleteProduct(int $id): bool
    {
        $this->logInfo('Deleting product', ['product_id' => $id]);

        $product = Product::query()->findOrFail($id);
        $deleted = $product->delete();

        if ($deleted) {
            $this->forget("product:$id");
            $this->clearProductCaches();

            $this->logInfo('Product deleted successfully', ['product_id' => $id]);
        }

        return $deleted;
    }

    public function searchProducts(string $query): Collection
    {
        if (empty($query)) {
            return new Collection();
        }

        $this->logInfo('Searching products', ['query' => $query]);

        return $this->remember(
            "search:" . md5($query),
            fn() => Product::with(['category:id,name', 'supplier:id,name'])
                ->active()
                ->search($query)
                ->limit(50) // Limit results for performance
                ->get(),
            600 // 10 minutes for search results
        );
    }

    public function getLowStockProducts(int $storeId): Collection
    {
        $this->logInfo('Fetching low stock products', ['store_id' => $storeId]);

        return $this->remember(
            "low_stock:store:$storeId",
            fn() => Product::with(['category:id,name', 'supplier:id,name'])
                ->active()
                ->lowStock($storeId)
                ->get(),
            300 // 5 minutes cache
        );
    }

    public function getProductsByCategory(int $categoryId, int $limit = 20): Collection
    {
        $this->logInfo('Fetching products by category', ['category_id' => $categoryId]);

        return $this->remember(
            "category:$categoryId:limit:$limit",
            fn() => Product::with(['supplier:id,name'])
                ->active()
                ->inCategory($categoryId)
                ->limit($limit)
                ->get(),
            600
        );
    }

    public function getTopSellingProducts(int $storeId, int $days = 30, int $limit = 10): Collection
    {
        $this->logInfo('Fetching top selling products', [
            'store_id' => $storeId,
            'days' => $days,
            'limit' => $limit
        ]);

        return $this->remember(
            "top_selling:store:$storeId:days:$days:limit:$limit",
            fn() => Product::with(['category:id,name'])
                ->select('products.*')
                ->selectRaw('SUM(sale_items.quantity) as total_sold')
                ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.store_id', $storeId)
                ->where('sales.created_at', '>=', now()->subDays($days))
                ->where('sales.status', 'completed')
                ->groupBy('products.id')
                ->orderByDesc('total_sold')
                ->limit($limit)
                ->get(),
            1800 // 30 minutes cache
        );
    }

    private function clearProductCaches(): void
    {
        $this->forget('paginated:10');
        $this->forget('paginated:20');
        $this->forget('paginated:50');
        // Clear search caches would require a more sophisticated approach in production
    }
}
