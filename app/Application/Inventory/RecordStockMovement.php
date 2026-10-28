<?php

namespace App\Application\Inventory;

use App\Domain\Inventory\StockMovementRejectedException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RecordStockMovement
{
    private const MAX_AMOUNT = 9999999999999.99;

    private const MAX_STOCK = 2000000000;

    /**
     * Inbound adds stock at the price the employee entered (an expense).
     * Outbound removes stock at the product's selling price (revenue).
     */
    public function execute(
        User $employee,
        int $productID,
        string $type,
        int $quantity,
        ?string $inboundUnitPrice,
        ?string $note,
    ): StockMovement {
        return DB::transaction(function () use ($employee, $productID, $type, $quantity, $inboundUnitPrice, $note) {
            // Lock the row so two simultaneous requests cannot oversell the same stock.
            $product = Product::query()
                ->where('companyID', $employee->companyID)
                ->lockForUpdate()
                ->findOrFail($productID);

            $inbound = $type === StockMovement::TYPE_INBOUND;
            $unitPrice = $inbound ? (float) $inboundUnitPrice : (float) $product->sellingPrice;
            $totalAmount = round($unitPrice * $quantity, 2);

            if ($totalAmount > self::MAX_AMOUNT) {
                throw StockMovementRejectedException::amountTooLarge();
            }

            if ($inbound) {
                if ($product->stockQuantity + $quantity > self::MAX_STOCK) {
                    throw StockMovementRejectedException::amountTooLarge();
                }
                $product->stockQuantity += $quantity;
            } else {
                if ($quantity > $product->stockQuantity) {
                    throw StockMovementRejectedException::insufficientStock(
                        $product->name,
                        $product->stockQuantity,
                        $quantity,
                    );
                }
                $product->stockQuantity -= $quantity;
            }

            $product->save();

            return StockMovement::create([
                'companyID' => $employee->companyID,
                'productID' => $product->productID,
                'userID' => $employee->userID,
                'skuType' => $type,
                'skuQuantity' => $quantity,
                'unitPrice' => $unitPrice,
                'totalAmount' => $totalAmount,
                'skuNote' => $note,
            ]);
        });
    }
}
