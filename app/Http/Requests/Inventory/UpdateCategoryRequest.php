<?php

namespace App\Http\Requests\Inventory;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    protected $errorBag = 'editCategory';

    public function authorize(): bool
    {
        return parent::authorize()
            && $this->route('category')->companyID === $this->user()->companyID;
    }
}
