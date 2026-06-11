<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\OfferPrice;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\PriceType;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\PropertyDefinition;
use GeekCo\CommerceJson\Models\Stock;
use GeekCo\CommerceJson\Models\Warehouse;
use GeekCo\CommerceJson\Repositories\CategoryRepository;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;
use GeekCo\CommerceJson\Repositories\OfferPriceRepository;
use GeekCo\CommerceJson\Repositories\OfferRepository;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\PriceTypeRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use GeekCo\CommerceJson\Repositories\PropertyDefinitionRepository;
use GeekCo\CommerceJson\Repositories\StockRepository;
use GeekCo\CommerceJson\Repositories\WarehouseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('ProductRepository', function () {
    it('finds many products by ids', function () {
        $products = Product::factory(3)->create();
        $ids = $products->pluck('id')->toArray();

        $repo = new ProductRepository(new Product);
        $found = $repo->findMany($ids);

        expect($found)->toHaveCount(3);
    });

    it('finds products by category', function () {
        $category = Category::factory()->create();
        Product::factory(2)->create(['category_id' => $category->id]);
        Product::factory()->create();

        $repo = new ProductRepository(new Product);
        $found = $repo->findByCategory($category->id);

        expect($found)->toHaveCount(2);
    });

    it('paginates with eager loading', function () {
        Product::factory(5)->create();

        $repo = new ProductRepository(new Product);
        $result = $repo->paginate(3);

        expect($result->count())->toBe(3);
        expect($result->total())->toBe(5);
    });
});

describe('CategoryRepository', function () {
    it('finds categories by parent', function () {
        $parent = Category::factory()->create();
        Category::factory(2)->create(['parent_id' => $parent->id]);
        Category::factory()->create(['parent_id' => null]);

        $repo = new CategoryRepository(new Category);
        $children = $repo->findByParent($parent->id);

        expect($children)->toHaveCount(2);
    });

    it('finds root categories', function () {
        Category::factory(3)->create(['parent_id' => null]);

        $repo = new CategoryRepository(new Category);
        $roots = $repo->findByParent(null);

        expect($roots)->toHaveCount(3);
    });
});

describe('OfferRepository', function () {
    it('finds offers by product', function () {
        $product = Product::factory()->create();
        Offer::factory(2)->create(['product_id' => $product->id]);

        $repo = new OfferRepository(new Offer);
        $found = $repo->findByProduct($product->id);

        expect($found)->toHaveCount(2);
    });

    it('paginates with eager loading', function () {
        Offer::factory(3)->create();

        $repo = new OfferRepository(new Offer);
        $result = $repo->paginate(2);

        expect($result->count())->toBe(2);
    });
});

describe('OrderRepository', function () {
    it('finds orders by status', function () {
        Order::factory(3)->create(['status' => OrderStatusEnum::New->value]);
        Order::factory()->create(['status' => OrderStatusEnum::Confirmed->value]);

        $repo = new OrderRepository(new Order);
        $found = $repo->findByStatus(OrderStatusEnum::New->value);

        expect($found)->toHaveCount(3);
    });
});

describe('CounterpartyRepository', function () {
    it('finds counterparties by type', function () {
        Counterparty::factory(2)->create(['type' => 'legal_entity']);
        Counterparty::factory()->create(['type' => 'individual']);

        $repo = new CounterpartyRepository(new Counterparty);
        $found = $repo->findByType('legal_entity');

        expect($found)->toHaveCount(2);
    });
});

describe('WarehouseRepository', function () {
    it('returns all warehouses including trashed', function () {
        Warehouse::factory(2)->create();
        $trashed = Warehouse::factory()->create();
        $trashed->delete();

        $repo = new WarehouseRepository(new Warehouse);

        $active = $repo->all();
        $withTrashed = $repo->allWithTrashed();

        expect($active)->toHaveCount(2);
        expect($withTrashed)->toHaveCount(3);
    });
});

describe('PriceTypeRepository', function () {
    it('finds all price types', function () {
        PriceType::factory(3)->create();

        $repo = new PriceTypeRepository(new PriceType);
        $all = $repo->all();

        expect($all)->toHaveCount(3);
    });

    it('creates and finds a price type', function () {
        $repo = new PriceTypeRepository(new PriceType);
        $created = $repo->create([
            'id' => test()->createTestUuid(),
            'name' => 'Wholesale',
            'currency' => CurrencyEnum::RUB->value,
        ]);

        $found = $repo->find($created->id);
        expect($found->name)->toBe('Wholesale');
    });
});

describe('OfferPriceRepository', function () {
    it('creates and retrieves offer prices', function () {
        $offer = Offer::factory()->create();
        $priceType = PriceType::factory()->create();
        $id = test()->createTestUuid();

        $repo = new OfferPriceRepository(new OfferPrice);
        $created = $repo->create([
            'id' => $id,
            'offer_id' => $offer->id,
            'price_type_id' => $priceType->id,
            'price_amount' => '1500.00',
            'price_currency' => CurrencyEnum::RUB->value,
        ]);

        $found = $repo->find($id);
        expect($found->price_amount)->toBe('1500.00');
    });
});

describe('StockRepository', function () {
    it('creates and retrieves stock entries', function () {
        $offer = Offer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $id = test()->createTestUuid();

        $repo = new StockRepository(new Stock);
        $created = $repo->create([
            'id' => $id,
            'offer_id' => $offer->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
        ]);

        $found = $repo->find($id);
        expect((int) $found->quantity)->toBe(100);
    });
});

describe('PropertyDefinitionRepository', function () {
    it('creates and finds property definitions', function () {
        $repo = new PropertyDefinitionRepository(new PropertyDefinition);
        $created = $repo->create([
            'id' => test()->createTestUuid(),
            'name' => 'Color',
            'type' => 'string',
        ]);

        $found = $repo->find($created->id);
        expect($found->name)->toBe('Color');
    });
});

describe('BaseRepository', function () {
    it('updateOrCreate creates if not exists', function () {
        $product = Product::factory()->create();

        $repo = new ProductRepository(new Product);
        $result = $repo->updateOrCreate(
            ['id' => $product->id],
            ['name' => 'Updated via upsert'],
        );

        expect($result->name)->toBe('Updated via upsert');
    });

    it('soft deletes and checks deletion', function () {
        $product = Product::factory()->create();

        $repo = new ProductRepository(new Product);
        $repo->delete($product);

        $found = $repo->find($product->id);
        expect($found)->toBeNull();
    });

    it('finds or fails with exception', function () {
        $repo = new ProductRepository(new Product);

        expect(fn () => $repo->findOrFail('non-existent-id'))
            ->toThrow(ModelNotFoundException::class);
    });
});
