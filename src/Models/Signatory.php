<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Signatory extends Model
{
    protected $fillable = [
        'signatory_type',
        'signatory_id',
        'last_name',
        'first_name',
        'middle_name',
        'position',
        'basis',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function signatory(): MorphTo
    {
        return $this->morphTo();
    }
}
