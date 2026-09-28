<?php

namespace App\Services;

use App\Models\Exercise;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Throwable;

/**
 * The semantic search core the exercise and learning path searches share:
 * embedding a query, and matching exercises against it under the admin-set
 * similarity floor. Keeping both here means every search embeds a query the
 * same way and agrees on what counts as related.
 */
#[Singleton]
class SemanticSearchService
{
    public function __construct(private readonly SiteSettings $settings) {}

    /**
     * The query's embedding, for handing to nearestExercises(). Each string
     * passed to the vector query helpers is embedded again on its own, so a
     * search embeds once here and reuses the vector for the distance, the
     * filter and the ordering alike.
     *
     * Null means the embedding call itself failed — a missing key or an
     * unreachable provider — which callers report as search being
     * unavailable rather than as an empty result. The failure is reported
     * here so it is not lost once the caller has swallowed it.
     *
     * @return array<int, float>|null
     */
    public function embed(string $query): ?array
    {
        try {
            return Str::of($query)->toEmbeddings(cache: true);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Embedded exercises within the admin-set similarity floor of $vector,
     * closest first, each with its cosine distance selected as `distance`.
     *
     * $columns are selected before the distance rather than passed to get(),
     * which only sets columns that are not already selected and would
     * otherwise drop the distance expression. Callers add a limit: the HNSW
     * index only serves an ORDER BY distance ... LIMIT query.
     *
     * @param  array<int, float>  $vector
     * @param  list<string>  $columns
     * @return Builder<Exercise>
     */
    public function nearestExercises(array $vector, array $columns): Builder
    {
        return Exercise::query()
            ->select($columns)
            ->selectVectorDistance('embedding', $vector, 'distance')
            ->whereNotNull('embedding')
            ->whereVectorSimilarTo('embedding', $vector, $this->settings->embeddingMinSimilarity());
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
