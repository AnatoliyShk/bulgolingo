<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ScriptedLineCauseCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        $data = json_decode($value, true) ?? [];

        return array_merge([
            'line_text'    => null,
            'answer_options'    => [],
            'correct_option' => null,
        ], $data);
    }

    public function set($model, $key, $value, $attributes)
    {
        if(!isset($value['line_text'])) {
            throw new \Exception('Line text is required');
        }
        if(!isset($value['answer_options'])) {
            throw new \Exception('Answer options are required');
        }
        if(!isset($value['correct_option'])) {
            throw new \Exception('Correct option is required');
        }

        return json_encode($value);
    }
}
