<?php

namespace App\Services;

use InvalidArgumentException;

final class StockQuantity
{
    public const MAX_MINOR = 999999999999;

    public const PATTERN = '/^\d{1,9}(?:\.\d{1,3})?$/D';

    public static function minor(string $quantity): int
    {
        if (! preg_match(self::PATTERN, $quantity)) {
            throw new InvalidArgumentException('Quantité invalide.');
        }
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '');

        return ((int) $whole) * 1000 + (int) str_pad($fraction, 3, '0');
    }

    public static function decimal(int $minor): string
    {
        return intdiv($minor, 1000).'.'.str_pad((string) ($minor % 1000), 3, '0', STR_PAD_LEFT);
    }
}
