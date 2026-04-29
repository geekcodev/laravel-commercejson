<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Enums\ContactTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'counterparty_id',
        'type',
        'value',
        'comment',
    ];

    protected $casts = [
        'type' => ContactTypeEnum::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }
}
