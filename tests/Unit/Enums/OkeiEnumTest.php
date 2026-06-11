<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Enums;

use GeekCo\CommerceJson\Enums\OkeiEnum;
use GeekCo\CommerceJson\Tests\TestCase;

class OkeiEnumTest extends TestCase
{
    public function test_from_code_returns_correct_enum(): void
    {
        $this->assertSame(OkeiEnum::UNIT_PIECE, OkeiEnum::fromCode('796'));
        $this->assertSame(OkeiEnum::UNIT_KILOGRAM, OkeiEnum::fromCode('166'));
        $this->assertSame(OkeiEnum::UNIT_METER, OkeiEnum::fromCode('004'));
        $this->assertSame(OkeiEnum::UNIT_LITER, OkeiEnum::fromCode('006'));
    }

    public function test_from_code_strips_non_numeric_characters(): void
    {
        $this->assertSame(OkeiEnum::UNIT_PIECE, OkeiEnum::fromCode(' 796 '));
        $this->assertSame(OkeiEnum::UNIT_PIECE, OkeiEnum::fromCode('796abc'));
    }

    public function test_from_code_throws_on_invalid_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        OkeiEnum::fromCode('999');
    }

    public function test_try_from_code_returns_null_for_invalid(): void
    {
        $this->assertNull(OkeiEnum::tryFromCode('999'));
    }

    public function test_try_from_code_returns_enum_for_valid(): void
    {
        $this->assertSame(OkeiEnum::UNIT_PIECE, OkeiEnum::tryFromCode('796'));
    }

    public function test_is_valid_code(): void
    {
        $this->assertTrue(OkeiEnum::isValidCode('796'));
        $this->assertFalse(OkeiEnum::isValidCode('999'));
        $this->assertTrue(OkeiEnum::isValidCode(' 796 '));
    }

    public function test_getters_return_expected_values(): void
    {
        $unit = OkeiEnum::UNIT_PIECE;

        $this->assertSame('796', $unit->getCode());
        $this->assertSame('Штука', $unit->getFullName());
        $this->assertSame('шт', $unit->getShortName());
        $this->assertSame('pce', $unit->getInternational());
    }

    public function test_get_localized_name(): void
    {
        $unit = OkeiEnum::UNIT_KILOGRAM;

        $this->assertSame('Килограмм', $unit->getLocalizedName('ru'));
        $this->assertSame('Kilogram', $unit->getLocalizedName('en'));
    }

    public function test_get_localized_name_throws_on_unsupported_locale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        OkeiEnum::UNIT_PIECE->getLocalizedName('fr');
    }

    public function test_to_array_returns_full_structure(): void
    {
        $result = OkeiEnum::UNIT_METER->toArray();

        $this->assertSame([
            'code' => '004',
            'full_name' => 'Метр',
            'short_name' => 'м',
            'international' => 'm',
        ], $result);
    }

    public function test_json_serialize_returns_array(): void
    {
        $result = OkeiEnum::UNIT_LITER->jsonSerialize();

        $this->assertIsArray($result);
        $this->assertSame('006', $result['code']);
    }

    public function test_all_cases_have_unique_codes(): void
    {
        $codes = array_map(fn (OkeiEnum $case) => $case->value, OkeiEnum::cases());
        $this->assertSame(count($codes), count(array_unique($codes)));
    }
}
