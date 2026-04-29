<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'offer_id',
        'warehouse_id',
        'quantity',
        'quantity_reserved',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'quantity_reserved' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
