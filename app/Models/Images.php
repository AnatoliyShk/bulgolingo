<?php

namespace App\Models;

use Database\Factories\ImagesFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['filepath'])]
#[Appends(['url'])]
class Images extends Model
{
    /** @use HasFactory<ImagesFactory> */
    use HasFactory;

    /**
     * S3 bucket, since Laravel Cloud containers lose local uploads on deploy.
     */
    public const DISK = 'bb_images';

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_image', 'image_id', 'exercise_id');
    }

    /**
     * One-hour signed URL, as the bucket is private.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk(self::DISK)->temporaryUrl($this->filepath, now()->addHour());
    }
}
