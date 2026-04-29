<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAnalogue extends Model
{
    public $timestamps = true;

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'analogue_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function analogue(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'analogue_id');
    }
}
