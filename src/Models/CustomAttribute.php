<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomAttribute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attributable_type',
        'attributable_id',
        'key',
        'value_string',
        'value_number',
        'value_boolean',
        'value_json',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_boolean' => 'boolean',
        'value_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function attributable(): MorphTo
    {
        return $this->morphTo();
    }
}
