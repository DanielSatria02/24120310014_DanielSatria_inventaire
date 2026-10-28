<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $primaryKey = 'productID';

    protected $fillable = [
        'categoryID',
        'stockKeepUnit',
        'name',
        'stockQuantity',
        'minStockLevel',
        'purchasePrice',
        'sellingPrice',
    ];

    protected function casts(): array
    {
        return [
            'purchasePrice' => 'decimal:2',
            'sellingPrice' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'companyID', 'companyID');
    }

    public function isLowOnStock(): bool
    {
        return $this->stockQuantity <= $this->minStockLevel;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoryID', 'categoryID');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'productID', 'productID');
    }
}
