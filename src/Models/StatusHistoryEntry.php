<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusHistoryEntry extends Model
{
    protected $fillable = [
        'order_id',
        'status',
        'changed_at',
        'changed_by',
        'comment',
    ];

    protected $casts = [
        'changed_at' => 'datetime:3', // milliseconds precision
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
