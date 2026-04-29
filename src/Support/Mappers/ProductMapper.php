<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Support\Mappers;

use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Models\Product;

/**
 * Маппер для товаров (API Data → Model)
 */
class ProductMapper
{
    /**
     * Преобразовать ProductData в Product модель
     */
    public function toModel(ProductData $data): Product
    {
        return Product::updateOrCreate(
            ['id' => $data->id],
            [
                'external_id' => $data->externalId,
                'name' => $data->name,
                'code' => $data->code,
                'barcode' => $data->barcode,
                'category_id' => $data->categoryId,
                'description' => $data->description,
                'short_description' => $data->shortDescription,
                'tax_rate' => $data->taxRate,
                'weight' => $data->weight,
                'dimensions_length' => $data->dimensions?->length,
                'dimensions_width' => $data->dimensions?->width,
                'dimensions_height' => $data->dimensions?->height,
                'manufacturer_country' => $data->manufacturer?->country,
                'manufacturer_brand' => $data->manufacturer?->brand,
                'manufacturer_brand_owner_id' => $data->manufacturer?->brandOwnerId,
                'manufacturer_id' => $data->manufacturer?->manufacturerId,
                'unit_code' => $data->unit?->code,
                'unit_short_name' => $data->unit?->shortName,
                'unit_full_name' => $data->unit?->fullName,
                'unit_international' => $data->unit?->international,
                'is_active' => $data->isActive ?? true,
                'seo_title' => $data->seo?->title,
                'seo_description' => $data->seo?->description,
                'seo_keywords' => $data->seo?->keywords,
            ]
        );
    }

    /**
     * Преобразовать Product модель в ProductData
     */
    public function toData(Product $model): ProductData
    {
        return new ProductData(
            id: $model->id,
            externalId: $model->external_id,
            name: $model->name,
            code: $model->code,
            barcode: $model->barcode,
            categoryId: $model->category_id,
            description: $model->description,
            shortDescription: $model->short_description,
            taxRate: $model->tax_rate,
            weight: $model->weight,
            isActive: $model->is_active,
        );
    }
}
