# POS‑SuperMarket Edge Cases and What to Take Care Of

This document lists practical edge cases and gotchas for a retail POS built with Laravel + Vue in this repository. It’s aimed at advanced contributors to prevent subtle bugs, data leaks, and production outages.

Use it as a checklist when changing controllers/services, adding features, or designing tests.


## 1) Inventory and Stock Movements

Edge cases:
- Negative stock: reductions/transfer_out that exceed available stock; out‑of‑order writes.
- Adjustment semantics: “adjustment” type should set final stock, not apply delta (already implemented in StockMovementController::adjustProductStock).
- Pivot creation: product_store record may not exist for a product+store; ensure attach before update (implemented).
- Transfer pairing: ensure both transfer_out and transfer_in are created atomically in one transaction; on failure, none should persist (implemented with DB::transaction).
- Orphaned transfers: transfer_out created but transfer_in missing due to failure; avoid by storing both in a single transaction.
- Enum mismatches: controller code comparing string type vs enum value; use App\Enums\StockMovementType consistently (updated in stats code).
- Concurrency: simultaneous updates to the same product+store pivot can race; rely on DB transactions and consider row‑level locks if high contention.
- Soft deletes vs history: deleting a movement should reverse its effect where possible; for adjustments, reversal is not well‑defined (controller logs a warning).
- Statistics date source: some rows may miss occurred_at; fallback to created_at for summaries (implemented in stats).

Careful with:
- Validation of store_id, product_id, type, reason in bulk endpoints (/api/stock-movements/bulk) and transfers.
- Guard against decrease below zero; use max(0, …) consistently.
- Keep product_store unique(product_id, store_id) at DB level (add an index if missing during migrations).

Tests to add/keep:
- Attempt reduction > available stock → 422 with available_stock.
- Bulk create includes mixed types and reasons; ensures pivot created if missing.
- Transfer ACID: if transfer_in fails, transfer_out rolls back.


## 2) Sales and Payments

Edge cases:
- Partial payments; multiple payment methods per sale; refunds vs voids.
- Payment gateway errors/timeouts; idempotency on retried webhooks or client retries.
- Currency/rounding: floating point vs decimal; ensure money fields are decimal(…) and formatted consistently; never sum floats in JS without coercion.
- Status transitions: pending → completed → refunded; ensure inventory adjustments only on completed (and reversed on refund, if model requires).
- Offline/latency: queued receipts/transactions; reconcile on reconnect; prevent duplicate charges.

Careful with:
- Store scoping on payments (store_id should match sale->store_id); report endpoints must filter by store (and user access).
- Avoid exposing full PAN/PII; store tokens/last4 only; redact logs.

Tests:
- Refund reverses inventory and creates audit entries.
- Payment failures do not create sales in completed state.


## 3) Returns/RMAs and Exchanges

Edge cases:
- Return after price change or promotion ended; refund amount vs original line price.
- Return without receipt; require manager approval.
- Partial returns across multiple stores: return must occur at original store to keep stock integrity.
- Condition‑based returns (damaged/expired) should use correct StockMovementReason and not inflate available stock for sale if unsellable.

Careful with:
- For returns, stock movement type should be addition with reason=return (or loss if unsellable).
- Validate sale id and line items; prevent over‑return (quantity > sold).


## 4) Discounts, Taxes, and Pricing

Edge cases:
- Stacking discounts; rounding at line vs receipt level; tax‑inclusive vs exclusive prices.
- Customer tier or store‑specific promotions; time‑boxed promos crossing midnight/timezone boundaries.
- Tax exemptions (customers, categories); variable rates.

Careful with:
- Prefer decimal math in PHP; avoid JS floating drift; in Vue components coerce Number(...) like we did in StoreManagement.vue.
- If per‑store pricing overrides are introduced, ensure fallback to product base price.

Tests:
- Totals should be deterministic; freeze time when calculating promo eligibility.


## 5) Multi‑Store Scoping and Authorization

Edge cases:
- Data leakage across stores due to missing where('store_id', …) in queries.
- Admin “All Stores” aggregations accidentally available to non‑admins.
- Transfers with invalid from/to store or same store.
- Users without any assigned store using endpoints that require a store context.

Careful with:
- Use auth:sanctum consistently for API routes (customers fixed to use sanctum).
- Controllers that accept store_id must check permissions (user canViewReports/canManageInventory and store access).
- For background jobs, include store_id in payloads.

Tests:
- Given a manager for Store A, ensure queries don’t include Store B.
- Transfer validation rejects to_store_id == from_store_id.


## 6) Concurrency, Locking, and Race Conditions

Edge cases:
- Two cashiers selling last unit concurrently; stock goes negative or oversold.
- Simultaneous bulk adjustments.

Careful with:
- Wrap critical updates in DB::transaction and consider SELECT … FOR UPDATE on product_store pivot rows for hot SKUs.
- Retry policy for deadlocks with backoff.

