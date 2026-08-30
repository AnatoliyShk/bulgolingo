<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class UserLexema extends Pivot
{
    protected $table = 'user_lexema';

    public $incrementing = true;

    protected $fillable = [
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
    ];

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

    public static function lexemas(User $user): Collection
    {
        return static::join('lexemas', 'lexemas.id', '=', 'user_lexema.lexema_id')
            ->where('user_lexema.user_id', $user->id)
            ->select('lexemas.word')
            ->selectRaw('count(*) as count')
            ->groupBy('lexemas.id', 'lexemas.word')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'word'  => $row->word,
                'count' => (int) $row->count,
            ]);
    }
}
