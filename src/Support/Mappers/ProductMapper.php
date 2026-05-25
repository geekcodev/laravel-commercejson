<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Support\Mappers;

use GeekCo\CommerceJson\Data\ProductData;

/**
 * Маппер для товаров (API Data → Model Data)
 *
 * Только преобразует данные, не выполняет сохранение.
 */
class ProductMapper
{
    /**
     * Преобразовать ProductData в массив для создания/обновления модели
     *
     * @return array<string, mixed>
     */
    public function toPayload(ProductData $data): array
    {
        return [
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
        ];
    }

    /**
     * Преобразовать Product модель в ProductData
     *
     * @deprecated Используйте ProductData::from() напрямую
     */
    public function toData(ProductData $model): ProductData
    {
        return $model;
    }
}
