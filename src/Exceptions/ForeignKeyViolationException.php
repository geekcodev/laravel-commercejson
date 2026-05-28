<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

use Illuminate\Database\QueryException;
use RuntimeException;

class ForeignKeyViolationException extends RuntimeException
{
    public readonly string $errorCode;

    public function __construct(QueryException $previous)
    {
        $result = self::parse($previous);

        parent::__construct($result['message'], 0, $previous);

        $this->errorCode = $result['code'];
    }

    /**
     * @return array{message: string, code: string}
     */
    private static function parse(QueryException $e): array
    {
        $message = $e->getMessage();

        if (preg_match('/foreign key constraint.*"(?P<fk>[^"]+)"/i', $message, $m)) {
            $fk = $m['fk'];

            return match (true) {
                str_contains($fk, 'category_id') => [
                    'code' => 'MISSING_CATEGORY',
                    'message' => 'Referenced category does not exist',
                ],
                str_contains($fk, 'manufacturer_id') => [
                    'code' => 'MISSING_MANUFACTURER',
                    'message' => 'Referenced manufacturer (counterparty) does not exist',
                ],
                str_contains($fk, 'manufacturer_brand_owner_id') => [
                    'code' => 'MISSING_BRAND_OWNER',
                    'message' => 'Referenced brand owner (counterparty) does not exist',
                ],
                str_contains($fk, 'parent_id') => [
                    'code' => 'MISSING_PARENT_CATEGORY',
                    'message' => 'Referenced parent category does not exist',
                ],
                str_contains($fk, 'property_id') => [
                    'code' => 'MISSING_PROPERTY_DEFINITION',
                    'message' => 'Referenced property definition does not exist',
                ],
                str_contains($fk, 'product_id') => [
                    'code' => 'MISSING_PRODUCT',
                    'message' => 'Referenced product does not exist',
                ],
                str_contains($fk, 'variant_id') => [
                    'code' => 'MISSING_VARIANT',
                    'message' => 'Referenced variant does not exist',
                ],
                default => [
                    'code' => 'FOREIGN_KEY_VIOLATION',
                    'message' => "Referenced record for constraint '{$fk}' does not exist",
                ],
            };
        }

        if ($e->getCode() === '23503') {
            return [
                'code' => 'FOREIGN_KEY_VIOLATION',
                'message' => 'Referenced entity does not exist',
            ];
        }

        return [
            'code' => 'FOREIGN_KEY_VIOLATION',
            'message' => $e->getMessage(),
        ];
    }
}
