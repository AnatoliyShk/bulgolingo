<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Requires a correct_option to point at one of the options beside it, the same
 * range the exercises_clause_*_check constraints enforce in the database, so an
 * admin sees a form error rather than a failed insert. The options are read
 * from the sibling key of the attribute under validation; when they are not a
 * list the options rule reports that instead.
 */
class OptionIndex implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $options = Arr::get($this->data, Str::beforeLast($attribute, '.').'.options');

        if (! is_array($options)) {
            return;
        }

        if (! is_int($value) || $value < 0 || $value >= count($options)) {
            $fail('The correct answer must be one of the options.');
        }
    }
}
