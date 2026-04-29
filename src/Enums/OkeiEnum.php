<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Классификатор единиц измерения по ОКЕИ (ОК 015-94 / 2014)
 * Полностью соответствует схеме `Unit` из CommerceJSON OpenAPI v1.0.8
 *
 * @phpstan-immutable
 */
enum OkeiEnum: string implements JsonSerializable
{
    // ─── Счётные и упаковочные ───────────────────────────────────
    case UNIT_PIECE = '796';
    case UNIT_PAIR = '794';
    case UNIT_SET = '798';
    case UNIT_KIT = '799';
    case UNIT_PACKAGE = '771';
    case UNIT_PACK = '797';
    case UNIT_BOX = '721';
    case UNIT_BOTTLE = '831';
    case UNIT_SHEET = '715';
    case UNIT_CARD = '718';
    case UNIT_ROLL = '728';
    case UNIT_BAG = '791';
    case UNIT_SACK = '792';
    case UNIT_CRATE = '779';
    case UNIT_BARREL = '815';
    case UNIT_JAR = '817';
    case UNIT_FLACON = '821';
    case UNIT_BALLOON = '832';
    case UNIT_CONTAINER = '835';
    case UNIT_PALLET = '778';
    case UNIT_CASE = '714';

    // ─── Массовые ────────────────────────────────────────────────
    case UNIT_KILOGRAM = '166';
    case UNIT_GRAM = '162';
    case UNIT_MILLIGRAM = '163';
    case UNIT_TONNE = '168';
    case UNIT_QUINTAL = '165';

    // ─── Линейные ────────────────────────────────────────────────
    case UNIT_METER = '004';
    case UNIT_KILOMETER = '009';
    case UNIT_MILLIMETER = '008';
    case UNIT_CENTIMETER = '085';
    case UNIT_RUNNING_METER = '081';

    // ─── Площадные ───────────────────────────────────────────────
    case UNIT_SQUARE_METER = '055';
    case UNIT_SQUARE_CENTIMETER = '071';
    case UNIT_SQUARE_MILLIMETER = '056';
    case UNIT_HECTARE = '014';

    // ─── Объёмные ────────────────────────────────────────────────
    case UNIT_LITER = '006';
    case UNIT_MILLILITER = '016';
    case UNIT_CUBIC_METER = '001';
    case UNIT_CUBIC_CENTIMETER = '003';
    case UNIT_CUBIC_DECIMETER = '005';

    // ─── Электрические, технические и время ──────────────────────
    case UNIT_WATT = '031';
    case UNIT_VOLT = '035';
    case UNIT_AMPERE = '040';
    case UNIT_HERTZ = '042';
    case UNIT_KILOWATT_HOUR = '032';
    case UNIT_HOUR = '064';
    case UNIT_MINUTE = '052';
    case UNIT_SECOND = '061';
    case UNIT_DAY = '051';

    // ─── Методы доступа к атрибутам ──────────────────────────────
    public function getCode(): string
    {
        return $this->value;
    }

    public function getFullName(): string
    {
        return match ($this) {
            self::UNIT_PIECE => 'Штука',
            self::UNIT_PAIR => 'Пара',
            self::UNIT_SET => 'Комплект',
            self::UNIT_KIT => 'Набор',
            self::UNIT_PACKAGE => 'Пакет',
            self::UNIT_PACK => 'Упаковка',
            self::UNIT_BOX => 'Коробка',
            self::UNIT_BOTTLE => 'Бутылка',
            self::UNIT_SHEET => 'Лист',
            self::UNIT_CARD => 'Карточка',
            self::UNIT_ROLL => 'Рулон',
            self::UNIT_BAG => 'Мешок',
            self::UNIT_SACK => 'Мешок (сыпучий)',
            self::UNIT_CRATE => 'Ящик',
            self::UNIT_BARREL => 'Бочка',
            self::UNIT_JAR => 'Банка',
            self::UNIT_FLACON => 'Флакон',
            self::UNIT_BALLOON => 'Баллон',
            self::UNIT_CONTAINER => 'Контейнер',
            self::UNIT_PALLET => 'Поддон (паллет)',
            self::UNIT_CASE => 'Чехол/Футляр',
            self::UNIT_KILOGRAM => 'Килограмм',
            self::UNIT_GRAM => 'Грамм',
            self::UNIT_MILLIGRAM => 'Миллиграмм',
            self::UNIT_TONNE => 'Тонна',
            self::UNIT_QUINTAL => 'Центнер',
            self::UNIT_METER => 'Метр',
            self::UNIT_KILOMETER => 'Километр',
            self::UNIT_MILLIMETER => 'Миллиметр',
            self::UNIT_CENTIMETER => 'Сантиметр',
            self::UNIT_RUNNING_METER => 'Метр погонный',
            self::UNIT_SQUARE_METER => 'Квадратный метр',
            self::UNIT_SQUARE_CENTIMETER => 'Квадратный сантиметр',
            self::UNIT_SQUARE_MILLIMETER => 'Квадратный миллиметр',
            self::UNIT_HECTARE => 'Гектар',
            self::UNIT_LITER => 'Литр',
            self::UNIT_MILLILITER => 'Миллилитр',
            self::UNIT_CUBIC_METER => 'Кубический метр',
            self::UNIT_CUBIC_CENTIMETER => 'Кубический сантиметр',
            self::UNIT_CUBIC_DECIMETER => 'Кубический дециметр',
            self::UNIT_WATT => 'Ватт',
            self::UNIT_VOLT => 'Вольт',
            self::UNIT_AMPERE => 'Ампер',
            self::UNIT_HERTZ => 'Герц',
            self::UNIT_KILOWATT_HOUR => 'Киловатт-час',
            self::UNIT_HOUR => 'Час',
            self::UNIT_MINUTE => 'Минута',
            self::UNIT_SECOND => 'Секунда',
            self::UNIT_DAY => 'Сутки',
        };
    }

