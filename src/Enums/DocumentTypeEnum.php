<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum DocumentTypeEnum: string implements JsonSerializable
{
    case Order = 'order';
    case Invoice = 'invoice';
    case Shipment = 'shipment';
    case InvoiceForPayment = 'invoice_for_payment';
    case Return = 'return';
    case CommissionTransfer = 'commission_transfer';
    case CommissionReturn = 'commission_return';
    case CommissionReport = 'commission_report';
    case CashPayment = 'cash_payment';
    case CashReturn = 'cash_return';
    case NonCashPayment = 'non_cash_payment';
    case NonCashReturn = 'non_cash_return';
    case Correction = 'correction';
    case RightsTransfer = 'rights_transfer';
    case Other = 'other';

    private const array NAMES_RU = [
        'order' => 'Заказ',
        'invoice' => 'Счёт-фактура',
        'shipment' => 'Отпуск товара',
        'invoice_for_payment' => 'Счёт на оплату',
        'return' => 'Возврат товара',
        'commission_transfer' => 'Передача на комиссию',
        'commission_return' => 'Возврат комиссии',
        'commission_report' => 'Отчёт комиссионера',
        'cash_payment' => 'Выплата наличных',
        'cash_return' => 'Возврат наличных',
        'non_cash_payment' => 'Безналичная выплата',
        'non_cash_return' => 'Безналичный возврат',
        'correction' => 'Корректировка',
        'rights_transfer' => 'Передача прав',
        'other' => 'Прочее',
    ];

    private const array NAMES_EN = [
        'order' => 'Order',
        'invoice' => 'Invoice',
        'shipment' => 'Shipment',
        'invoice_for_payment' => 'Invoice for payment',
        'return' => 'Return',
        'commission_transfer' => 'Commission transfer',
        'commission_return' => 'Commission return',
        'commission_report' => 'Commission report',
        'cash_payment' => 'Cash payment',
        'cash_return' => 'Cash return',
        'non_cash_payment' => 'Non-cash payment',
        'non_cash_return' => 'Non-cash return',
        'correction' => 'Correction',
        'rights_transfer' => 'Rights transfer',
        'other' => 'Other',
    ];

    public function getLocalizedName(string $locale = 'ru'): string
    {
        return match ($locale) {
            'ru' => self::NAMES_RU[$this->value] ?? $this->name,
            'en' => self::NAMES_EN[$this->value] ?? $this->name,
            default => throw new InvalidArgumentException("Unsupported locale: {$locale}"),
        };
    }

    /** Требует обязательных полей delivery и payment согласно OpenAPI if/then */
    public function requiresDeliveryAndPayment(): bool
    {
        return $this === self::Order;
    }

    /** Является ли документ финансовым (операции с деньгами) */
    public function isFinancial(): bool
    {
        return in_array($this, [
            self::CashPayment, self::CashReturn,
            self::NonCashPayment, self::NonCashReturn,
            self::InvoiceForPayment,
        ], true);
    }

    /** Является ли документ логистическим (операции с товаром) */
    public function isLogistical(): bool
    {
        return in_array($this, [
            self::Shipment, self::Return,
            self::CommissionTransfer, self::CommissionReturn,
        ], true);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
