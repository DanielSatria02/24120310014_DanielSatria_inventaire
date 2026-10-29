<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $primaryKey = 'companyID';

    protected $fillable = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'companyID', 'companyID');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'companyID', 'companyID');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'companyID', 'companyID');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'companyID', 'companyID');
    }
}
