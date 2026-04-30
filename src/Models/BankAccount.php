<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasFactory;

    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::newInstance();
    }

    protected $fillable = [
        'counterparty_id',
        'bank_name',
        'bik',
        'account',
        'corr_account',
        'swift',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }
}
