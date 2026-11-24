<?php

namespace App\Models;

use Database\Factories\DietaryTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description'])]
class DietaryTag extends Model
{
    /** @use HasFactory<DietaryTagFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
