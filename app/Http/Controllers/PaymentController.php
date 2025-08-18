<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct()
    {
        // PaymentService temporarily disabled due to Stripe dependency issues
    }

    /**
     * Process a payment for a sale
     */
    public function processPayment(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'method' => ['required', Rule::in(['cash', 'stripe', 'visa', 'mastercard', 'amex', 'tng'])],
            'currency' => 'string|size:3|in:MYR,USD,SGD',

            // Stripe specific
            'payment_method_id' => 'required_if:method,stripe|string',

            // Card specific
            'card_number' => 'required_if:method,visa,mastercard,amex|string|min:13|max:19',
            'exp_month' => 'required_if:method,visa,mastercard,amex|integer|between:1,12',
            'exp_year' => 'required_if:method,visa,mastercard,amex|integer|min:2024',
            'cvv' => 'required_if:method,visa,mastercard,amex|string|size:3',
            'cardholder_name' => 'required_if:method,visa,mastercard,amex|string|max:255',

            // TNG specific
            'phone' => 'required_if:method,tng|string|regex:/^(\+?6?01)[0-9]{8,9}$/',
        ]);

        try {
            $sale = Sale::query()->findOrFail($request->sale_id);

            // Check if sale is already paid
            if ($sale->status === 'completed' && $sale->payments()->where('status', 'completed')->exists()) {
                return response()->json([
                    'message' => 'Sale is already paid',
                    'error' => 'ALREADY_PAID'
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
            ];

            $payment = $this->paymentService->processPayment($sale, $paymentData);

            // Update sale status if payment is completed
            if ($payment->isCompleted()) {
                $sale->update([
                    'status' => 'completed',
                    'paid_at' => now()
                ]);
            }

            return response()->json([
                'message' => 'Payment processed successfully',
                'payment' => $payment->load(['sale', 'store']),
                'requires_action' => $payment->status === 'processing'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment methods available
     */
    public function getPaymentMethods(): JsonResponse
    {
        return response()->json([
            'methods' => [
                [
                    'code' => 'cash',
                    'name' => 'Cash',
                    'icon' => '💵',
                    'fee_rate' => 0,
                    'enabled' => true
                ],
                [
                    'code' => 'stripe',
                    'name' => 'Stripe (Credit/Debit Card)',
                    'icon' => '💳',
                    'fee_rate' => 2.9,
                    'fee_fixed' => 0.50,
                    'enabled' => true
                ],
                [
                    'code' => 'visa',
                    'name' => 'Visa',
                    'icon' => '💳',
                    'fee_rate' => 2.5,
                    'enabled' => true
                ],
                [
                    'code' => 'mastercard',
                    'name' => 'Mastercard',
                    'icon' => '💳',
                    'fee_rate' => 2.5,
                    'enabled' => true
                ],
                [
                    'code' => 'amex',
                    'name' => 'American Express',
                    'icon' => '💳',
                    'fee_rate' => 3.5,
                    'enabled' => true
                ],
                [
                    'code' => 'tng',
                    'name' => 'Touch n Go eWallet',
                    'icon' => '📱',
                    'fee_rate' => 1.5,
                    'enabled' => true
                ]
            ]
        ]);
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
            'total_paid' => $payments->where('status', 'completed')->sum('amount'),
            'total_fees' => $payments->where('status', 'completed')->sum('fee')
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
            'amount' => 'numeric|min:0.01|max:' . $payment->amount,
            'reason' => 'required|string|max:500'
        ]);

        try {
            $refundAmount = $request->amount ?? $payment->amount;
            $success = $this->paymentService->refundPayment($payment, $refundAmount);

            if ($success) {
                $payment->update([
                    'notes' => $request->reason
                ]);

                return response()->json([
                    'message' => 'Payment refunded successfully',
                    'payment' => $payment->fresh()
                ]);
            } else {
                return response()->json([
                    'message' => 'Refund failed',
                    'error' => 'REFUND_FAILED'
                ], 500);
            }

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Refund processing failed',
                'error' => $e->getMessage()
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
            'store_id' => 'exists:stores,id'
        ]);

        $query = Payment::query()
            ->where('status', 'completed');

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
            'by_method' => $payments->groupBy('payment_method')->map(function ($methodPayments) {
                return [
                    'count' => $methodPayments->count(),
                    'amount' => $methodPayments->sum('amount'),
                    'fees' => $methodPayments->sum('fee')
                ];
            })
        ];

        return response()->json($stats);
    }
}
