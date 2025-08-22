<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class PaymentService
{
    private ?StripeClient $stripe = null;

    public function __construct()
    {
        // Initialize Stripe only if the package is available
        if (class_exists('Stripe\StripeClient')) {
            $stripeKey = config('services.stripe.secret');
            if ($stripeKey) {
                $this->stripe = new StripeClient($stripeKey);
            }
        }
    }

    /**
     * Process payment for a sale
     */
    public function processPayment(Sale $sale, array $paymentData): Payment
    {
        return DB::transaction(function () use ($sale, $paymentData) {
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
     * Process Stripe payment
     *
     * @throws ApiErrorException
     */
    private function processStripePayment(Payment $payment, array $data): Payment
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($payment->amount * 100), // Convert to cents
                'currency' => strtolower($payment->currency),
                'payment_method' => $data['payment_method_id'] ?? null,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => url('/payment/return'),
                'metadata' => [
                    'sale_id' => $payment->sale_id,
                    'payment_id' => $payment->id,
                    'store_id' => $payment->store_id,
                ],
            ]);

            $fee = $this->calculateStripeFee($payment->amount);

            $payment->update([
                'gateway_transaction_id' => $paymentIntent->id,
                'gateway_response' => $paymentIntent->toArray(),
                'fee' => $fee,
                'net_amount' => $payment->amount - $fee,
                'status' => $paymentIntent->status === 'succeeded' ? PaymentStatus::COMPLETED : PaymentStatus::PROCESSING,
                'processed_at' => $paymentIntent->status === 'succeeded' ? now() : null,
            ]);

            if ($paymentIntent->status === 'succeeded') {
                Log::info('Stripe payment completed', [
                    'payment_id' => $payment->id,
                    'intent_id' => $paymentIntent->id,
                ]);
            }

            return $payment;

        } catch (Exception $e) {
            $payment->markAsFailed($e->getMessage());

            Log::error('Stripe payment failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
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
                    'gateway_reference' => 'CARD-'.strtoupper(uniqid()),
                    'gateway_transaction_id' => 'TXN_'.time().'_'.rand(1000, 9999),
                    'card_last_four' => substr(str_replace(' ', '', $cardNumber), -4),
                    'card_brand' => $cardBrand,
                    'card_exp_month' => $data['exp_month'] ?? null,
                    'card_exp_year' => $data['exp_year'] ?? null,
                ]);

                Log::info('Card payment completed', [
                    'payment_id' => $payment->id,
                    'card_brand' => $cardBrand,
                    'last_four' => substr(str_replace(' ', '', $cardNumber), -4),
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

            $tngResponse = $this->simulateTngPayment($data);

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
     * Calculate Stripe processing fee (2.9% + MYR 0.50)
     */
    private function calculateStripeFee(float $amount): float
    {
        return round(($amount * 0.029) + 0.50, 2);
    }

    /**
     * Calculate card processing fee
     */
    private function calculateCardFee(float $amount, PaymentMethod $paymentMethod): float
    {
        $rate = match ($paymentMethod) {
            PaymentMethod::CARD => 0.029,      // 2.9%
            PaymentMethod::DIGITAL => 0.025,   // 2.5%
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

        if (preg_match('/^4/', $cleanNumber)) {
            return 'visa';
        } elseif (preg_match('/^5[1-5]|^2[2-7]/', $cleanNumber)) {
            return 'mastercard';
        } elseif (preg_match('/^3[47]/', $cleanNumber)) {
            return 'amex';
        } elseif (preg_match('/^6(?:011|5)/', $cleanNumber)) {
            return 'discover';
        } elseif (preg_match('/^3[0689]/', $cleanNumber)) {
            return 'diners';
        } elseif (preg_match('/^35/', $cleanNumber)) {
            return 'jcb';
        }

        return 'card';
    }

    /**
     * Simulate card payment (replace with real bank integration)
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

        return rand(1, 100) <= $successRate;
    }

    /**
     * Simulate TNG payment (replace with real TNG integration)
     */
    private function simulateTngPayment(array $data): array
    {
        $success = rand(1, 100) <= 98; // 98% success rate

        return [
            'success' => $success,
            'reference' => $success ? 'TNG'.time().rand(1000, 9999) : null,
            'transaction_id' => $success ? 'TXN'.uniqid() : null,
            'message' => $success ? 'Payment successful' : 'Insufficient balance or payment declined',
        ];
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
                PaymentMethod::CASH => $this->refundCashPayment($payment, $refundAmount),
                default => $this->refundGenericPayment($payment, $refundAmount),
            };
        } catch (Exception $e) {
            Log::error('Payment refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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

    private function refundCashPayment(Payment $payment, float $amount): bool
    {
        // Cash refunds are handled manually at POS
        $payment->update([
            'status' => PaymentStatus::REFUNDED,
            'notes' => 'Cash refund processed manually at POS',
        ]);

        return true;
    }

    private function refundGenericPayment(Payment $payment, float $amount): bool
    {
        // For other payment methods, mark as refunded manually
        $payment->update([
            'status' => PaymentStatus::REFUNDED,
            'notes' => 'Manual refund processed',
        ]);

        return true;
    }
}
