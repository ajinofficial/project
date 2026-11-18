<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || preg_match('/[\r\n]/', $value)
            || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $fail(__('auth.enter_a_valid_email_address_2e09ed'));
        }
    }
}
