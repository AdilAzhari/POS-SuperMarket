<?php

use App\DTOs\CreateSaleDTO;
use App\DTOs\SaleResponseDTO;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SaleProcessingException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stockService = Mockery::mock(StockService::class);
    $this->saleService = new SaleService($this->stockService);
    
    $this->store = Store::factory()->create();
    $this->cashier = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->product = Product::factory()->create();
});

it('can get paginated sales', function () {
    Sale::factory()->count(25)->create();
    
    $result = $this->saleService->getPaginatedSales(20);
    
    expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
        ->and($result->count())->toBe(20)
        ->and($result->total())->toBe(25);
});

it('can get sale by id', function () {
    $sale = Sale::factory()->create();
    
    $result = $this->saleService->getSaleById($sale->id);
    
    expect($result)->toBeInstanceOf(SaleResponseDTO::class)
        ->and($result->id)->toBe($sale->id)
        ->and($result->code)->toBe($sale->code);
});

it('can create a sale successfully', function () {
    $this->stockService->shouldReceive('getStockForStore')
        ->once()
        ->with($this->product->id, $this->store->id)
        ->andReturn(10);
    
    $this->stockService->shouldReceive('decrementStock')
        ->once()
        ->with($this->product->id, $this->store->id, 2);
    
    $saleData = new CreateSaleDTO(
        store_id: $this->store->id,
        cashier_id: $this->cashier->id,
        customer_id: $this->customer->id,
        items: [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 10.00,
                'quantity' => 2,
                'discount' => 0,
                'tax' => 0,
            ]
        ],
        payment_method: 'cash',
        discount: 0,
        tax: 1.60
    );
    
    $result = $this->saleService->createSale($saleData);
    
    expect($result)->toBeInstanceOf(SaleResponseDTO::class)
        ->and($result->store_id)->toBe($this->store->id)
        ->and($result->cashier_id)->toBe($this->cashier->id)
        ->and($result->customer_id)->toBe($this->customer->id)
        ->and($result->items_count)->toBe(2)
        ->and($result->subtotal)->toBe(20.00)
        ->and($result->total)->toBe(21.60)
        ->and($result->payment_method)->toBe('cash');
    
    $this->assertDatabaseHas('sales', [
        'store_id' => $this->store->id,
        'cashier_id' => $this->cashier->id,
        'customer_id' => $this->customer->id,
        'items_count' => 2,
        'subtotal' => 20.00,
        'total' => 21.60,
    ]);
});

it('throws exception when insufficient stock', function () {
    $this->stockService->shouldReceive('getStockForStore')
        ->once()
        ->with($this->product->id, $this->store->id)
        ->andReturn(1);
    
    $saleData = new CreateSaleDTO(
        store_id: $this->store->id,
        cashier_id: $this->cashier->id,
        customer_id: null,
        items: [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 10.00,
                'quantity' => 5, // More than available stock
                'discount' => 0,
                'tax' => 0,
            ]
        ],
        payment_method: 'cash'
    );
    
    expect(fn() => $this->saleService->createSale($saleData))
        ->toThrow(InsufficientStockException::class);
});

it('updates customer statistics after sale', function () {
    $this->stockService->shouldReceive('getStockForStore')
        ->once()
        ->andReturn(10);
    
    $this->stockService->shouldReceive('decrementStock')
        ->once();
    
    $initialPurchases = $this->customer->total_purchases;
    $initialSpent = $this->customer->total_spent;
    
    $saleData = new CreateSaleDTO(
        store_id: $this->store->id,
        cashier_id: $this->cashier->id,
        customer_id: $this->customer->id,
        items: [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 15.00,
                'quantity' => 1,
                'discount' => 0,
                'tax' => 0,
            ]
        ],
        payment_method: 'cash'
    );
    
    $this->saleService->createSale($saleData);
    
    $this->customer->refresh();
    
    expect($this->customer->total_purchases)->toBe($initialPurchases + 1)
        ->and((float) $this->customer->total_spent)->toBe($initialSpent + 15.0)
        ->and($this->customer->last_purchase_at)->not->toBeNull();
});

it('generates transaction code correctly', function () {
    $this->stockService->shouldReceive('getStockForStore')
        ->once()
        ->andReturn(10);
    
    $this->stockService->shouldReceive('decrementStock')
        ->once();
    
    Sale::factory()->count(5)->create(); // Existing sales
    
    $saleData = new CreateSaleDTO(
        store_id: $this->store->id,
        cashier_id: $this->cashier->id,
        customer_id: null,
        items: [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 10.00,
                'quantity' => 1,
                'discount' => 0,
                'tax' => 0,
            ]
        ],
        payment_method: 'cash'
    );
    
    $result = $this->saleService->createSale($saleData);
    
    expect($result->code)->toMatch('/^TXN-\d{6}$/');
});

afterEach(function () {
    Mockery::close();
});