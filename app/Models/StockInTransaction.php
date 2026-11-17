<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockInTransaction extends Model
{
    protected $fillable = [
        'reference_number',
        'supplier',
        'received_date',
        'notes',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'stock_in_transaction_id');
    }
}
