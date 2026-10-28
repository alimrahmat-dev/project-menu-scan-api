<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_EXPIRED = 'expired';

    public const PAYMENT_REFUNDED = 'refunded';

    public const ORDER_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'order_number',
        'tracking_token',
        'dining_table_id',
        'customer_name',
        'customer_phone',
        'notes',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_url',
        'paid_at',
        'subtotal',
        'tax_amount',
        'service_amount',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'subtotal' => 'integer',
            'tax_amount' => 'integer',
            'service_amount' => 'integer',
            'total' => 'integer',
        ];
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function canBePaid(): bool
    {
        return in_array($this->payment_status, [self::PAYMENT_UNPAID, self::PAYMENT_FAILED], true)
            && $this->status !== self::STATUS_CANCELLED;
    }

    public function markAsPaid(?string $method = null, ?string $reference = null): void
    {
        $this->forceFill([
            'payment_status' => self::PAYMENT_PAID,
            'paid_at' => now(),
            'status' => $this->status === self::STATUS_PENDING ? self::STATUS_CONFIRMED : $this->status,
            'payment_method' => $method ?? $this->payment_method,
            'payment_reference' => $reference ?? $this->payment_reference,
        ])->save();
    }

    public function trackingTokenMatches(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals($this->tracking_token, $token);
    }
}
