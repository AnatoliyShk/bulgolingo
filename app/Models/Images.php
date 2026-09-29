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
     * Deletes one object from the bucket. Avatars live on this disk too but
     * have no row of their own, so they are deleted by path through here.
     */
    public static function deleteFile(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }

    /**
     * Deletes the image's object and then its row; the row's `exercise_image`
     * links go with it through the cascade. The object goes first, so a failed
     * bucket delete leaves the row in place to retry from instead of an object
     * nothing points to any more.
     */
    public function deleteWithFile(): void
    {
        static::deleteFile($this->filepath);

        $this->delete();
    }

    /**
     * One-hour signed URL, as the bucket is private.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk(self::DISK)->temporaryUrl($this->filepath, now()->addHour());
    }
}
