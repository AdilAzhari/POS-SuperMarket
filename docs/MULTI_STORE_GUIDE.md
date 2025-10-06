# Multi‑Store Conversion Guide for POS‑SuperMarket

This guide explains how to run this repository as a true multi‑store system, what’s already supported, and what you should implement or double‑check. It’s designed for advanced Laravel + Vue contributors to make the app production‑ready for multiple stores/branches.


## 1) Current readiness in this repo
The project already includes strong multi‑store primitives:
- Data model
  - stores table and Store CRUD.
  - product_store pivot with stock and low_stock_threshold, enabling per‑store stock.
  - stock_movements table has store_id, supports transfer_in / transfer_out with from_store_id and to_store_id.
  - Sales, reports, and dashboards often accept a store_id filter (e.g., ManagerDashboardController::getRealtimeStats / getDashboardOverview).
- Front end
  - Pinia app store includes selectedStore and fetchStores.
  - StockManagement and related components allow choosing store(s) and executing transfers.
  - ManagerDashboard exposes period and store filters.
- Files
  - Receipts are already stored under storage/app/private/receipts/{storeId}/... which is multi‑store friendly.

These foundations mean you are more than halfway to multi‑store. What follows are the production details to lock down.


## 2) Decide your multi‑store strategy
There are 3 common approaches. Choose one up‑front:

1. Single database, store‑scoped rows (recommended here)
   - Keep one DB; add store_id to rows that must be scoped by store.
   - Pros: simplest ops; existing code already leans this way.
   - Cons: noisy cross‑store queries unless scoped carefully.

2. Single database, multiple schemas (per store)
   - Each store in its own schema; migration complexity increases.
   - Not natively handled by Laravel’s default stack.

3. Multi‑tenant (database per store)
   - Each store gets its own database and connections resolved at request time.
   - Best isolation; most operational overhead; requires a tenancy package and significant refactors.

This repository is optimized for Option 1.


## 3) Data model audit: add store_id where needed
Make a pass through domain tables and mark which need store scoping vs global. Suggested defaults:

- Must be per‑store (add store_id if missing):
  - sales (often already present in similar repos; confirm yours).
  - payments (ensure store_id and link to sale’s store_id).
  - cash sessions/register logs.
  - purchase_orders / goods_receipts (if you use them).
  - discounts/promotions that vary by store (optional but common).

- Already per‑store:
  - product_store (pivot with stock per store).
  - stock_movements (store_id, from_store_id, to_store_id).

- Likely global (no store_id):
  - products (global catalog; price can be overridden per store if needed via product_store pricing fields or a product_store_overrides table).
  - categories, suppliers, global settings, loyalty program definitions.
  - customers can be global; if you need store‑local CRM, add a customer_store pivot for preferences or blacklists.
  - users are global with role/permission; assign per‑store access via a user_store pivot (see next section).

Migration checklist (single‑DB strategy):
- Ensure sales has a foreignId('store_id')->constrained('stores').
- Ensure payments has a foreignId('store_id')->constrained('stores') and matches sale->store_id on creation.
- If you use sequences like receipt numbers, add a store‑scoped sequence table or store‑scoped counters in settings.


## 4) Authorization and access control (critical)
Implement store‑aware authorization so users only see/manipulate allowed stores.

- User–Store assignment
  - Create a user_store pivot (user_id, store_id, role_override?, primary_store?).
  - For managers/cashiers, require at least one store assignment.
  - Admins can be “all stores” by policy.

- Policies/Gates
  - Add helper methods in User model:
    - canAccessStore(Store $store)
    - accessibleStoreIds(): array
  - Update controllers/services to use these when applying store filters.

- Route middleware for current store context
  - Create middleware SetCurrentStore that resolves the current store via:
    - Subdomain (e.g., {store}.example.com), OR
    - Request header X-Store-Id, OR
    - Auth user’s selected/primary store, OR
    - Query param ?store_id=...
  - Validate the user has access to that store; fallback or 403 if not.
  - Put the resolved store on the request (request()->attributes->set('store_id', ...)) and/or in a singleton StoreContext service.

- Front end
  - Continue using appStore.selectedStore, but ensure all API calls either:
    - pass store_id as query param, or
    - rely on middleware/headers (e.g., axios default header X-Store-Id).


## 5) Query scoping patterns (avoid leaks)
In services and controllers:
- Always apply store scope for per‑store data: where('store_id', $currentStoreId) unless the user is admin and explicitly requests “All Stores”.
- Prefer explicit scoping to global scopes to avoid surprising cross‑module effects. If you use global scopes, provide withAllStores() escape hatches.
- For cross‑store reports, require an explicit role (admin) and an explicit filter period + store selection.

Helpers you can add:
- A trait StoreScopedQueries with helpers: scopeForStore($query, $storeId), scopeForUserStores($query, $user).
- An App\Support\StoreContext service to read the active store_id safely from middleware, header, or user preference.


## 6) Concurrency and consistency
- Stock adjustments and transfers already use DB transactions; keep it that way.
- Add database constraints:
  - Unique(product_id, store_id) on product_store pivot.
  - Foreign keys for all store_id fields with cascade/null on delete as appropriate.

