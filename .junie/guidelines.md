Project development guidelines for POS-SuperMarket

Scope
- Audience: Advanced Laravel + Vue developers contributing to this repository.
- Goal: Capture project-specific conventions, setup, and testing practice relevant to this codebase.

Build and configuration
1) PHP/Laravel back end
- Runtime: PHP ^8.2 (currently 8.4 works), Laravel 12.x, Composer 2.x.
- Install PHP deps: composer install
- Environment file: copy .env.example to .env and set
  - APP_KEY: php artisan key:generate
  - DB: local development commonly uses MySQL (Laragon) for runtime, but tests are configured to use SQLite in-memory via phpunit.xml.
  - QUEUE_CONNECTION=sync for local.
  - Third-party: packages present include dompdf/dompdf, maatwebsite/excel, inertiajs/inertia-laravel, sanctum, ziggy, stripe/stripe-php. Only configure services you actually exercise locally (Stripe keys not required for boot unless used by code path).
- Database: apply migrations as needed
  - php artisan migrate
  - Seeders available (DatabaseSeeder, StockMovementSeeder, etc.). For demo data: php artisan db:seed
- Local dev helpers
  - Use composer dev to start an integrated dev stack (PHP server, queue listener, Pail, Vite) concurrently. Requires Node installed and npx available.
    - composer run dev

2) Front end (Vite + Vue 3)
- Install Node deps: npm ci (or npm install)
- Dev server: npm run dev (Vite) — already included in composer dev script.
- Build: npm run build

Testing: how this project is wired
- Test runner: Laravel’s artisan test (Pest 3.x under the hood). phpunit.xml provides environment for in-memory SQLite.
- Composer script: composer test runs:
  1) php artisan config:clear
  2) php artisan test
- Parallel tests: php artisan test --parallel is supported by Pest/Laravel stack, but be mindful of factories touching shared resources.
- Database for tests: phpunit.xml sets DB_CONNECTION=sqlite and DB_DATABASE=:memory:. Most tests rely on RefreshDatabase trait which will run the migrations into an in-memory connection automatically. No additional DB service is required for tests.

Running tests effectively in this repository
- Full suite: composer test
  - Note: The full suite currently includes API feature tests (e.g., tests/Feature/Api/SaleControllerTest.php) that hit authenticated routes using Sanctum::actingAs and expect certain domain behavior. If your local env differs (routes/auth/migrations), you may see failures — prefer running targeted tests during iteration.
- Targeted file or directory:
  - Single file: php artisan test tests\Unit\SmokeTest.php
  - Directory: php artisan test tests\Feature
- Name filter:
  - php artisan test --filter=StockValidation
- Stop on failure: add --stop-on-failure to shorten feedback.

Creating a new test (project conventions)
- Pest is used (function-style tests with uses() and expectations API). Example unit test template:
  - File: tests/Unit/MyExampleTest.php
    
    <?php
    test('math works', function () {
        expect(1 + 1)->toBe(2);
    });
    
- Feature test conventions:
  - Use Illuminate\Foundation\Testing\RefreshDatabase when you need DB state.
  - For authenticated API tests use Sanctum::actingAs(User::factory()->create()).
  - Prefer factories over manual model creation; model factories are available under database/factories.
  - API assertions use postJson/getJson and ->assertJson/->assertJsonStructure.
- Example minimal Feature test that aligns with existing patterns:
    
    <?php
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use App\Models\User;
    
    uses(RefreshDatabase::class);
    
    it('responds to home route', function () {
        // If your app has an authenticated dashboard route, act as a user
        $this->actingAs(User::factory()->create());
        $response = $this->get('/');
        $response->assertStatus(200);
    });
    
  - Note: Replace '/' with an actual route present in routes/web.php or routes/api.php in this repo when you add such a test.

What’s already covered by existing tests
- Stock service and stock-related API endpoints are covered in tests/Feature/StockValidationTest.php.
- Sales API happy-path and validation scenarios live in tests/Feature/Api/SaleControllerTest.php.
- Service-layer tests are under tests/Feature/Services (ProductServiceTest.php, SaleServiceTest.php, StockServiceTest.php).
- These tests assume:
  - Relationships between Product, Store, and pivot with stock and low_stock_threshold fields.
  - Auth via Sanctum for protected endpoints.
  - Inertia/Vue layers are not required for test execution (tests are HTTP/API focused).

Adding tests that touch the database
- Keep migrations atomic and idempotent; use RefreshDatabase to migrate the in-memory SQLite schema per test case.
- If you need seed data, prefer factories inside tests rather than running global seeders to keep tests isolated and fast.
- When asserting DB state, use $this->assertDatabaseHas() with model tables.

Domain-specific tips for this project
- StockService is a central dependency for stock checks (validateStockSufficiency, checkLowStock, getProductStockSummary, validateMultipleProducts). When changing StockService, review the tests in tests/Feature/StockValidationTest.php and tests/Feature/Services/StockServiceTest.php to quickly assess the impact.
- API endpoints exercised by tests:
  - POST /api/stock-movements/validate
  - POST /api/stock-movements/check-low-stock
  - GET  /api/products/{product}/stock-summary
  - Sales endpoints under /api/sales
  Update routes/api.php and controllers consistently with these contracts.
- Authentication/authorization:
  - Tests commonly use actingAs() or Sanctum::actingAs(). Ensure middleware and guards are consistent so tests don’t depend on CSRF; prefer API routes for JSON tests.
- Testing with external packages (Dompdf, Excel, Stripe):
  - Mock external services; do not call network-bound code in tests.

Demonstrated working test example
- A smoke test was created and executed locally to verify the testing toolchain without touching the domain:
  - File: tests/Unit/SmokeTest.php
  - Command executed: php artisan test tests\Unit\SmokeTest.php
  - Result: 1 passed (1 assertions)
- To keep the repository clean, such smoke files should not be committed permanently; they are useful for quick local verification.

Code style and tooling
- PHP: Laravel Pint is available (composer require-dev laravel/pint). Run vendor/bin/pint or ./vendor/bin/pint fix.
- JS/TS: ESLint and Tailwind configs are present. Run npm run lint if wired, or configure per team standards.
- Static analysis: Add phpstan/psalm if desired; not currently included.

Debugging tips
- Use storage/logs/laravel.log; enable APP_DEBUG=true in .env for local.
- For HTTP debugging, Laravel Pail is included (composer require-dev laravel/pail). It is part of composer dev script and can stream logs.
- When feature tests fail locally, run them with -vvv and without parallelization to get clearer traces:
  - php artisan test tests\Feature\StockValidationTest.php -vvv --stop-on-failure

CI and deterministic tests
- Favor deterministic factories (explicit values) in tests that assert exact numbers (e.g., totals/stock remaining).
- Avoid using now() without freezing time (use Carbon::setTestNow()).

Maintainer checklist before merging
- Run targeted domain tests covering the changed modules.
- Run full suite if feasible: composer test
- Run Pint and front-end build to ensure no obvious breakages: vendor\bin\pint && npm run build
