<?php

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->unsignedSmallInteger('preparation_minutes')->default(0);
            $table->unsignedSmallInteger('cooking_minutes')->default(0);
            $table->unsignedSmallInteger('servings');
            $table->string('difficulty')->default(RecipeDifficulty::Easy->value);
            $table->string('status')->default(RecipeStatus::Draft->value)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
