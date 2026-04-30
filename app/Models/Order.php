<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'client_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'status',
        'required_at',
        'total',
        'notes',
        'approved_at',
        'locked_at',
        'cooking_started_at',
    ];

    protected function casts(): array
    {
        return [
            'required_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
            'cooking_started_at' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function history()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function canClientEdit(): bool
    {
        return $this->locked_at === null && in_array($this->status, ['pending', 'approved'], true);
    }

    public function customerName(): string
    {
        return $this->customer_name ?: ($this->client?->name ?? 'Walk-in customer');
    }

    public function customerPhone(): ?string
    {
        return $this->customer_phone ?: $this->client?->clientProfile?->phone;
    }
}
