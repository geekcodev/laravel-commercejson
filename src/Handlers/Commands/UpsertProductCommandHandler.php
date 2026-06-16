<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertProductCommand;
use GeekCo\CommerceJson\Data\CustomAttributeData;
use GeekCo\CommerceJson\Data\ProductComponentData;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Data\ProductImageData;
use GeekCo\CommerceJson\Data\ProductVariantData;
use GeekCo\CommerceJson\Data\PropertyValueData;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class UpsertProductCommandHandler implements CommandHandlerInterface
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertProductCommand);

        $data = $command->productData;

        return DB::transaction(function () use ($data) {
            $product = $this->repository->find($data->id);

            if (! $product && $data->external_id !== null) {
                $product = $this->repository->findByExternalId($data->external_id);
            }

            if ($product) {
                $product->update($this->buildFlatAttributes($data));
            } else {
                $product = $this->repository->create($this->buildFlatAttributes($data));
            }

            assert($product instanceof Product);

            $this->syncImages($product, $data->images);
            $this->syncProperties($product, $data->properties);
            $this->syncVariants($product, $data->variants);
            $this->syncCustomAttributes($product, $data->custom_attributes);
            $this->syncAnalogues($product, $data->analogues);
            $this->syncComponents($product, $data->components);

            return $product;
        });
    }

    private function buildFlatAttributes(ProductData $data): array
    {
        $attrs = [];

        foreach (['id', 'external_id', 'name', 'code', 'barcode', 'category_id',
            'description', 'short_description', 'tax_rate', 'weight',
            'is_active'] as $field) {
            if (property_exists($data, $field)) {
                $attrs[$field] = $data->$field;
            }
        }

        if ($data->unit !== null) {
            $attrs['unit_code'] = $data->unit->code?->value;
            $attrs['unit_short_name'] = $data->unit->short_name;
            $attrs['unit_full_name'] = $data->unit->full_name;
            $attrs['unit_international'] = $data->unit->international;
        }

        if ($data->dimensions !== null) {
            $attrs['dimensions_length'] = $data->dimensions->length;
            $attrs['dimensions_width'] = $data->dimensions->width;
            $attrs['dimensions_height'] = $data->dimensions->height;
        }

        if ($data->manufacturer !== null) {
            $attrs['manufacturer_country'] = $data->manufacturer->country;
            $attrs['manufacturer_brand'] = $data->manufacturer->brand;
            $attrs['manufacturer_brand_owner_id'] = $data->manufacturer->brand_owner_id;
            $attrs['manufacturer_id'] = $data->manufacturer->manufacturer_id;
        }

        if ($data->seo !== null) {
            $attrs['seo_title'] = $data->seo->title;
            $attrs['seo_description'] = $data->seo->description;
            $attrs['seo_keywords'] = $data->seo->keywords;
        }

        return $attrs;
    }

    private function resolveValue(mixed $value): array
    {
        if (is_string($value)) {
            return ['value_string' => $value, 'value_number' => null, 'value_boolean' => null, 'value_json' => null];
        }

        if (is_int($value) || is_float($value)) {
            return ['value_string' => null, 'value_number' => $value, 'value_boolean' => null, 'value_json' => null];
        }

        if (is_bool($value)) {
            return ['value_string' => null, 'value_number' => null, 'value_boolean' => $value, 'value_json' => null];
        }

        if (is_array($value)) {
            return ['value_string' => null, 'value_number' => null, 'value_boolean' => null, 'value_json' => $value];
        }

        return ['value_string' => (string) $value, 'value_number' => null, 'value_boolean' => null, 'value_json' => null];
    }

    private function syncImages(Product $product, ?array $imagesData): void
    {
        if ($imagesData === null) {
            return;
        }

        $product->images()->delete();

        $rows = [];
        /** @var ProductImageData $img */
        foreach ($imagesData as $img) {
            $rows[] = [
                'product_id' => $product->id,
                'url' => $img->url,
                'sort' => $img->sort ?? 0,
                'alt' => $img->alt,
                'is_main' => $img->is_main ?? false,
            ];
        }

        if ($rows !== []) {
            $product->images()->createMany($rows);
        }
    }

    private function syncProperties(Product $product, ?array $propertiesData): void
    {
        if ($propertiesData === null) {
            return;
        }

        $product->propertyValues()->whereNull('variant_id')->delete();

        $rows = [];
        /** @var PropertyValueData $prop */
        foreach ($propertiesData as $prop) {
            $rows[] = array_merge([
                'property_id' => $prop->property_id,
                'product_id' => $product->id,
                'variant_id' => null,
            ], $this->resolveValue($prop->value));
        }

        if ($rows !== []) {
            $product->propertyValues()->createMany($rows);
        }
    }

    private function syncVariants(Product $product, ?array $variantsData): void
    {
        if ($variantsData === null) {
            return;
        }

        $existingIds = $product->variants()->pluck('id')->toArray();
        $incomingIds = [];

        /** @var ProductVariantData $vd */
        foreach ($variantsData as $vd) {
            $variant = $product->variants()->updateOrCreate(
                ['id' => $vd->id],
                [
                    'external_id' => $vd->external_id,
                    'name' => $vd->name,
                    'code' => $vd->code,
                    'barcode' => $vd->barcode,
                    'is_active' => $vd->is_active ?? true,
                ]
            );
            $incomingIds[] = $variant->id;

            if ($vd->properties !== null) {
                $variant->propertyValues()->delete();

                $vRows = [];
                foreach ($vd->properties as $vp) {
                    $vRows[] = array_merge([
                        'property_id' => $vp->property_id,
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                    ], $this->resolveValue($vp->value));
                }

                if ($vRows !== []) {
                    $variant->propertyValues()->createMany($vRows);
                }
            }
        }

        $toDelete = array_diff($existingIds, $incomingIds);
        if ($toDelete !== []) {
            $product->variants()->whereIn('id', $toDelete)->delete();
        }
    }

    private function syncCustomAttributes(Product $product, ?array $customAttributesData): void
    {
        if ($customAttributesData === null) {
            return;
        }

        $product->customAttributes()->delete();

        $rows = [];
        /** @var CustomAttributeData $attr */
        foreach ($customAttributesData as $attr) {
            $rows[] = array_merge([
                'attributable_type' => $product->getMorphClass(),
                'attributable_id' => $product->id,
                'key' => $attr->key,
            ], $this->resolveValue($attr->value));
        }

        if ($rows !== []) {
            $product->customAttributes()->createMany($rows);
        }
    }

    private function syncAnalogues(Product $product, ?array $analogueIds): void
    {
        if ($analogueIds === null) {
            return;
        }

        $product->analogues()->sync($analogueIds);
    }

    private function syncComponents(Product $product, ?array $componentsData): void
    {
        if ($componentsData === null) {
            return;
        }

        $sync = [];
        foreach ($componentsData as $component) {
            if ($component instanceof ProductComponentData) {
                $sync[$component->product_id] = ['quantity' => $component->quantity];
            }
        }

        if ($sync !== []) {
            $product->components()->sync($sync);
        }
    }
}