Tests:
- Parallel test or simulated concurrency ensures stock never negative.


## 7) Reporting, Analytics, and Caching

Edge cases:
- Summaries mixing stores due to cache key collisions.
- Date boundaries (UTC vs local timezone) causing off‑by‑one in daily reports.
- Missing occurred_at; rely on created_at fallback as implemented.

Careful with:
- Namespace caches per store_id and date range.
- Long‑running exports should stream/chunk; avoid memory blowups.
- Use integer timestamps or Carbon with explicit timezone.


## 8) Files, Receipts, and Exports

Edge cases:
- File paths without store hierarchy causing clashes.
- PDF/CSV generation with undefined fields (e.g., price nulls) → JS runtime errors (we hardened InventoryOverview exports with defaults/coercion).

Careful with:
- Keep receipts and exports under storage/app/private/receipts/{storeId}/... (already used for receipts).
- Sanitize filenames; include store and date in export names.
- In Vue export code, guard against undefined data and use defaults.


## 9) Authentication, Roles, and Permissions

Edge cases:
- Mixed middleware (auth vs auth:sanctum) causing 401/422 inconsistencies (we aligned customers to sanctum).
- Over‑permissive defaults in User::hasPermission; ensure enums map correctly and fallbacks are safe.

Careful with:
- API routes should be consistent with how front end authenticates (Sanctum).
- Add store‑aware checks (canAccessStore) if you implement user_store pivot.


## 10) Time, Locale, and Rounding

Edge cases:
- Daylight savings time; end‑of‑day rollups; “today” in dashboards vs server timezone.
- Number formatting (toFixed on non‑numbers) causing Vue crashes (fixed in StoreManagement.vue).

Careful with:
- Use Carbon with a defined timezone; avoid implicit server timezone in tests.
- Coerce inputs to numbers on the front end before formatting; prefer backend to compute money totals.


## 11) Performance and Limits

Edge cases:
- N+1 queries on dashboards (recent activity loading relations for sales, stock movements).
- Large bulk operations (100+) causing long transactions and lock contention.

Careful with:
- Add indexes on hot columns: stock_movements(product_id, store_id, type, occurred_at), product_store(product_id, store_id).
- Paginate and limit requests; validate bulk size (we capped to 100 movements per bulk call).


## 12) Observability and Auditing

Edge cases:
- Silent failures in observers; partial logs.

Careful with:
- Use structured logs around stock operations (already in controllers); log movement IDs, deltas, user_id.
- Consider adding an audit trail table for critical events.


## 13) Front‑End Data Safety

Edge cases:
- Unhandled errors crash render (e.g., toFixed on string/undefined; addressed in components).
- Missing reactive defaults leading to Vue warnings.
- Over‑reliance on client state for security decisions.

Careful with:
- Always default optional fields; use optional chaining and Number(...) coercion.
- Do not trust client store_id; validate on server.


## 14) Testing Guidance (Pest/Laravel)

- Prefer RefreshDatabase; tests in this repo already use it. The suite assumes SQLite in-memory.
- Run targeted tests while iterating:
  - php artisan test tests\Feature\StockValidationTest.php -vvv --stop-on-failure
  - php artisan test tests\Feature\Api\SaleControllerTest.php
- For new features touching money or time, freeze time (Carbon::setTestNow) and use exact decimals.
- Mock external services (Stripe, Dompdf, Excel) in feature tests.


## 15) Quick Developer Checklist

Before merging changes that touch inventory/sales:
- [ ] All write endpoints validate store_id/product_id/type/quantity and user authorization.
- [ ] Adjustments set final stock; deltas applied only for addition/reduction/transfer.
- [ ] Transfer is transactional; no orphaned movements.
- [ ] No negative stock; reducers clamp to >= 0 or return 422.
- [ ] Queries are store‑scoped unless explicitly cross‑store for admins.
- [ ] Money as decimals; front end coerces numbers before formatting.
- [ ] Cache/report keys include store and date range.
- [ ] Exports/receipts include store context; guard against undefined data in Vue.
- [ ] Routes use auth:sanctum consistently for API accessed by SPA.
- [ ] Added/updated tests for critical paths.


## 16) References (in this repo)

- StockMovementController: bulkStore, transfer, statistics, adjustProductStock, reverseStockAdjustment.
- ManagerDashboardController: getDashboardOverview, getRealtimeStats (store‑aware metrics).
- Vue Components: InventoryOverview.vue exports safety; StoreManagement.vue numeric coercion; ManagerDashboard.vue period/store filters.
- Routes: routes/api.php using auth:sanctum for protected resources.
- Enums: App\Enums\StockMovementType, App\Enums\StockMovementReason for consistent typing.

Use this document with docs/MULTI_STORE_GUIDE.md to plan robust multi‑store deployments and safe change management.
