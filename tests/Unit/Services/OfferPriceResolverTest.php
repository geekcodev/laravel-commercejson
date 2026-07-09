<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Models\OfferPrice;
use GeekCo\CommerceJson\Services\OfferPriceResolver;
use GeekCo\CommerceJson\Tests\TestCase;
use Illuminate\Support\Str;

class OfferPriceResolverTest extends TestCase
{
    private OfferPriceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new OfferPriceResolver;
    }

    public function test_returns_null_for_empty_collection(): void
    {
        $result = $this->resolver->resolve(collect(), 'any-id');

        $this->assertNull($result);
    }

    private function makeOfferPrice(array $attributes = []): OfferPrice
    {
        return (new OfferPrice)->forceFill(array_merge([
            'id' => (string) Str::uuid(),
            'price_amount' => '0.00',
        ], $attributes));
    }

    public function test_returns_first_price_when_price_type_id_is_null(): void
    {
        $prices = collect([
            $this->makeOfferPrice(['price_amount' => '100.00', 'price_type_id' => 'type-a']),
            $this->makeOfferPrice(['price_amount' => '200.00', 'price_type_id' => 'type-b']),
        ]);

        $result = $this->resolver->resolve($prices, null);

        $this->assertNotNull($result);
        $this->assertSame('100.00', $result->price_amount);
    }

    public function test_returns_matching_price_by_price_type_id(): void
    {
        $prices = collect([
            $this->makeOfferPrice(['price_amount' => '100.00', 'price_type_id' => 'type-a']),
            $this->makeOfferPrice(['price_amount' => '200.00', 'price_type_id' => 'type-b']),
            $this->makeOfferPrice(['price_amount' => '300.00', 'price_type_id' => 'type-c']),
        ]);

        $result = $this->resolver->resolve($prices, 'type-b');

        $this->assertNotNull($result);
        $this->assertSame('200.00', $result->price_amount);
        $this->assertSame('type-b', $result->price_type_id);
    }

    public function test_falls_back_to_first_price_when_price_type_not_found(): void
    {
        $prices = collect([
            $this->makeOfferPrice(['price_amount' => '100.00', 'price_type_id' => 'type-a']),
            $this->makeOfferPrice(['price_amount' => '200.00', 'price_type_id' => 'type-b']),
        ]);

        $result = $this->resolver->resolve($prices, 'non-existent-type');

        $this->assertNotNull($result);
        $this->assertSame('100.00', $result->price_amount);
    }

    public function test_returns_first_price_when_only_one_price_exists(): void
    {
        $prices = collect([
            $this->makeOfferPrice(['price_amount' => '150.00', 'price_type_id' => 'type-a']),
        ]);

        $result = $this->resolver->resolve($prices, 'type-a');

        $this->assertNotNull($result);
        $this->assertSame('150.00', $result->price_amount);
    }
}
