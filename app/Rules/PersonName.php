<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts human names only: Unicode letters and combining marks, separated by
 * spaces, apostrophes or hyphens; a part may end with a dot ("J. R."). Rejects
 * digits, domains, URLs, markup and the symbols registration spam bots stuff
 * into name fields.
 */
class PersonName implements ValidationRule
{
    private const PATTERN = "/^\p{L}[\p{L}\p{M}]*\.?(?:[ '’\-]\p{L}[\p{L}\p{M}]*\.?)*$/u";

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail('The :attribute may only contain letters, spaces, apostrophes, hyphens and dots.');
        }
    }
}
