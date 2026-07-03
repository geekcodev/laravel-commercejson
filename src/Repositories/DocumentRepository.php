<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Document;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository extends BaseRepository
{
    public function __construct(Document $model)
    {
        parent::__construct($model);
    }

    /**
     * @return Collection<int, Document>
     */
    public function findByDocumentable(string $type, string $id): Collection
    {
        /** @var Collection<int, Document> $results */
        $results = $this->model
            ->where('documentable_type', $type)
            ->where('documentable_id', $id)
            ->get();

        return $results;
    }

    public function findByExternalId(string $documentableType, string $documentableId, string $externalId): ?Document
    {
        /** @var Document|null $document */
        $document = $this->model
            ->withTrashed()
            ->where('documentable_type', $documentableType)
            ->where('documentable_id', $documentableId)
            ->where('external_id', $externalId)
            ->first();

        return $document;
    }

    /**
     * @param  array<int, string>  $externalIds
     */
    public function deleteMissingExternalIds(string $documentableType, string $documentableId, array $externalIds): void
    {
        $this->model
            ->where('documentable_type', $documentableType)
            ->where('documentable_id', $documentableId)
            ->whereNotIn('external_id', $externalIds)
            ->delete();
    }
}
