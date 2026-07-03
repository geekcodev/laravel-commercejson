<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Document;
use GeekCo\CommerceJson\Repositories\DocumentRepository;

describe('DocumentRepository', function () {
    it('creates a document', function () {
        $counterparty = Counterparty::factory()->create();
        $repo = new DocumentRepository(new Document);

        $doc = $repo->create([
            'id' => test()->createTestUuid(),
            'documentable_type' => (new Counterparty)->getMorphClass(),
            'documentable_id' => $counterparty->id,
            'external_id' => 'ext-doc-001',
            'type' => 'contract',
            'name' => 'Main Contract',
            'file_name' => 'contract.pdf',
            'file_path' => 'commercejson/documents/test/contract.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
        ]);

        expect($doc)->toBeInstanceOf(Document::class);
        expect($doc->external_id)->toBe('ext-doc-001');
        expect($doc->name)->toBe('Main Contract');

        test()->assertDatabaseHas('documents', [
            'external_id' => 'ext-doc-001',
            'name' => 'Main Contract',
        ]);
    });

    it('finds document by external_id', function () {
        $counterparty = Counterparty::factory()->create();
        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'ext-uniq-001',
        ]);

        $repo = new DocumentRepository(new Document);
        $found = $repo->findByExternalId(
            (new Counterparty)->getMorphClass(),
            $counterparty->id,
            'ext-uniq-001',
        );

        expect($found)->toBeInstanceOf(Document::class);
        expect($found->external_id)->toBe('ext-uniq-001');
    });

    it('returns null when document by external_id not found', function () {
        $repo = new DocumentRepository(new Document);
        $found = $repo->findByExternalId(
            (new Counterparty)->getMorphClass(),
            'non-existent',
            'ext-uniq-001',
        );

        expect($found)->toBeNull();
    });

    it('finds documents by documentable', function () {
        $counterparty = Counterparty::factory()->create();
        Document::factory(3)->create([
            'documentable_id' => $counterparty->id,
        ]);

        $repo = new DocumentRepository(new Document);
        $docs = $repo->findByDocumentable(
            (new Counterparty)->getMorphClass(),
            $counterparty->id,
        );

        expect($docs)->toHaveCount(3);
    });

    it('deletes documents not in the given external_ids list', function () {
        $counterparty = Counterparty::factory()->create();
        $morphClass = (new Counterparty)->getMorphClass();

        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'keep-01',
        ]);
        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'keep-02',
        ]);
        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'delete-01',
        ]);

        $repo = new DocumentRepository(new Document);
        $repo->deleteMissingExternalIds($morphClass, $counterparty->id, ['keep-01', 'keep-02']);

        expect(Document::where('external_id', 'delete-01')->withTrashed()->first()->deleted_at)->not->toBeNull();
        test()->assertDatabaseHas('documents', ['external_id' => 'keep-01']);
        test()->assertDatabaseHas('documents', ['external_id' => 'keep-02']);
    });

    it('deletes all documents when empty external_ids list', function () {
        $counterparty = Counterparty::factory()->create();
        $morphClass = (new Counterparty)->getMorphClass();

        Document::factory(2)->create([
            'documentable_id' => $counterparty->id,
        ]);

        $repo = new DocumentRepository(new Document);
        $repo->deleteMissingExternalIds($morphClass, $counterparty->id, []);

        expect(Document::where('documentable_id', $counterparty->id)->withTrashed()->count())->toBe(2);
        expect(Document::where('documentable_id', $counterparty->id)->count())->toBe(0);
    });

    it('does not delete documents of other counterparties', function () {
        $counterparty1 = Counterparty::factory()->create();
        $counterparty2 = Counterparty::factory()->create();
        $morphClass = (new Counterparty)->getMorphClass();

        Document::factory()->create([
            'documentable_id' => $counterparty1->id,
            'external_id' => 'delete-me',
        ]);
        Document::factory()->create([
            'documentable_id' => $counterparty2->id,
            'external_id' => 'keep-me',
        ]);

        $repo = new DocumentRepository(new Document);
        $repo->deleteMissingExternalIds($morphClass, $counterparty1->id, []);

        expect(Document::where('external_id', 'delete-me')->withTrashed()->first()->deleted_at)->not->toBeNull();
        test()->assertDatabaseHas('documents', ['external_id' => 'keep-me']);
    });

    it('updates existing document', function () {
        $counterparty = Counterparty::factory()->create();
        $doc = Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'name' => 'Old Name',
        ]);

        $repo = new DocumentRepository(new Document);
        $updated = $repo->update($doc, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');
        test()->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'name' => 'New Name',
        ]);
    });

    it('soft deletes document', function () {
        $counterparty = Counterparty::factory()->create();
        $doc = Document::factory()->create([
            'documentable_id' => $counterparty->id,
        ]);

        $repo = new DocumentRepository(new Document);
        $repo->delete($doc);

        test()->assertSoftDeleted('documents', ['id' => $doc->id]);
    });

    it('finds document by id', function () {
        $counterparty = Counterparty::factory()->create();
        $doc = Document::factory()->create([
            'documentable_id' => $counterparty->id,
        ]);

        $repo = new DocumentRepository(new Document);
        $found = $repo->find($doc->id);

        expect($found)->toBeInstanceOf(Document::class);
        expect($found->id)->toBe($doc->id);
    });

    it('returns null for non-existent id', function () {
        $repo = new DocumentRepository(new Document);
        $found = $repo->find('non-existent-id');

        expect($found)->toBeNull();
    });

    it('paginates documents', function () {
        $counterparty = Counterparty::factory()->create();
        Document::factory(5)->create([
            'documentable_id' => $counterparty->id,
        ]);

        $repo = new DocumentRepository(new Document);
        $result = $repo->paginate(3);

        expect($result->count())->toBe(3);
        expect($result->total())->toBe(5);
    });
});
