<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table): void {
            $table->id();
            $table->morphs('imageable');
            $table->string('path')->unique();
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('position')->default(1);
            $table->boolean('is_thumbnail')->default(false);
            $table->timestamps();
            $table->unique(['imageable_type', 'imageable_id', 'position'], 'images_owner_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
