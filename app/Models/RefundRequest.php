<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;

class RefundRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_APPROVED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeRequested(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_REQUESTED
        );
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_PROCESSING,
        ]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_COMPLETED
        );
    }

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function canTransitionTo(string $nextStatus): bool
    {
        if (!in_array($nextStatus, self::STATUSES, true)) {
            return false;
        }

        $transitions = [
            self::STATUS_REQUESTED => [
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
                self::STATUS_CANCELLED,
            ],

            self::STATUS_APPROVED => [
                self::STATUS_PROCESSING,
                self::STATUS_FAILED,
                self::STATUS_CANCELLED,
            ],

            self::STATUS_PROCESSING => [
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
            ],

            self::STATUS_FAILED => [
                self::STATUS_APPROVED,
                self::STATUS_CANCELLED,
            ],

            self::STATUS_COMPLETED => [],
            self::STATUS_REJECTED => [],
            self::STATUS_CANCELLED => [],
        ];

        return in_array(
            $nextStatus,
            $transitions[$this->status] ?? [],
            true
        );
    }
}
