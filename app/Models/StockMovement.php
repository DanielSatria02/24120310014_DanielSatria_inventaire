<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_INBOUND = 'inbound';

    public const TYPE_OUTBOUND = 'outbound';

    protected $primaryKey = 'stockMovementID';

    protected $fillable = [
        'companyID',
        'productID',
        'userID',
        'skuType',
        'skuQuantity',
        'unitPrice',
        'totalAmount',
        'skuNote',
    ];

    protected function casts(): array
    {
        return [
            'unitPrice' => 'decimal:2',
            'totalAmount' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productID', 'productID');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }
}
