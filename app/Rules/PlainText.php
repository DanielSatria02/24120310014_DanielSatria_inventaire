<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Defence in depth against stored XSS: free-text fields cannot carry HTML tags.
 * Output is still escaped by Blade; this just keeps tag-like input out of the database.
 */
class PlainText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/[<>]/', $value)) {
            $fail('The :attribute may not contain the characters < or >.');
        }
    }
}
