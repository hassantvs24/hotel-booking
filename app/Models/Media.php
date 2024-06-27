<?php

namespace App\Models;

use App\Traits\MediaMan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\Media
 *
 */
class Media extends Model
{
    use HasFactory, MediaMan;

    protected $guarded = ['id'];
    protected $appends = ['url'];
    protected $hidden = ['media_type', 'media_id'];

    public function media() :MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute() :string
    {
        return url('storage/' . $this->attributes['path'] . '/' . $this->attributes['name']);
    }

}
