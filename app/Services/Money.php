<?php

namespace App\Services;

use InvalidArgumentException;

final class Money
{
    public const MAX_MINOR = 999999999999;

    public static function minor(string $amount): int
    {
        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $amount)) {
            throw new InvalidArgumentException('Montant invalide.');
        }
        [$whole,$fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole) * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function decimal(int $minor): string
    {
        return ($minor < 0 ? '-' : '').intdiv(abs($minor), 100).'.'.str_pad((string) (abs($minor) % 100), 2, '0', STR_PAD_LEFT);
    }
}