    public function getShortName(): string
    {
        return match ($this) {
            self::UNIT_PIECE => 'шт',
            self::UNIT_PAIR => 'пар',
            self::UNIT_SET => 'компл',
            self::UNIT_KIT => 'набор',
            self::UNIT_PACKAGE => 'пак',
            self::UNIT_PACK => 'упак',
            self::UNIT_BOX => 'кор',
            self::UNIT_BOTTLE => 'бут',
            self::UNIT_SHEET => 'лист',
            self::UNIT_CARD => 'карт',
            self::UNIT_ROLL => 'рул',
            self::UNIT_BAG => 'меш',
            self::UNIT_SACK => 'меш',
            self::UNIT_CRATE => 'ящ',
            self::UNIT_BARREL => 'боч',
            self::UNIT_JAR => 'банк',
            self::UNIT_FLACON => 'фл',
            self::UNIT_BALLOON => 'балл',
            self::UNIT_CONTAINER => 'конт',
            self::UNIT_PALLET => 'пал',
            self::UNIT_CASE => 'чех',
            self::UNIT_KILOGRAM => 'кг',
            self::UNIT_GRAM => 'г',
            self::UNIT_MILLIGRAM => 'мг',
            self::UNIT_TONNE => 'т',
            self::UNIT_QUINTAL => 'ц',
            self::UNIT_METER => 'м',
            self::UNIT_KILOMETER => 'км',
            self::UNIT_MILLIMETER => 'мм',
            self::UNIT_CENTIMETER => 'см',
            self::UNIT_RUNNING_METER => 'м.п.',
            self::UNIT_SQUARE_METER => 'м²',
            self::UNIT_SQUARE_CENTIMETER => 'см²',
            self::UNIT_SQUARE_MILLIMETER => 'мм²',
            self::UNIT_HECTARE => 'га',
            self::UNIT_LITER => 'л',
            self::UNIT_MILLILITER => 'мл',
            self::UNIT_CUBIC_METER => 'м³',
            self::UNIT_CUBIC_CENTIMETER => 'см³',
            self::UNIT_CUBIC_DECIMETER => 'дм³',
            self::UNIT_WATT => 'Вт',
            self::UNIT_VOLT => 'В',
            self::UNIT_AMPERE => 'А',
            self::UNIT_HERTZ => 'Гц',
            self::UNIT_KILOWATT_HOUR => 'кВт·ч',
            self::UNIT_HOUR => 'ч',
            self::UNIT_MINUTE => 'мин',
            self::UNIT_SECOND => 'с',
            self::UNIT_DAY => 'сут',
        };
    }

    public function getInternational(): string
    {
        return match ($this) {
            self::UNIT_PIECE => 'pce',
            self::UNIT_PAIR => 'pair',
            self::UNIT_SET => 'set',
            self::UNIT_KIT => 'kit',
            self::UNIT_PACKAGE => 'pkg',
            self::UNIT_PACK => 'pack',
            self::UNIT_BOX => 'bx',
            self::UNIT_BOTTLE => 'btl',
            self::UNIT_SHEET => 'sh',
            self::UNIT_CARD => 'crd',
            self::UNIT_ROLL => 'roll',
            self::UNIT_BAG => 'bag',
            self::UNIT_SACK => 'sack',
            self::UNIT_CRATE => 'crate',
            self::UNIT_BARREL => 'bbl',
            self::UNIT_JAR => 'jar',
            self::UNIT_FLACON => 'flac',
            self::UNIT_BALLOON => 'cyl',
            self::UNIT_CONTAINER => 'cont',
            self::UNIT_PALLET => 'pal',
            self::UNIT_CASE => 'case',
            self::UNIT_KILOGRAM => 'kg',
            self::UNIT_GRAM => 'g',
            self::UNIT_MILLIGRAM => 'mg',
            self::UNIT_TONNE => 't',
            self::UNIT_QUINTAL => 'c',
            self::UNIT_METER => 'm',
            self::UNIT_KILOMETER => 'km',
            self::UNIT_MILLIMETER => 'mm',
            self::UNIT_CENTIMETER => 'cm',
            self::UNIT_RUNNING_METER => 'lm',
            self::UNIT_SQUARE_METER => 'm²',
            self::UNIT_SQUARE_CENTIMETER => 'cm²',
            self::UNIT_SQUARE_MILLIMETER => 'mm²',
            self::UNIT_HECTARE => 'ha',
            self::UNIT_LITER => 'l',
            self::UNIT_MILLILITER => 'ml',
            self::UNIT_CUBIC_METER => 'm³',
            self::UNIT_CUBIC_CENTIMETER => 'cm³',
            self::UNIT_CUBIC_DECIMETER => 'dm³',
            self::UNIT_WATT => 'W',
            self::UNIT_VOLT => 'V',
            self::UNIT_AMPERE => 'A',
            self::UNIT_HERTZ => 'Hz',
            self::UNIT_KILOWATT_HOUR => 'kWh',
            self::UNIT_HOUR => 'h',
            self::UNIT_MINUTE => 'min',
            self::UNIT_SECOND => 's',
            self::UNIT_DAY => 'd',
        };
    }

