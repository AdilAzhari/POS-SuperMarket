<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessLoyaltyPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:process-loyalty-points {--expire-days=365 : Days after which points expire} {--customer= : Process specific customer ID} {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process loyalty points: expire old points, calculate tier upgrades, and send birthday rewards';

    public function __construct(
        private readonly LoyaltyService $loyaltyService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎯 Starting loyalty points processing...');

        try {
            $expireDays = (int) $this->option('expire-days');
            $customerId = $this->option('customer');
            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->warn('🧪 DRY RUN MODE - No changes will be made');
            }

            $stats = [
                'points_expired' => 0,
                'customers_processed' => 0,
                'tier_upgrades' => 0,
                'birthday_rewards' => 0,
                'points_amount_expired' => 0,
            ];

            if ($customerId) {
                $customer = Customer::query()->findOrFail($customerId);
                $stats = $this->processCustomer($customer, $expireDays, $dryRun, $stats);
            } else {
                $stats = $this->processAllCustomers($expireDays, $dryRun, $stats);
            }

            $this->displaySummary($stats, $dryRun);

            $this->info('✅ Loyalty points processing completed successfully!');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Failed to process loyalty points: {$e->getMessage()}");
            Log::error('Loyalty points processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function processAllCustomers(int $expireDays, bool $dryRun, array $stats): array
    {
        $customers = Customer::query()
            ->where('status', 'active')
            ->whereHas('loyaltyTransactions')
            ->get();

        $this->info("👥 Processing {$customers->count()} customers...");

        $progressBar = $this->output->createProgressBar($customers->count());
        $progressBar->start();

        foreach ($customers as $customer) {
            $stats = $this->processCustomer($customer, $expireDays, $dryRun, $stats);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $stats;
    }

    private function processCustomer(Customer $customer, int $expireDays, bool $dryRun, array $stats): array
    {
        $stats['customers_processed']++;

        // 1. Expire old points
        $expiredStats = $this->expireOldPoints($customer, $expireDays, $dryRun);
        $stats['points_expired'] += $expiredStats['count'];
        $stats['points_amount_expired'] += $expiredStats['amount'];

        // 2. Check for tier upgrades
        if ($this->checkTierUpgrade($customer, $dryRun)) {
            $stats['tier_upgrades']++;
        }

        // 3. Process birthday rewards
        if ($this->processBirthdayRewards($customer, $dryRun)) {
            $stats['birthday_rewards']++;
        }

        return $stats;
    }

    private function expireOldPoints(Customer $customer, int $expireDays, bool $dryRun): array
    {
        $expirationDate = now()->subDays($expireDays);

        $expiredTransactions = LoyaltyTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'earned')
            ->where('created_at', '<=', $expirationDate)
            ->where('points', '>', 0)
            ->whereNull('expired_at')
            ->get();

        $stats = [
            'count' => $expiredTransactions->count(),
            'amount' => $expiredTransactions->sum('points'),
        ];

        if ($stats['count'] > 0 && ! $dryRun) {
            DB::transaction(function () use ($customer, $expiredTransactions) {
                // Mark transactions as expired
                $expiredTransactions->each(function ($transaction) {
                    $transaction->update(['expired_at' => now()]);
                });

                // Create expiration transaction
                if ($expiredTransactions->sum('points') > 0) {
                    LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'type' => 'expired',
                        'points' => -$expiredTransactions->sum('points'),
                        'description' => "Points expired after 365 days ({$expiredTransactions->count()} transactions)",
                        'reference_type' => 'system',
                        'reference_id' => null,
                    ]);
                }
            });

            Log::info('Points expired for customer', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'expired_points' => $stats['amount'],
                'transaction_count' => $stats['count'],
            ]);
        }

        return $stats;
    }

    private function checkTierUpgrade(Customer $customer, bool $dryRun): bool
    {
        // Get current points balance
        $currentPoints = $customer->loyaltyTransactions()
            ->whereNull('expired_at')
            ->sum('points');

        // Define tier thresholds
        $tiers = [
            'bronze' => 0,
            'silver' => 1000,
            'gold' => 5000,
            'platinum' => 10000,
        ];

        // Determine new tier
        $newTier = 'bronze';
        foreach ($tiers as $tier => $threshold) {
            if ($currentPoints >= $threshold) {
                $newTier = $tier;
            }
        }

        // Check if upgrade is needed
        $currentTier = $customer->loyalty_tier ?? 'bronze';
        if ($newTier !== $currentTier) {
            if (! $dryRun) {
                $customer->update(['loyalty_tier' => $newTier]);

                // Award tier upgrade bonus
                $bonusPoints = match ($newTier) {
                    'silver' => 100,
                    'gold' => 250,
                    'platinum' => 500,
                    default => 0
                };

                if ($bonusPoints > 0) {
                    LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'type' => 'bonus',
                        'points' => $bonusPoints,
                        'description' => "Tier upgrade bonus: {$currentTier} → {$newTier}",
                        'reference_type' => 'tier_upgrade',
                        'reference_id' => null,
                    ]);
                }

                Log::info('Customer tier upgraded', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'old_tier' => $currentTier,
                    'new_tier' => $newTier,
                    'bonus_points' => $bonusPoints,
                ]);
            }

            return true;
        }

        return false;
    }

    private function processBirthdayRewards(Customer $customer, bool $dryRun): bool
    {
        if (! $customer->date_of_birth) {
            return false;
        }

        $birthday = $customer->date_of_birth->setYear(now()->year);
        $isWithinBirthdayWeek = now()->diffInDays($birthday, false) >= -3 &&
                               now()->diffInDays($birthday, false) <= 3;

        if (! $isWithinBirthdayWeek) {
            return false;
        }

        // Check if birthday reward already given this year
        $existingReward = LoyaltyTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'birthday')
            ->whereYear('created_at', now()->year)
            ->exists();

        if ($existingReward) {
            return false;
        }

        if (! $dryRun) {
            $birthdayPoints = 200; // Birthday bonus

            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'type' => 'birthday',
                'points' => $birthdayPoints,
                'description' => 'Birthday bonus points',
                'reference_type' => 'birthday',
                'reference_id' => null,
            ]);

            Log::info('Birthday reward given', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'points' => $birthdayPoints,
                'birthday' => $birthday->format('Y-m-d'),
            ]);
        }

        return true;
    }

    private function displaySummary(array $stats, bool $dryRun): void
    {
        $this->newLine();
        $this->info('📊 Loyalty Points Processing Summary');
        $this->line(str_repeat('=', 50));

        $this->table(
            ['Metric', 'Count'],
            [
                ['Customers Processed', number_format($stats['customers_processed'])],
                ['Points Expired (transactions)', number_format($stats['points_expired'])],
                ['Points Expired (amount)', number_format($stats['points_amount_expired'])],
                ['Tier Upgrades', number_format($stats['tier_upgrades'])],
                ['Birthday Rewards', number_format($stats['birthday_rewards'])],
            ]
        );

        if ($dryRun) {
            $this->warn('⚠️  This was a dry run - no actual changes were made');
        }

        $this->newLine();
    }
}
