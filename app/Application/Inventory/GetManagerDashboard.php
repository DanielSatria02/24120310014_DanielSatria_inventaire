<?php

namespace App\Application\Inventory;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetManagerDashboard
{
    private const PANEL_LIMIT = 8;

    /**
     * @return array{
     *     revenue: float, expenses: float, net: float, valuation: float,
     *     products: LengthAwarePaginator, categories: Collection,
     *     lowStock: Collection, recentMovements: Collection
     * }
     */
    public function execute(int $companyID, ?string $search = null, int $perPage = 10): array
    {
        $totals = StockMovement::query()
            ->where('companyID', $companyID)
            ->selectRaw('skuType, SUM(totalAmount) as total')
            ->groupBy('skuType')
            ->pluck('total', 'skuType');

        $revenue = (float) ($totals[StockMovement::TYPE_OUTBOUND] ?? 0);
        $expenses = (float) ($totals[StockMovement::TYPE_INBOUND] ?? 0);

        $valuation = Product::query()
            ->where('companyID', $companyID)
            ->selectRaw('COALESCE(SUM(stockQuantity * purchasePrice), 0) as valuation')
            ->value('valuation');

        $products = Product::query()
            ->with('category:categoryID,name')
            ->where('companyID', $companyID)
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $lowStock = Product::query()
            ->where('companyID', $companyID)
            ->whereColumn('stockQuantity', '<=', 'minStockLevel')
            ->orderBy('stockQuantity')
            ->orderBy('name')
            ->limit(self::PANEL_LIMIT)
            ->get();

        $recentMovements = StockMovement::query()
            ->with(['product:productID,name', 'user:userID,name'])
            ->where('companyID', $companyID)
            ->orderByDesc('created_at')
            ->orderByDesc('stockMovementID')
            ->limit(self::PANEL_LIMIT)
            ->get();

        $categories = \App\Models\Category::query()
            ->where('companyID', $companyID)
            ->orderBy('name')
            ->get(['categoryID', 'name']);

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net' => round($revenue - $expenses, 2),
            'valuation' => (float) $valuation,
            'products' => $products,
            'categories' => $categories,
            'lowStock' => $lowStock,
            'recentMovements' => $recentMovements,
        ];
    }
}
