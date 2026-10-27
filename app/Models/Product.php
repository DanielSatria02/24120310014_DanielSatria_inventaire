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
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoryID', 'categoryID');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'productID', 'productID');
    }
}
