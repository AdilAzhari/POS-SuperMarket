<?php

declare(strict_types=1);

namespace App\Actions\Loyalty;

use App\Models\Sale;
use App\Services\LoyaltyService;
use Exception;

final readonly class ApplyLoyaltyDiscountAction
{
    public function __construct(
        private LoyaltyService $loyaltyService
    ) {}

    /**
     * Apply loyalty discount to a sale
     */
    public function execute(Sale $sale, int $pointsToRedeem): array
    {
        if (! $sale->customer) {
            throw new Exception('Sale must have a customer to apply loyalty discount');
        }

        if ($sale->customer->loyalty_points < $pointsToRedeem) {
            throw new Exception('Customer does not have enough loyalty points');
        }

        if ($pointsToRedeem <= 0) {
            throw new Exception('Points to redeem must be greater than 0');
        }

        return $this->loyaltyService->applyLoyaltyDiscount($sale, $pointsToRedeem);
    }
}
