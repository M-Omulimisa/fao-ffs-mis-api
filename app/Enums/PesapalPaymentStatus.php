<?php

namespace App\Enums;

class PesapalPaymentStatus
{
    // Pesapal API payment statuses
    const PENDING = 'PENDING';
    const COMPLETED = 'COMPLETED';
    const FAILED = 'FAILED';
    const INVALID = 'INVALID';
    const REVERSED = 'REVERSED';
    
    // Internal payment statuses
    const PENDING_PAYMENT = 'PENDING_PAYMENT';
    const PROCESSING = 'PROCESSING';
    const PARTIALLY_PAID = 'PARTIALLY_PAID';
    const REFUNDED = 'REFUNDED';
    const CANCELLED = 'CANCELLED';

    /**
     * Get all valid payment statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::PENDING,
            self::COMPLETED,
            self::FAILED,
            self::INVALID,
            self::REVERSED,
            self::PENDING_PAYMENT,
            self::PROCESSING,
            self::PARTIALLY_PAID,
            self::REFUNDED,
            self::CANCELLED
        ];
    }

    /**
     * Check if status indicates payment is successful
     */
    public static function isSuccessful(string $status): bool
    {
        return in_array($status, [
            self::COMPLETED,
            self::PARTIALLY_PAID
        ]);
    }

    /**
     * Check if status indicates payment has failed
     */
    public static function isFailed(string $status): bool
    {
        return in_array($status, [
            self::FAILED,
            self::INVALID,
            self::CANCELLED
        ]);
    }

    /**
     * Check if status indicates payment is still pending
     */
    public static function isPending(string $status): bool
    {
        return in_array($status, [
            self::PENDING,
            self::PENDING_PAYMENT,
            self::PROCESSING
        ]);
    }

    /**
     * Map Pesapal status to internal status
     */
    public static function mapPesapalStatus(string $pesapalStatus): string
    {
        $mappings = [
            'PENDING' => self::PENDING_PAYMENT,
            'COMPLETED' => self::COMPLETED,
            'FAILED' => self::FAILED,
            'INVALID' => self::INVALID,
            'REVERSED' => self::REVERSED,
        ];

        return $mappings[strtoupper($pesapalStatus)] ?? self::PENDING_PAYMENT;
    }

    /**
     * Get user-friendly status description
     */
    public static function getDescription(string $status): string
    {
        $descriptions = [
            self::PENDING => 'Payment pending',
            self::COMPLETED => 'Payment completed successfully',
            self::FAILED => 'Payment failed',
            self::INVALID => 'Invalid payment',
            self::REVERSED => 'Payment reversed/refunded',
            self::PENDING_PAYMENT => 'Awaiting payment',
            self::PROCESSING => 'Payment processing',
            self::PARTIALLY_PAID => 'Partially paid',
            self::REFUNDED => 'Payment refunded',
            self::CANCELLED => 'Payment cancelled'
        ];

        return $descriptions[$status] ?? 'Unknown payment status';
    }
}
