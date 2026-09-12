<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Read and written through App\Services\SiteSettings, which owns the defaults
 * and the cache in front of this table.
 */
#[Table('settings', key: 'key', keyType: 'string', incrementing: false)]
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
