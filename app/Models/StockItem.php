<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable = ['name', 'quantity', 'unit', 'low_stock_threshold', 'notes'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'low_stock_threshold' => 'decimal:2',
        ];
    }

    public function isLow(): bool
    {
        return (float) $this->quantity <= (float) $this->low_stock_threshold;
    }
}
