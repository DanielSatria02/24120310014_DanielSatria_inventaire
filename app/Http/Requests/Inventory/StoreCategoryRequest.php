<?php

namespace App\Http\Requests\Inventory;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    protected $errorBag = 'addCategory';

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
        $unique = Rule::unique('categories', 'name')->where('companyID', $this->user()->companyID);
        $category = $this->route('category');

        if ($category !== null) {
            $unique->ignore($category->categoryID, 'categoryID');
        }

        return [
            'name' => ['required', 'string', 'max:255', new PlainText, $unique],
            'description' => ['nullable', 'string', 'max:1000', new PlainText],
        ];
    }
}
