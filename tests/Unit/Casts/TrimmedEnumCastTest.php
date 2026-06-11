<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Casts;

use GeekCo\CommerceJson\Data\UnitData;
use GeekCo\CommerceJson\Enums\OkeiEnum;
use GeekCo\CommerceJson\Tests\TestCase;

class TrimmedEnumCastTest extends TestCase
{
    public function test_trims_whitespace_before_enum_casting(): void
    {
        $data = UnitData::from(['code' => ' 796 ']);

        $this->assertInstanceOf(OkeiEnum::class, $data->code);
        $this->assertSame(OkeiEnum::UNIT_PIECE, $data->code);
    }

    public function test_works_without_whitespace(): void
    {
        $data = UnitData::from(['code' => '796']);

        $this->assertSame(OkeiEnum::UNIT_PIECE, $data->code);
    }

    public function test_trims_tabs_and_newlines(): void
    {
        $data = UnitData::from(['code' => "\t796\n"]);

        $this->assertSame(OkeiEnum::UNIT_PIECE, $data->code);
    }

    public function test_passes_nulls_through(): void
    {
        $data = UnitData::from(['code' => null]);

        $this->assertNull($data->code);
    }

    public function test_populates_other_unit_fields(): void
    {
        $data = UnitData::from([
            'code' => ' 166 ',
            'short_name' => 'kg',
            'full_name' => 'Kilogram',
            'international' => 'KG',
        ]);

        $this->assertSame(OkeiEnum::UNIT_KILOGRAM, $data->code);
        $this->assertSame('kg', $data->short_name);
        $this->assertSame('Kilogram', $data->full_name);
        $this->assertSame('KG', $data->international);
    }
}
