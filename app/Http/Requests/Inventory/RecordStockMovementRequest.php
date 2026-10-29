<?php

namespace App\Http\Requests\Inventory;

use App\Models\StockMovement;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordStockMovementRequest extends FormRequest
{
    protected $errorBag = 'recordMovement';

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ! $user->isManager() && $user->companyID !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'movementProduct' => ['required', 'integer'],
            'skuType' => ['required', Rule::in([StockMovement::TYPE_INBOUND, StockMovement::TYPE_OUTBOUND])],
            'skuQuantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unitPrice' => [
                Rule::requiredIf(fn () => $this->input('skuType') === StockMovement::TYPE_INBOUND),
                'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000000',
            ],
            'skuNote' => ['nullable', 'string', 'max:255', new PlainText],
        ];
    }

    public function attributes(): array
    {
        return [
            'skuType' => 'movement type',
            'skuQuantity' => 'quantity',
            'unitPrice' => 'purchase price',
            'skuNote' => 'note',
        ];
    }
}
