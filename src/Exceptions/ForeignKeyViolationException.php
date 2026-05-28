<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

use Illuminate\Database\QueryException;
use RuntimeException;

class ForeignKeyViolationException extends RuntimeException
{
    public function __construct(QueryException $previous)
    {
        $message = self::formatMessage($previous);

        parent::__construct($message, 0, $previous);
    }

    private static function formatMessage(QueryException $e): string
    {
        $message = $e->getMessage();

        if (preg_match('/foreign key constraint.*"(?P<fk>[^"]+)"/i', $message, $m)) {
            $fk = $m['fk'];

            return match (true) {
                str_contains($fk, 'category_id') => 'Referenced category does not exist',
                str_contains($fk, 'manufacturer_id') => 'Referenced manufacturer (counterparty) does not exist',
                str_contains($fk, 'manufacturer_brand_owner_id') => 'Referenced brand owner (counterparty) does not exist',
                str_contains($fk, 'parent_id') => 'Referenced parent category does not exist',
                str_contains($fk, 'property_id') => 'Referenced property definition does not exist',
                str_contains($fk, 'product_id') => 'Referenced product does not exist',
                str_contains($fk, 'variant_id') => 'Referenced variant does not exist',
                default => "Referenced record for constraint '{$fk}' does not exist",
            };
        }

        if ($e->getCode() === '23503') {
            return 'Referenced entity does not exist';
        }

        return $e->getMessage();
    }
}
