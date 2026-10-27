<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $primaryKey = 'stockMovementID';

    protected $fillable = [
        'productID',
        'userID',
        'skuType',
        'skuQuantity',
        'skuNote',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productID', 'productID');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }
}
