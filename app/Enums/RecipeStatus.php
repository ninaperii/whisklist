<?php

namespace App\Enums;

enum RecipeStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
