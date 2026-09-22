<?php

namespace App\Jobs;

use App\Models\DesiredTopic;
use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateDesiredTopicEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public DesiredTopic $desiredTopic) {}

    /**
     * Embeds the topic text so a desired topic can be matched against exercise
     * embeddings by meaning, the way SearchContentTool matches a query.
     *
     * Turning embedding search off in the admin settings stops every call to
     * the provider, including from jobs queued before it was turned off, so
     * the setting is checked when the job runs rather than when it was
     * dispatched. The write is quiet and by attribute: there is nothing to
     * react to in storing a vector, and embedding is not fillable, so mass
     * assignment would drop it.
     */
    public function handle(SiteSettings $settings): void
    {
        if (! $settings->embeddingSearchEnabled()) {
            return;
        }

        $topic = trim($this->desiredTopic->topic ?? '');

        if ($topic === '') {
            return;
        }

        $this->desiredTopic->embedding = Str::of($topic)->toEmbeddings();
        $this->desiredTopic->saveQuietly();
    }
}
