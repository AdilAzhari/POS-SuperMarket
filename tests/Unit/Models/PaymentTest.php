<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Payment;

it('auto-generates payment code on creation', function (): void {
    $payment = Payment::factory()->create(['payment_code' => null]);

    expect($payment->payment_code)
        ->not->toBeNull()
        ->toStartWith('PAY-');
});

it('calculates net amount correctly', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 100.00,
        'fee' => 2.50,
    ]);

    expect((float) $payment->net_amount)->toBe(97.50);
});

it('can mark payment as processed', function (): void {
    $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

    $payment->markAsProcessed();

    expect($payment->status)->toBe(PaymentStatus::COMPLETED)
        ->and($payment->processed_at)->not->toBeNull();
});

it('can mark payment as failed', function (): void {
    $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

    $payment->markAsFailed('Insufficient funds');

    expect($payment->status)->toBe(PaymentStatus::FAILED)
        ->and($payment->notes)->toBe('Insufficient funds');
});

it('has correct status check methods', function (): void {
    $completedPayment = Payment::factory()->create(['status' => PaymentStatus::COMPLETED]);
    $failedPayment = Payment::factory()->create(['status' => PaymentStatus::FAILED]);
    $pendingPayment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

    expect($completedPayment->isCompleted())->toBeTrue()
        ->and($completedPayment->isFailed())->toBeFalse()
        ->and($completedPayment->isPending())->toBeFalse();

    expect($failedPayment->isCompleted())->toBeFalse()
        ->and($failedPayment->isFailed())->toBeTrue()
        ->and($failedPayment->isPending())->toBeFalse();

    expect($pendingPayment->isCompleted())->toBeFalse()
        ->and($pendingPayment->isFailed())->toBeFalse()
        ->and($pendingPayment->isPending())->toBeTrue();
});
