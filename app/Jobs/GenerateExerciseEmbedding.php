<?php

namespace App\Jobs;

use App\Enums\ExerciseType;
use App\Enums\LanguageCode;
use App\Models\Exercise;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateExerciseEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Exercise $exercise) {}

    /**
     * Embeds the exercise's clause text through the Laravel AI SDK and stores
     * the vector. A clause with no text leaves the embedding untouched rather
     * than paying for a vector of an empty string.
     *
     * The write is quiet because the observer's updating hooks exist for admin
     * edits: they re-validate the clause and deal a word-pair board afresh, and
     * storing a vector is neither an edit nor a reason to move a student's
     * cards. Assigning the attribute also sidesteps mass assignment, which
     * would silently drop embedding since it is not fillable.
     */
    public function handle(): void
    {
        $text = $this->textToVectorize();

        if ($text === '') {
            return;
        }

        $this->exercise->embedding = Str::of($text)->toEmbeddings();
        $this->exercise->saveQuietly();
    }

    /**
     * One labelled line per clause field that carries language, chosen by the
     * exercise type since each type stores a different clause shape (see
     * ExerciseType::dataRules()). The answer is spelled out as words rather than
     * left as correct_option's index or boolean, which would mean nothing to the
     * embedding model; the word-pair board order is left out for the same reason.
     *
     * The type's name heads the text in English and in Bulgarian, since the
     * search can be asked in either language, but only once some content line
     * exists, so a clause with no text still comes back empty and is not embedded.
     */
    private function textToVectorize(): string
    {
        $clause = $this->exercise->clause ?? [];
        $type = $this->exercise->decision_type;

        $fields = match ($type) {
            ExerciseType::MULTIPLE_CHOICE => [
                'Word pairs' => $this->wordPairs($clause),
            ],
            ExerciseType::TRUE_FALSE => [
                'Statement' => $clause['sentence'] ?? null,
                'Answer' => is_bool($clause['correct_option'] ?? null) ? ($clause['correct_option'] ? 'true' : 'false') : null,
            ],
            ExerciseType::FILL_IN_THE_BLANK => [
                'Sentence' => $clause['sentence'] ?? null,
                'Options' => $this->options($clause),
                'Answer' => $this->chosenOption($clause),
            ],
            ExerciseType::IMAGE_MATCHING => [
                'Options' => $this->options($clause),
                'Answer' => $this->chosenOption($clause),
            ],
            default => [],
        };

        $lines = collect($fields + ['Explanation' => $clause['explanation'] ?? null])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value, string $label) => "{$label}: {$value}");

        if ($lines->isEmpty()) {
            return '';
        }

        return $lines
            ->when($type, fn ($lines) => $lines
                ->prepend('Тип упражнение: '.$type->getDescription(LanguageCode::BG))
                ->prepend('Exercise type: '.$type->getDescription(LanguageCode::EN)))
            ->implode("\n");
    }

    private function wordPairs(array $clause): string
    {
        return collect($clause['pairs'] ?? [])
            ->filter(fn ($pair) => is_array($pair))
            ->map(fn (array $pair) => implode(' = ', array_filter($pair, 'is_string')))
            ->implode('; ');
    }

    private function options(array $clause): string
    {
        return collect($clause['options'] ?? [])
            ->filter(fn ($option) => is_string($option))
            ->implode(', ');
    }

    private function chosenOption(array $clause): ?string
    {
        $index = $clause['correct_option'] ?? null;
        $option = is_int($index) ? ($clause['options'][$index] ?? null) : null;

        return is_string($option) ? $option : null;
    }
}
