<?php

namespace App\Http\Requests\Inventory;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    protected $errorBag = 'editEmployee';

    public function authorize(): bool
    {
        $user = $this->user();
        $employee = $this->route('employee');

        return $user !== null
            && $user->isManager()
            && $user->companyID !== null
            && $employee->companyID === $user->companyID
            && $employee->role === 'employee';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new PlainText],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('employee')->userID, 'userID'),
            ],
        ];
    }
}
