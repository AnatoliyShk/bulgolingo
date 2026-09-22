<?php

namespace App\Services;

use App\Models\Exercise;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

#[Singleton]
class ExerciseSearch
{
    public function __construct(private readonly SiteSettings $settings) {}

    /**
     * Exercises related to $query, closest first, each carrying the distance
     * it sorted on and the lessons and paths holding it. What counts as
     * related is the admin-set similarity floor, shared with the learning
     * path search and ExerciseController::search().
     *
     * The query is embedded once and the vector reused for the distance and
     * the filter alike, since each string handed to the vector helpers is
     * embedded again on its own. The columns are selected before the distance
     * rather than passed to get(), which only sets columns that are not
     * already selected and would otherwise drop the distance expression.
     *
     * Null means the embedding call itself failed — a missing key or an
     * unreachable provider — which callers report as search being unavailable
     * rather than as an empty result, and which is reported here so it is not
     * lost.
     *
     * @return Collection<int, Exercise>|null
     */
    public function search(string $query, int $limit = 10): ?Collection
    {
        try {
            $vector = Str::of($query)->toEmbeddings(cache: true);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return Exercise::query()
            ->select(['id', 'uuid', 'name', 'decision_type'])
            ->selectVectorDistance('embedding', $vector, 'distance')
            ->whereNotNull('embedding')
            ->whereVectorSimilarTo('embedding', $vector, $this->settings->embeddingMinSimilarity())
            ->with('lessons.learningPath')
            ->limit($limit)
            ->get();
    }

    /**
     * The similarity behind a result's distance, on the 0-to-1 scale the admin
     * floor is set in rather than the cosine distance the index sorts on.
     */
    public function similarity(Exercise $exercise): float
    {
        return round(1 - (float) $exercise->distance, 3);
    }
}
