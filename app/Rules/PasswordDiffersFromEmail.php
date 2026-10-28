<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordDiffersFromEmail implements ValidationRule
{
    public function __construct(private readonly string $email) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $local = strstr($this->email, '@', true) ?: $this->email;

        if ($this->email !== '' && (strcasecmp((string) $value, $this->email) === 0 || strcasecmp((string) $value, $local) === 0)) {
            $fail('The password must not be the same as the email address.');
        }
    }
}
