<?php

namespace App\Domain\Inventory;

use DomainException;

final class StockMovementRejectedException extends DomainException
{
    public static function insufficientStock(string $product, int $available, int $requested): self
    {
        return new self(
            "Only {$available} of {$product} in stock, so {$requested} cannot be shipped out."
        );
    }

    public static function amountTooLarge(): self
    {
        return new self('This quantity and price add up to an amount that is too large to record.');
    }
}
