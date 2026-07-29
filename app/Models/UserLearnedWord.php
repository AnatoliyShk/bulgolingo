<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class UserLearnedWord extends Pivot
{
    protected $table = 'user_learned_word';

    public static function learnedWords(User $user): Collection
    {
        return static::join('learned_words', 'learned_words.id', '=', 'user_learned_word.learned_word_id')
            ->where('user_learned_word.user_id', $user->id)
            ->select('learned_words.word')
            ->selectRaw('count(*) as count')
            ->groupBy('learned_words.id', 'learned_words.word')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'word'  => $row->word,
                'count' => (int) $row->count,
            ]);
    }
}