- Sequence numbers per store
  - Receipt/order codes should be unique per store (e.g., {STORECODE}-{YYYYMMDD}-{SEQ}). Maintain counters per store in a table or use a safe, atomic generator.


## 7) Pricing and catalog differences (optional)
If stores can have different prices/promo:
- Add price, promo_price, tax_overrides, etc., to product_store pivot or a dedicated overrides table.
- Update pricing calculators to prefer store override, fallback to product base price.


## 8) Reporting and dashboards
- All analytics endpoints should accept store_id (or All) and validate permissions.
- For “All Stores” aggregations, use UNION/aggregate across allowed stores (for non‑admins, aggregate only allowed stores).
- Cache per store: include store_id in cache keys (e.g., reports:sales:{store}:{date}).

Current code highlights:
- ManagerDashboardController already accepts store_id and composes today_metrics, live_stats, urgent_alerts considering store scope.
- Front‑end ManagerDashboard.vue exposes selected store and period.


## 9) Caching, queues, and scheduled jobs
- Caching: namespace keys by store_id.
- Queues/Jobs: pass store_id in job payloads so workers process in correct context.
- Scheduled tasks: run per store (iterate allowed stores) or global with store awareness.


## 10) File storage and exports
- Continue using per‑store directories (already done for receipts). Apply same for exports/backups: storage/app/private/{store_id}/...
- When generating PDFs/CSVs (e.g., InventoryOverview exports), include store context in file names when relevant.


## 11) Testing strategy for multi‑store
- Unit/Feature tests should seed multiple stores and assert scoping:
  - Given user assigned to Store A only, ensure Store B data is not visible or modifiable.
  - StockService tests: validate per‑store stock sufficiency and transfers between stores.
- Use RefreshDatabase; the project is already wired for SQLite in‑memory via phpunit.xml.
- Example patterns (Pest):
  - it('limits sales listing to user’s stores', ...)
  - it('transfers stock between stores and updates pivots atomically', ...)


## 12) Front‑end integration checklist
- Pinia app store already holds selectedStore and fetches stores.
- Ensure axios applies the store context consistently:
  - Either pass ?store_id=appStore.selectedStore in requests that need it, or
  - Set axios defaults.headers.common['X-Store-Id'] = appStore.selectedStore (and update on change).
- Guard UI routes: if user lacks access to selected store, prompt to pick an allowed store.


## 13) Security and validation
- Validate store_id on all write APIs (create/update/transfer) using exists:stores,id.
- Cross‑check user’s permission to the provided store_id in controllers/policies.
- Sanitize All Stores aggregations: admin‑only or restricted.


## 14) Rollout plan
1) Schema pass: add missing store_id columns + FKs; add user_store pivot; add unique/product_store index.
2) Policies/middleware: implement SetCurrentStore + User access helpers; add to API route groups.
3) Controller/Service audit: enforce store scoping on all per‑store resources.
4) Front‑end: set/store selected store; ensure requests carry context.
5) Reporting cache and jobs: namespace by store.
6) Tests: add store‑scoped tests; run targeted suite (composer test or php artisan test tests\Feature).


## 15) Quick code snippets (sketches)

Middleware (SetCurrentStore):
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCurrentStore
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $storeId = $request->header('X-Store-Id')
            ?? $request->get('store_id')
            ?? optional($user)->primary_store_id;

        if ($storeId && $user && ! $user->canAccessStoreId($storeId)) {
            abort(403, 'Unauthorized store');
        }

        app()->instance('current.store_id', $storeId);
        return $next($request);
    }
}
```

User helpers:
```php
public function canAccessStoreId(?int $storeId): bool
{
    if ($this->isAdmin() || $storeId === null) return true;
    return $this->stores()->where('stores.id', $storeId)->exists();
}

public function accessibleStoreIds(): array
{
    if ($this->isAdmin()) {
        return \App\Models\Store::pluck('id')->all();
    }
    return $this->stores()->pluck('stores.id')->all();
}
```

Applying scope in a controller method:
```php
$query = Sale::query()->where('status', 'completed');
$storeId = app('current.store_id');
if ($storeId && ! auth()->user()->isAdmin()) {
    $query->where('store_id', $storeId);
}
```


## 16) Common pitfalls to avoid
- Forgetting to include store_id in create/update payloads.
- Returning data across stores due to missing WHERE store_id.
- Global caches that mix stores (always namespace by store).
- Allowing “All Stores” to non‑admins.
- Not passing store_id in background jobs (wrong store context in workers).


## 17) Summary
- This codebase is already multi‑store friendly: per‑store stock, transfers, and dashboard/store selectors exist.
- Choose single‑DB, store‑scoped strategy; fill gaps via store_id columns, user_store access, middleware, and consistent scoping.
- Update APIs, policies, caches, jobs, exports, and tests to be store‑aware.

Use this checklist as your implementation roadmap. Keep changes incremental and run targeted tests while iterating (e.g., php artisan test tests\Feature\StockValidationTest.php -vvv --stop-on-failure).
