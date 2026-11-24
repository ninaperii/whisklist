<?php

namespace App\Models;

use Database\Factories\AllergenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description'])]
class Allergen extends Model
{
    /** @use HasFactory<AllergenFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
