<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    /**
     * Get all payments with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'payment_method' => 'string',
            'status' => 'string',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        $query = Payment::with(['sale', 'store', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by payment method
        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $payments = $query->paginate($request->per_page ?? 20);

        return response()->json($payments);
    }

    /**
     * Process a payment for a sale
     */
    public function processPayment(Request $request): JsonResponse
    {
        $paymentMethods = collect(PaymentMethod::cases())->pluck('value')->toArray();

        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'method' => ['required', Rule::in($paymentMethods)],
            'currency' => 'string|size:3|in:MYR,USD,SGD',

            // Stripe specific
            'payment_method_id' => 'required_if:method,card|string',

            // Card specific
            'card_number' => 'required_if:method,card,digital|string|min:13|max:19',
            'exp_month' => 'required_if:method,card,digital|integer|between:1,12',
            'exp_year' => 'required_if:method,card,digital|integer|min:2024',
            'cvv' => 'required_if:method,card,digital|string|min:3|max:4',
            'cardholder_name' => 'required_if:method,card,digital|string|max:255',

            // TNG specific
            'phone' => 'required_if:method,tng|string|regex:/^(\+?6?01)[0-9]{8,9}$/',

            // Cash specific
            'cash_received' => 'numeric|min:0',
            'change_amount' => 'numeric|min:0',
        ]);

        try {
            $sale = Sale::query()->findOrFail($request->sale_id);

            // Check if sale is already paid
            if ($sale->status === SaleStatus::COMPLETED && $sale->payments()->where('status', PaymentStatus::COMPLETED)->exists()) {
                return response()->json([
                    'message' => 'Sale is already paid',
                    'error' => 'ALREADY_PAID',
                ], 400);
            }

            $paymentData = [
                'method' => $request->method,
                'currency' => $request->currency ?? 'MYR',
                'payment_method_id' => $request->payment_method_id,
                'card_number' => $request->card_number,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvv' => $request->cvv,
                'cardholder_name' => $request->cardholder_name,
                'phone' => $request->phone,
                'cash_received' => $request->cash_received,
                'change_amount' => $request->change_amount,
            ];

            $payment = $this->paymentService->processPayment($sale, $paymentData);

            // Update sale status if payment is completed
            if ($payment->isCompleted()) {
                $sale->update([
                    'status' => SaleStatus::COMPLETED,
                    'paid_at' => now(),
                ]);
            }

            return response()->json([
                'message' => 'Payment processed successfully',
                'payment' => $payment->load(['sale', 'store']),
                'requires_action' => $payment->status === PaymentStatus::PROCESSING,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Payment processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment methods available
     */
    public function getPaymentMethods(): JsonResponse
    {
        $methods = collect(PaymentMethod::cases())->map(function ($method): array {
            $feeRate = match ($method) {
                PaymentMethod::CASH => 0,
                PaymentMethod::CARD => 2.9,
                PaymentMethod::DIGITAL => 2.5,
                PaymentMethod::BANK_TRANSFER => 1.0,
                PaymentMethod::TOUCHNGO => 1.5,
                PaymentMethod::GRAB_PAY => 2.0,
                PaymentMethod::MOBILE_PAYMENT => 2.2,
            };

            $feeFixed = match ($method) {
                PaymentMethod::CARD => 0.50,
                default => 0,
            };

            $icon = match ($method) {
                PaymentMethod::CASH => '💵',
                PaymentMethod::CARD => '💳',
                PaymentMethod::DIGITAL => '🔗',
                PaymentMethod::BANK_TRANSFER => '🏦',
                PaymentMethod::TOUCHNGO => '📱',
                PaymentMethod::GRAB_PAY => '🚗',
                PaymentMethod::MOBILE_PAYMENT => '📱',
            };

            return [
                'code' => $method->value,
                'name' => $method->label(),
                'icon' => $icon,
                'fee_rate' => $feeRate,
                'fee_fixed' => $feeFixed,
                'enabled' => true,
            ];
        });

        return response()->json(['methods' => $methods]);
    }

    /**
     * Get payment history for a sale
     */
    public function getSalePayments(Sale $sale): JsonResponse
    {
        $payments = $sale->payments()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'payments' => $payments,
            'total_paid' => $payments->where('status', PaymentStatus::COMPLETED)->sum('amount'),
            'total_fees' => $payments->where('status', PaymentStatus::COMPLETED)->sum('fee'),
        ]);
    }

    /**
     * Get payment details
     */
    public function show(Payment $payment): JsonResponse
    {
        return response()->json(
            $payment->load(['sale', 'store', 'user'])
        );
    }

    /**
     * Refund a payment
     */
    public function refund(Payment $payment, Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'numeric|min:0.01|max:'.$payment->amount,
            'reason' => 'required|string|max:500',
        ]);

        try {
            $refundAmount = $request->amount ?? $payment->amount;
            $success = $this->paymentService->refundPayment($payment, $refundAmount);

            if ($success) {
                $payment->update([
                    'notes' => $request->reason,
                ]);

                return response()->json([
                    'message' => 'Payment refunded successfully',
                    'payment' => $payment->fresh(),
                ]);
            }

            return response()->json([
                'message' => 'Refund failed',
                'error' => 'REFUND_FAILED',
            ], 500);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Refund processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'store_id' => 'exists:stores,id',
        ]);

        $query = Payment::query()
            ->where('status', PaymentStatus::COMPLETED);

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        $payments = $query->get();

        $stats = [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'total_fees' => $payments->sum('fee'),
            'net_amount' => $payments->sum('net_amount'),
            'by_method' => $payments->groupBy('payment_method')->map(fn ($methodPayments): array => [
                'count' => $methodPayments->count(),
                'amount' => $methodPayments->sum('amount'),
                'fees' => $methodPayments->sum('fee'),
            ]),
        ];

        return response()->json($stats);
    }
}
