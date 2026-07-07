<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Data\MoneyData;
use Illuminate\Validation\ValidationException;

it('replaces comma with dot in amount from array', function () {
    $money = MoneyData::from(['amount' => '0,1', 'currency' => 'RUB']);

    expect($money->amount)->toBe('0.1');
});

it('replaces comma with dot in amount via validate', function () {
    $result = MoneyData::validateAndCreate(['amount' => '0,1', 'currency' => 'RUB']);

    expect($result->amount)->toBe('0.1');
});

it('handles dot amount unchanged', function () {
    $money = MoneyData::from(['amount' => '1500.00', 'currency' => 'RUB']);

    expect($money->amount)->toBe('1500.00');
});

it('handles integer amount', function () {
    $money = MoneyData::from(['amount' => '100', 'currency' => 'RUB']);

    expect($money->amount)->toBe('100');
});

it('fails validation for invalid amount', function () {
    MoneyData::validateAndCreate(['amount' => 'abc', 'currency' => 'RUB']);
})->throws(ValidationException::class);
