<?php

namespace App\Jobs;

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

    public function handle(): void
    {
        // Формируем единый текст для генерации смысла упражнения
        $textToVectorize = "Заголовок: {$this->exercise->title}. " .
            "Тема: {$this->exercise->topic_description}. " .
            "Текст: {$this->exercise->content_bg}";

        // Генерация векторов через хелпер Laravel AI SDK
        $vector = Str::of($textToVectorize)->toEmbeddings();

        $this->exercise->update([
            'embedding' => $vector,
        ]);
    }
}
