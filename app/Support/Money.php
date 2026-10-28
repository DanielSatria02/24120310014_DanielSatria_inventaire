<?php

namespace App\Support;

final class Money
{
    public static function format(float|int|string|null $amount): string
    {
        $value = (float) $amount;
        $decimals = fmod($value, 1.0) === 0.0 ? 0 : 2;

        return 'Rp '.number_format($value, $decimals, ',', '.');
    }
}
