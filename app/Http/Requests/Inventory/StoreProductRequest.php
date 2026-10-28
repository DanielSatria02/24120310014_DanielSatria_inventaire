<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isManager() && $user->companyID !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $price = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'stockKeepUnit' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'stockKeepUnit')->where('companyID', $this->user()->companyID),
            ],
            'minStockLevel' => ['required', 'integer', 'min:0', 'max:1000000'],
            'purchasePrice' => $price,
            'sellingPrice' => $price,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'stockKeepUnit' => 'stock keeping unit',
            'minStockLevel' => 'minimum stock level',
            'purchasePrice' => 'purchase price',
            'sellingPrice' => 'selling price',
        ];
    }
}
