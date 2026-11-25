<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_recipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_main')->default(false);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['ingredient_id', 'recipe_id']);
            $table->unique(['recipe_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_recipe');
    }
};
