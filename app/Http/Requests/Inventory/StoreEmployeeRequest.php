<?php

namespace App\Http\Requests\Inventory;

use App\Models\User;
use App\Rules\PasswordDiffersFromEmail;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{
    protected $errorBag = 'addEmployee';

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isManager() && $user->companyID !== null;
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                Password::defaults(),
                'not_regex:/^\s|\s$/',
                new PasswordDiffersFromEmail((string) $this->input('email')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.not_regex' => 'The password must not start or end with a space.',
        ];
    }
}
