<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->string('mood', 20)->nullable()->after('sticker');
        });

        // Reações de pet nos rastros (uma por pessoa, pode trocar)
        Schema::create('story_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->timestamps();
            $table->unique(['story_id', 'user_id']);
        });

        // Recadinhos públicos nos rastros
        Schema::create('story_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('body', 300);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_comments');
        Schema::dropIfExists('story_reactions');
        Schema::table('stories', fn (Blueprint $table) => $table->dropColumn('mood'));
    }
};