    public function getLocalizedName(string $locale = 'ru'): string
    {
        return match (strtolower($locale)) {
            'ru' => $this->getFullName(),
            'en' => $this->getEnglishName(),
            default => throw new InvalidArgumentException("Unsupported locale: {$locale}"),
        };
    }

    private function getEnglishName(): string
    {
        return match ($this) {
            self::UNIT_PIECE => 'Piece',
            self::UNIT_PAIR => 'Pair',
            self::UNIT_SET => 'Set',
            self::UNIT_KIT => 'Kit',
            self::UNIT_PACKAGE => 'Package',
            self::UNIT_PACK => 'Pack',
            self::UNIT_BOX => 'Box',
            self::UNIT_BOTTLE => 'Bottle',
            self::UNIT_SHEET => 'Sheet',
            self::UNIT_CARD => 'Card',
            self::UNIT_ROLL => 'Roll',
            self::UNIT_BAG => 'Bag',
            self::UNIT_SACK => 'Sack',
            self::UNIT_CRATE => 'Crate',
            self::UNIT_BARREL => 'Barrel',
            self::UNIT_JAR => 'Jar',
            self::UNIT_FLACON => 'Flacon',
            self::UNIT_BALLOON => 'Balloon/Cylinder',
            self::UNIT_CONTAINER => 'Container',
            self::UNIT_PALLET => 'Pallet',
            self::UNIT_CASE => 'Case',
            self::UNIT_KILOGRAM => 'Kilogram',
            self::UNIT_GRAM => 'Gram',
            self::UNIT_MILLIGRAM => 'Milligram',
            self::UNIT_TONNE => 'Tonne',
            self::UNIT_QUINTAL => 'Quintal',
            self::UNIT_METER => 'Metre',
            self::UNIT_KILOMETER => 'Kilometre',
            self::UNIT_MILLIMETER => 'Millimetre',
            self::UNIT_CENTIMETER => 'Centimetre',
            self::UNIT_RUNNING_METER => 'Running metre',
            self::UNIT_SQUARE_METER => 'Square metre',
            self::UNIT_SQUARE_CENTIMETER => 'Square centimetre',
            self::UNIT_SQUARE_MILLIMETER => 'Square millimetre',
            self::UNIT_HECTARE => 'Hectare',
            self::UNIT_LITER => 'Litre',
            self::UNIT_MILLILITER => 'Millilitre',
            self::UNIT_CUBIC_METER => 'Cubic metre',
            self::UNIT_CUBIC_CENTIMETER => 'Cubic centimetre',
            self::UNIT_CUBIC_DECIMETER => 'Cubic decimetre',
            self::UNIT_WATT => 'Watt',
            self::UNIT_VOLT => 'Volt',
            self::UNIT_AMPERE => 'Ampere',
            self::UNIT_HERTZ => 'Hertz',
            self::UNIT_KILOWATT_HOUR => 'Kilowatt-hour',
            self::UNIT_HOUR => 'Hour',
            self::UNIT_MINUTE => 'Minute',
            self::UNIT_SECOND => 'Second',
            self::UNIT_DAY => 'Day',
        };
    }

    /**
     * Преобразует enum в структуру, ожидаемую OpenAPI схемой `Unit`
     * Идеально для сериализации в JSON-ответы API
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'full_name' => $this->getFullName(),
            'short_name' => $this->getShortName(),
            'international' => $this->getInternational(),
        ];
    }

    // ─── JSON сериализация ───────────────────────────────────────
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // ─── Безопасные фабричные методы (валидация на входе) ────────
    public static function fromCode(string $code): self
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        $unit = self::tryFrom($code);

        if ($unit === null) {
            throw new InvalidArgumentException("Invalid OKEI unit code: '{$code}'");
        }

        return $unit;
    }

    public static function tryFromCode(string $code): ?self
    {
        $code = preg_replace('/[^0-9]/', '', $code);

        return self::tryFrom($code);
    }

    /**
     * Быстрый поиск по коду ОКЕИ без создания экземпляра (для валидаторов, мапперов)
     */
    public static function isValidCode(string $code): bool
    {
        $cleanedCode = preg_replace('/[^0-9]/', '', $code);
        foreach (self::cases() as $case) {
            if ($case->value === $cleanedCode) {
                return true;
            }
        }

        return false;
    }
}
