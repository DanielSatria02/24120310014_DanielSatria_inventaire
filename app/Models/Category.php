<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $primaryKey = 'categoryID';

    protected $fillable = ['name', 'description'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'companyID', 'companyID');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'categoryID', 'categoryID');
    }
}
