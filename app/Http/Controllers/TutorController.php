<?php

namespace App\Http\Controllers;

use App\Ai\Agents\LanguageTutor;
use App\Http\Requests\AskTutorRequest;
use App\Services\SiteSettings;
use Illuminate\Http\StreamedEvent;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TutorController extends Controller
{
    public function __construct(private readonly SiteSettings $settings) {}

    /**
     * Streams the tutor's answer to the welcome page a token at a time.
     *
     * The reply only ever travels server to client, so this is an event
     * stream rather than a socket: no broadcast connection, no second daemon,
     * and a disconnected visitor stops the generator through
     * connection_aborted() inside eventStream.
     *
     * Only text deltas are forwarded. A tool call is part of how the answer is
     * reached, not part of the answer, and the catalog lookup behind it is not
     * the visitor's business. A provider failing mid-answer is sent as its own
     * event so the widget can say so in place of the caret, rather than
     * leaving a half-written sentence that looks finished.
     *
     * Each delta is yielded as an array rather than a string so the framing
     * survives it: eventStream writes a string straight after "data: ", and a
     * delta holding a newline would put its remainder on a line with no field
     * name, which a reader drops. An array is JSON encoded onto one line.
     */
    public function ask(AskTutorRequest $request, LanguageTutor $tutor): StreamedResponse
    {
        abort_unless($this->settings->tutorBotEnabled(), 404);

        $question = $request->validated('question');
        $history = $request->validated('history') ?? [];

        return response()->eventStream(function () use ($tutor, $question, $history) {
            try {
                foreach ($tutor->withHistory($history)->answer($question) as $event) {
                    if ($event instanceof TextDelta) {
                        yield ['delta' => $event->delta];
                    }
                }
            } catch (Throwable $e) {
                report($e);

                yield new StreamedEvent('error', ['message' => 'The tutor is unavailable right now. Please try again in a moment.']);
            }
        });
    }
}
