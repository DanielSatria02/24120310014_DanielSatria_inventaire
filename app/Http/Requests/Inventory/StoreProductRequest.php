<?php

namespace App\Http\Requests\Inventory;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected $errorBag = 'addProduct';

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
        return [
            'name' => ['required', 'string', 'max:255', new PlainText],
            'categoryID' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'categoryID')->where('companyID', $this->user()->companyID),
            ],
            'minStockLevel' => ['required', 'integer', 'min:0', 'max:1000000'],
            'sellingPrice' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'categoryID' => 'category',
            'minStockLevel' => 'minimum stock level',
            'sellingPrice' => 'selling price',
        ];
    }
}
