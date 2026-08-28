<?php

namespace App\Models;

use Database\Factories\ImagesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['filepath'])]
class Images extends Model
{
    /** @use HasFactory<ImagesFactory> */
    use HasFactory;

    /**
     * Exercise pictures live on the bb_images S3 bucket rather than the local
     * public disk: the app is served from Laravel Cloud, whose containers keep
     * nothing an admin uploads past the next deploy.
     */
    public const DISK = 'bb_images';

    protected $appends = ['url'];

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_image', 'image_id', 'exercise_id');
    }

    /**
     * The bucket is private, so the player gets a signed URL rather than a
     * plain one. It is minted per render and expires in an hour, which is
     * ample for a page view and keeps the object unreachable afterwards.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk(self::DISK)->temporaryUrl($this->filepath, now()->addHour());
    }
}
