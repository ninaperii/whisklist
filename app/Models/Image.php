<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['path', 'alt_text', 'position', 'is_thumbnail'])]
class Image extends Model
{
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
