<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class UserLexema extends Pivot
{
    protected $table = 'user_lexema';

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
