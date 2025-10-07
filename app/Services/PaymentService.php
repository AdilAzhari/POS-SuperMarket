<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Random\RandomException;
use Stripe\StripeClient;
use Throwable;

final class PaymentService
{
    private ?StripeClient $stripe = null;

    public function __construct()
    {
        // Initialize Stripe only if the package is available
        if (class_exists(StripeClient::class)) {
            $stripeKey = config('services.stripe.secret');
            if ($stripeKey) {
                $this->stripe = new StripeClient($stripeKey);
            }
        }
    }

    /**
     * Process payment for a sale
     *
     * @throws Throwable
     */
    public function processPayment(Sale $sale, array $paymentData): Payment
    {
        return DB::transaction(function () use ($sale, $paymentData): Payment {
            $payment = Payment::query()->create([
                'sale_id' => $sale->id,
                'store_id' => $sale->store_id,
                'user_id' => $sale->cashier_id,
                'payment_method' => $paymentData['method'],
                'amount' => $sale->total,
                'currency' => $paymentData['currency'] ?? 'MYR',
                'status' => PaymentStatus::PENDING,
            ]);

            return match (PaymentMethod::from($paymentData['method'])) {
                PaymentMethod::CASH => $this->processCashPayment($payment, $paymentData),
                PaymentMethod::CARD, PaymentMethod::DIGITAL => $this->processCardPayment($payment, $paymentData),
                PaymentMethod::TOUCHNGO => $this->processTngPayment($payment, $paymentData),
                default => throw new InvalidArgumentException("Unsupported payment method: {$paymentData['method']}"),
            };
        });
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Payment $payment, ?float $amount = null): bool
    {
        $refundAmount = $amount ?? $payment->amount;

        try {
            return match ($payment->payment_method) {
                PaymentMethod::CARD => $this->refundStripePayment($payment, $refundAmount),
                PaymentMethod::CASH => $this->refundCashPayment($payment),
                default => $this->refundGenericPayment($payment),
            };
        } catch (Exception $e) {
            Log::error('Payment refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process cash payment (immediate completion)
     */
    private function processCashPayment(Payment $payment, array $data): Payment
    {
        $payment->update([
            'status' => PaymentStatus::COMPLETED,
            'processed_at' => now(),
            'cash_received' => $data['cash_received'] ?? $payment->amount,
            'change_amount' => $data['change_amount'] ?? 0,
            'notes' => 'Cash payment processed at POS',
        ]);

        Log::info('Cash payment processed', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'cash_received' => $data['cash_received'] ?? $payment->amount,
            'change' => $data['change_amount'] ?? 0,
        ]);

        return $payment;
    }

    /**
     * Process card payment (via bank gateway or other card processor)
     *
     * @throws Exception
     */
    private function processCardPayment(Payment $payment, array $data): Payment
    {
        try {
            // Simulate card payment processing
            // In real implementation, integrate with bank gateway or card processor

            $cardNumber = $data['card_number'] ?? '';
            $cardBrand = $this->detectCardBrand($cardNumber);
            $isSuccess = $this->simulateCardPayment($data);

            if ($isSuccess) {
                $fee = $this->calculateCardFee($payment->amount, $payment->payment_method);

                $payment->update([
                    'status' => PaymentStatus::COMPLETED,
                    'processed_at' => now(),
                    'fee' => $fee,
                    'net_amount' => $payment->amount - $fee,
                    'gateway_reference' => 'CARD-'.mb_strtoupper(uniqid()),
                    'gateway_transaction_id' => 'TXN_'.time().'_'.random_int(1000, 9999),
                    'card_last_four' => mb_substr(str_replace(' ', '', $cardNumber), -4),
                    'card_brand' => $cardBrand,
                    'card_exp_month' => $data['exp_month'] ?? null,
                    'card_exp_year' => $data['exp_year'] ?? null,
                ]);

                Log::info('Card payment completed', [
                    'payment_id' => $payment->id,
                    'card_brand' => $cardBrand,
                    'last_four' => mb_substr(str_replace(' ', '', $cardNumber), -4),
                ]);
            } else {
                $payment->markAsFailed('Card payment declined');

                Log::warning('Card payment declined', [
                    'payment_id' => $payment->id,
                    'card_brand' => $cardBrand,
                ]);
            }

            return $payment;

        } catch (Exception $e) {
            $payment->markAsFailed($e->getMessage());

            Log::error('Card payment failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process TNG eWallet payment
     *
     * @throws Exception
     */
    private function processTngPayment(Payment $payment, array $data): Payment
    {
        try {
            // Simulate TNG payment processing
            // In real implementation, integrate with TNG API

            $tngResponse = $this->simulateTngPayment();

            if ($tngResponse['success']) {
                $fee = $this->calculateTngFee($payment->amount);

                $payment->update([
                    'status' => PaymentStatus::COMPLETED,
                    'processed_at' => now(),
                    'fee' => $fee,
                    'net_amount' => $payment->amount - $fee,
                    'tng_phone' => $data['phone'] ?? null,
                    'tng_reference' => $tngResponse['reference'],
                    'gateway_reference' => $tngResponse['transaction_id'],
                    'gateway_response' => $tngResponse,
                ]);

                Log::info('TNG payment completed', [
                    'payment_id' => $payment->id,
                    'tng_ref' => $tngResponse['reference'],
                ]);
            } else {
                $payment->markAsFailed($tngResponse['message']);

                Log::warning('TNG payment failed', [
                    'payment_id' => $payment->id,
                    'reason' => $tngResponse['message'],
                ]);
            }

            return $payment;

        } catch (Exception $e) {
            $payment->markAsFailed($e->getMessage());

            Log::error('TNG payment failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate card processing fee
     */
    private function calculateCardFee(float $amount, PaymentMethod $paymentMethod): float
    {
        $rate = match ($paymentMethod) {
            PaymentMethod::CARD => 0.029,      // 2.9%
            // 2.5%
            PaymentMethod::BANK_TRANSFER => 0.010, // 1.0%
            default => 0.025,
        };

        return round($amount * $rate, 2);
    }

    /**
     * Calculate TNG processing fee (1.5%)
     */
    private function calculateTngFee(float $amount): float
    {
        return round($amount * 0.015, 2);
    }

    /**
     * Detect card brand from card number
     */
    private function detectCardBrand(string $cardNumber): string
    {
        $cleanNumber = preg_replace('/\D/', '', $cardNumber);
        if (str_starts_with((string) $cleanNumber, '4')) {
            return 'visa';
        }
        if (preg_match('/^5[1-5]|^2[2-7]/', (string) $cleanNumber)) {
            return 'mastercard';
        }
        if (preg_match('/^3[47]/', (string) $cleanNumber)) {
            return 'amex';
        }
        if (preg_match('/^6(?:011|5)/', (string) $cleanNumber)) {
            return 'discover';
        }
        if (preg_match('/^3[0689]/', (string) $cleanNumber)) {
            return 'diners';
        }

        if (str_starts_with((string) $cleanNumber, '35')) {
            return 'jcb';
        }

        return 'card';
    }

    /**
     * Simulate card payment (replace with real bank integration)
     *
     * @throws RandomException
     */
    private function simulateCardPayment(array $data): bool
    {
        // Simulate different success rates based on card type
        $cardNumber = $data['card_number'] ?? '';
        $cardBrand = $this->detectCardBrand($cardNumber);

        // Different success rates for testing
        $successRates = [
            'visa' => 98,
            'mastercard' => 97,
            'amex' => 96,
            'discover' => 95,
            'default' => 94,
        ];

        $successRate = $successRates[$cardBrand] ?? $successRates['default'];

        return random_int(1, 100) <= $successRate;
    }

    /**
     * Simulate TNG payment (replace with real TNG integration)
     *
     * @throws RandomException
     */
    private function simulateTngPayment(): array
    {
        $success = random_int(1, 100) <= 98;

        // 98% success rate
        return [
            'success' => $success,
            'reference' => $success ? 'TNG'.time().random_int(1000, 9999) : null,
            'transaction_id' => $success ? 'TXN'.uniqid() : null,
            'message' => $success ? 'Payment successful' : 'Insufficient balance or payment declined',
        ];
    }

    private function refundStripePayment(Payment $payment, float $amount): bool
    {
        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $payment->gateway_transaction_id,
                'amount' => (int) ($amount * 100), // Convert to cents
            ]);

            $payment->update(['status' => PaymentStatus::REFUNDED]);

            Log::info('Stripe payment refunded', [
                'payment_id' => $payment->id,
                'refund_id' => $refund->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Stripe refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function refundCashPayment(Payment $payment): bool
    {
        // Cash refunds are handled manually at POS
        $payment->update([
            'status' => PaymentStatus::REFUNDED,
            'notes' => 'Cash refund processed manually at POS',
        ]);

        return true;
    }

    private function refundGenericPayment(Payment $payment): bool
    {
        // For other payment methods, mark as refunded manually
        $payment->update([
            'status' => PaymentStatus::REFUNDED,
            'notes' => 'Manual refund processed',
        ]);

        return true;
    }
}
