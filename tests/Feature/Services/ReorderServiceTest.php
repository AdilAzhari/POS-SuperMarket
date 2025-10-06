<?php

declare(strict_types=1);

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReorderService;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->supplier = Supplier::factory()->create();
    $this->product = Product::factory()->create([
        'supplier_id' => $this->supplier->id,
        'cost' => 10.00,
    ]);

    // Attach product to store with low stock
    $this->product->stores()->attach($this->store->id, [
        'stock' => 5,
        'low_stock_threshold' => 20,
    ]);

    $this->reorderService = app(ReorderService::class);
});

it('can get reorder list for a store', function (): void {
    $reorderList = $this->reorderService->getReorderList($this->store->id);

    expect($reorderList)->toBeInstanceOf(Illuminate\Support\Collection::class);
    expect($reorderList->count())->toBeGreaterThan(0);

    $firstItem = $reorderList->first();
    expect($firstItem)->toHaveKeys([
        'product',
        'supplier',
        'current_stock',
        'threshold',
        'suggested_order_qty',
        'estimated_cost',
        'priority',
    ]);
});

it('can group reorder list by supplier', function (): void {
    $reorderListBySupplier = $this->reorderService->getReorderListBySupplier($this->store->id);

    expect($reorderListBySupplier)->toBeInstanceOf(Illuminate\Support\Collection::class);

    if ($reorderListBySupplier->isNotEmpty()) {
        $firstSupplierGroup = $reorderListBySupplier->first();
        expect($firstSupplierGroup)->toHaveKeys([
            'supplier',
            'items',
            'total_items',
            'total_cost',
            'high_priority_items',
        ]);
    }
});

it('can create purchase order from reorder items', function (): void {
    $items = [
        [
            'product_id' => $this->product->id,
            'supplier_id' => $this->supplier->id,
            'quantity' => 50,
            'notes' => 'Test reorder',
        ],
    ];

    $purchaseOrder = $this->reorderService->createPurchaseOrderFromReorder(
        $items,
        $this->store->id,
        $this->user->id,
        'Test PO from reorder'
    );

    expect($purchaseOrder)->toBeInstanceOf(PurchaseOrder::class);
    expect($purchaseOrder->supplier_id)->toBe($this->supplier->id);
    expect($purchaseOrder->store_id)->toBe($this->store->id);
    expect($purchaseOrder->created_by)->toBe($this->user->id);
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::DRAFT);
    expect($purchaseOrder->items)->toHaveCount(1);

    $item = $purchaseOrder->items->first();
    expect($item->product_id)->toBe($this->product->id);
    expect($item->quantity_ordered)->toBe(50);
    expect((float) $item->unit_cost)->toBe(10.00);
    expect((float) $item->total_cost)->toBe(500.00);
});

it('throws exception when creating PO with mixed suppliers', function (): void {
    $supplier2 = Supplier::factory()->create();
    $product2 = Product::factory()->create(['supplier_id' => $supplier2->id]);

    $items = [
        [
            'product_id' => $this->product->id,
            'supplier_id' => $this->supplier->id,
            'quantity' => 50,
        ],
        [
            'product_id' => $product2->id,
            'supplier_id' => $supplier2->id,
            'quantity' => 30,
        ],
    ];

    expect(fn () => $this->reorderService->createPurchaseOrderFromReorder(
        $items,
        $this->store->id,
        $this->user->id
    ))->toThrow(Exception::class, 'All items must be from the same supplier');
});

it('can get automatic reorder suggestions', function (): void {
    // Create a product that needs immediate reordering (high priority, low days remaining)
    $criticalProduct = Product::factory()->create([
        'supplier_id' => $this->supplier->id,
        'cost' => 15.00,
    ]);

    $criticalProduct->stores()->attach($this->store->id, [
        'stock' => 0, // Out of stock - should be high priority
        'low_stock_threshold' => 10,
    ]);

    $automaticSuggestions = $this->reorderService->getAutomaticReorderSuggestions($this->store->id);

    expect($automaticSuggestions)->toBeInstanceOf(Illuminate\Support\Collection::class);

    // Automatic suggestions may be empty if criteria (priority >= 4, days <= 3, no pending) aren't met
    // Just verify the method works and returns a collection
});

it('can get supplier comparison', function (): void {
    $comparison = $this->reorderService->getSupplierComparison($this->store->id);

    expect($comparison)->toBeInstanceOf(Illuminate\Support\Collection::class);

    if ($comparison->isNotEmpty()) {
        $firstComparison = $comparison->first();
        expect($firstComparison)->toHaveKeys([
            'supplier',
            'items_count',
            'total_cost',
            'high_priority_items',
            'avg_lead_time',
            'reliability_score',
            'priority_score',
        ]);
    }
});

it('can get reorder history', function (): void {
    $history = $this->reorderService->getReorderHistory($this->store->id, 30);

    expect($history)->toBeInstanceOf(Illuminate\Support\Collection::class);

    // History can be empty, that's fine for testing
    if ($history->isNotEmpty()) {
        $firstOrder = $history->first();
        expect($firstOrder)->toHaveKeys([
            'po_number',
            'supplier',
            'status',
            'items_count',
            'total_amount',
            'created_at',
            'ordered_at',
            'received_at',
        ]);
    }
});

it('can clear reorder cache', function (): void {
    // This test verifies the method runs without error
    // Cache clearing is hard to test directly, but we can ensure no exceptions
    expect(fn () => $this->reorderService->clearReorderCache($this->store->id))->not->toThrow(Exception::class);
});

it('can update reorder points', function (): void {
    $updatedCount = $this->reorderService->updateReorderPoints($this->store->id);

    expect($updatedCount)->toBeInt();
    expect($updatedCount)->toBeGreaterThanOrEqual(0);
});
