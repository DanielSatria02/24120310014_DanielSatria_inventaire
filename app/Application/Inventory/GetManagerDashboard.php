<?php

namespace App\Application\Inventory;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetManagerDashboard
{
    /**
     * @return array{revenue: float, expenses: float, valuation: float, products: LengthAwarePaginator}
     */
    public function execute(int $companyID, ?string $search = null, int $perPage = 10): array
    {
        $totals = StockMovement::query()
            ->where('companyID', $companyID)
            ->selectRaw('skuType, SUM(totalAmount) as total')
            ->groupBy('skuType')
            ->pluck('total', 'skuType');

        $valuation = Product::query()
            ->where('companyID', $companyID)
            ->selectRaw('COALESCE(SUM(stockQuantity * purchasePrice), 0) as valuation')
            ->value('valuation');

        $products = Product::query()
            ->where('companyID', $companyID)
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'revenue' => (float) ($totals[StockMovement::TYPE_OUTBOUND] ?? 0),
            'expenses' => (float) ($totals[StockMovement::TYPE_INBOUND] ?? 0),
            'valuation' => (float) $valuation,
            'products' => $products,
        ];
    }
}
