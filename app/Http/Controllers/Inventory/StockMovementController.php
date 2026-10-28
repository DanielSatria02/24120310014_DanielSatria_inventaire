<?php

namespace App\Http\Controllers\Inventory;

use App\Application\Inventory\RecordStockMovement;
use App\Domain\Inventory\StockMovementRejectedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\RecordStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;

class StockMovementController extends Controller
{
    public function store(
        RecordStockMovementRequest $request,
        Product $product,
        RecordStockMovement $recordStockMovement,
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $recordStockMovement->execute(
                $request->user(),
                $product->productID,
                $data['skuType'],
                (int) $data['skuQuantity'],
                isset($data['unitPrice']) ? (string) $data['unitPrice'] : null,
                $data['skuNote'] ?? null,
            );
        } catch (StockMovementRejectedException $e) {
            return back()
                ->withInput()
                ->withErrors(['skuQuantity' => $e->getMessage()], 'recordMovement');
        }

        $message = $data['skuType'] === StockMovement::TYPE_INBOUND
            ? 'Restock recorded. It was added to expenses.'
            : 'Sale recorded. It was added to revenue.';

        return redirect()->route('home')->with('status', $message);
    }
}
