<?php

namespace App\Http\Requests\Inventory;

class UpdateProductRequest extends StoreProductRequest
{
    protected $errorBag = 'editProduct';

    public function authorize(): bool
    {
        $product = $this->route('product');

        return parent::authorize() && $product->companyID === $this->user()->companyID;
    }
}
