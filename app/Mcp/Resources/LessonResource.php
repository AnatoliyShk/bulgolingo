<?php

namespace App\Mcp\Resources;

use App\Models\Exercise;
use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Reads a single lesson by uuid, with its ordered exercises.')]
#[MimeType('application/json')]
class LessonResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('lesson://{uuid}');
    }

    /**
     * The clause (an exercise's question and correct-answer payload) is left
     * out, same as ListExercisesTool: this server has no auth in front of it,
     * so returning clause would hand out answers to anyone who can reach it.
     */
    public function handle(Request $request): Response
    {
        $lesson = Lesson::query()->where('uuid', $request->get('uuid'))->first();

        if (! $lesson) {
            return Response::error("Lesson [{$request->get('uuid')}] not found.");
        }

        return Response::text(collect([
            'uuid' => $lesson->uuid,
            'name' => $lesson->name,
            'description' => $lesson->description,
            'exercises' => $lesson->exercises
                ->map(fn (Exercise $exercise) => [
                    'uuid' => $exercise->uuid,
                    'name' => $exercise->name,
                    'type' => $exercise->decision_type->value,
                ]),
        ])->toJson());
    }
}
