<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use Carbon\Carbon;
use GeekCo\CommerceJson\Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $documentable_type
 * @property string $documentable_id
 * @property string|null $external_id
 * @property string|null $type
 * @property string|null $name
 * @property string|null $file_name
 * @property string|null $file_path
 * @property string|null $disk
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $description
 * @property bool|null $paid
 * @property Carbon|null $document_date
 * @property string|null $document_amount_amount
 * @property string|null $document_amount_currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Document extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected static function newFactory(): DocumentFactory
    {
        return new DocumentFactory;
    }

    protected $fillable = [
        'id',
        'documentable_type',
        'documentable_id',
        'external_id',
        'type',
        'name',
        'file_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
        'description',
        'paid',
        'document_date',
        'document_amount_amount',
        'document_amount_currency',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'paid' => 'boolean',
        'document_date' => 'date',
        'document_amount_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
