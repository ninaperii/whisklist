<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allergen_ingredient', function (Blueprint $table): void {
            $table->foreignId('allergen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->primary(['ingredient_id', 'allergen_id']);
        });

        Schema::create('allergen_user', function (Blueprint $table): void {
            $table->foreignId('allergen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'allergen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allergen_user');
        Schema::dropIfExists('allergen_ingredient');
    }
};
