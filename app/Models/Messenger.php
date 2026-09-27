<?php

namespace App\Models;

use App\Enums\MessengerName;
use Database\Factories\MessengerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'messenger_name', 'messenger_user_id'])]
class Messenger extends Model
{
    /** @use HasFactory<MessengerFactory> */
    use HasFactory;

    protected $appends = ['messenger_label'];

    protected function casts(): array
    {
        return [
            'messenger_name' => MessengerName::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The messenger's display name, sent alongside the stored lowercase value
     * so the admin pages can show "WhatsApp" without a lookup of their own.
     */
    protected function messengerLabel(): Attribute
    {
        return Attribute::get(fn () => $this->messenger_name?->label());
    }
}
