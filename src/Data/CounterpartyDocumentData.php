<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CounterpartyDocumentData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $external_id,
        #[Nullable, StringType]
        public ?string $type = null,
        #[Nullable, StringType]
        public ?string $name = null,
        #[Nullable, StringType]
        public ?string $file_name = null,
        #[Nullable, StringType]
        public ?string $file_content = null,
        #[Nullable, StringType]
        public ?string $description = null,
        #[Nullable, BooleanType]
        public ?bool $paid = null,
        #[Nullable]
        public ?Carbon $document_date = null,
        #[Nullable]
        public ?MoneyData $document_amount = null,
        #[Nullable, StringType]
        public readonly ?string $id = null,
        #[Nullable, StringType]
        public readonly ?string $mime_type = null,
        #[Nullable]
        public readonly ?int $file_size = null,
        #[Nullable, StringType]
        public readonly ?string $download_url = null,
        #[Nullable]
        public readonly ?Carbon $uploaded_at = null,
    ) {}

    public static function createForOutput(Document $doc): static
    {
        $disk = $doc->disk ?? config('commercejson.documents.disk', 'public');

        $data = [
            'id' => $doc->id,
            'external_id' => $doc->external_id,
            'type' => $doc->type,
            'name' => $doc->name,
            'file_name' => $doc->file_name,
            'mime_type' => $doc->mime_type,
            'file_size' => $doc->file_size,
            'description' => $doc->description,
            'download_url' => $doc->file_path
                ? Storage::disk($disk)->url($doc->file_path)
                : null,
            'uploaded_at' => $doc->created_at,
        ];

        $data['paid'] = $doc->paid;

        if ($doc->document_date !== null) {
            $data['document_date'] = $doc->document_date;
        }

        if ($doc->document_amount_amount !== null && $doc->document_amount_currency !== null) {
            $data['document_amount'] = MoneyData::from([
                'amount' => (string) $doc->document_amount_amount,
                'currency' => $doc->document_amount_currency,
            ]);
        }

        return static::from($data);
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();

            $hasContent = ! empty($data['file_content'] ?? null);
            $hasType = ! empty($data['type'] ?? null);
            $hasName = ! empty($data['name'] ?? null);
            $hasFileName = ! empty($data['file_name'] ?? null);

            if ($hasContent && (! $hasType || ! $hasName || ! $hasFileName)) {
                if (! $hasType) {
                    $validator->errors()->add('type', 'Type is required when file_content is provided');
                }
                if (! $hasName) {
                    $validator->errors()->add('name', 'Name is required when file_content is provided');
                }
                if (! $hasFileName) {
                    $validator->errors()->add('file_name', 'File name is required when file_content is provided');
                }
            }

            $maxSize = (int) config('commercejson.documents.max_file_size', 10 * 1024 * 1024);
            if ($hasContent) {
                $decodedSize = (int) (strlen($data['file_content']) * 3 / 4);
                if ($decodedSize > $maxSize) {
                    $validator->errors()->add('file_content', sprintf(
                        'File content exceeds maximum size of %d bytes (%d bytes provided)',
                        $maxSize,
                        $decodedSize
                    ));
                }
            }
        });
    }
}
