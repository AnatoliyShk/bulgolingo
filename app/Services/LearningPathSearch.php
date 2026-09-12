<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class LearningPathSearch
{
    /**
     * The same floor ExerciseController::search() applies, so the catalog and
     * the exercise search agree on what counts as related.
     */
    public const MIN_SIMILARITY = 0.4;

    /**
     * How many nearest exercises are gathered before they are grouped into
     * paths. The HNSW index only serves an ORDER BY distance ... LIMIT query,
     * so the bound is what keeps the lookup on the index; it is generous next
     * to the handful of paths a page shows, so a path is not missed because
     * another path's exercises crowded it out.
     */
    public const NEAREST_EXERCISES = 50;

    /**
     * Learning path ids related to $query, closest first, ranked by their
     * best-matching exercise. A path is only as relevant as the nearest of its
     * exercises: averaging would bury a path that covers the topic in one
     * lesson among many unrelated ones.
     *
     * The query is embedded once and the vector reused, since each string
     * handed to the vector helpers is embedded again on its own. Null means the
     * embedding call itself failed — a missing key or an unreachable provider —
     * which the caller reports as search being unavailable rather than as an
     * empty result, and which is reported here so it is not lost.
     *
     * @return Collection<int, int>|null
     */
    public function rankedPathIds(string $query): ?Collection
    {
        try {
            $vector = Str::of($query)->toEmbeddings(cache: true);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        $nearest = DB::table('exercises')
            ->select('id')
            ->selectVectorDistance('embedding', $vector, 'distance')
            ->whereNotNull('embedding')
            ->whereVectorSimilarTo('embedding', $vector, self::MIN_SIMILARITY)
            ->limit(self::NEAREST_EXERCISES);

        return DB::query()
            ->fromSub($nearest, 'nearest')
            ->join('exercise_lesson as el', 'el.exercise_id', '=', 'nearest.id')
            ->join('learning_path_lesson as lpl', 'lpl.lesson_id', '=', 'el.lesson_id')
            ->groupBy('lpl.learning_path_id')
            ->selectRaw('lpl.learning_path_id, min(nearest.distance) as distance')
            ->orderBy('distance')
            ->orderBy('lpl.learning_path_id')
            ->pluck('learning_path_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }
}
