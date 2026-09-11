<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

#[Table('user_lexema', incrementing: true)]
#[Fillable([
    'user_id',
    'lexema_id',
    'reps_total',
    'stability',
    'difficulty',
    'state',
    'interval_days',
    'due_at',
    'last_reviewed_at',
    'lapses',
])]
class UserLexema extends Pivot
{
    protected function casts(): array
    {
        return [
            'stability' => 'float',
            'difficulty' => 'float',
            'interval_days' => 'integer',
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'lapses' => 'integer',
            'reps_total' => 'integer',
        ];
    }

    /**
     * The user's words weighted by reps_total, heaviest first, for the stats word
     * cloud. Rows are unique per word, so a row count would always be 1.
     */
    public static function lexemas(User $user): Collection
    {
        return static::join('lexemas', 'lexemas.id', '=', 'user_lexema.lexema_id')
            ->where('user_lexema.user_id', $user->id)
            ->select('lexemas.word', 'user_lexema.reps_total')
            ->orderByDesc('user_lexema.reps_total')
            ->orderBy('lexemas.word')
            ->get()
            ->map(fn ($row) => [
                'word' => $row->word,
                'count' => (int) $row->reps_total,
            ]);
    }
}
