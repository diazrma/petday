<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('species'); // dog, cat, bird, rodent, reptile, fish, other
            $table->string('breed')->nullable();
            $table->string('gender')->nullable(); // male | female
            $table->date('birthdate')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover')->nullable();
            $table->string('bio', 300)->nullable();
            $table->json('personality')->nullable(); // tags: brincalhão, dorminhoco...
            $table->string('color')->default('#f97316');
            $table->timestamps();
        });

        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'pet_id']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('moment'); // moment, milestone, walk, meal, health, play, nap
            $table->string('mood')->nullable(); // happy, sleepy, hungry, playful, grumpy, sick, loved
            $table->text('body')->nullable();
            $table->string('image')->nullable();
            $table->string('location')->nullable();
            $table->date('diary_date'); // dia do diário/calendário
            $table->unsignedInteger('paws_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamp('hidden_at')->nullable(); // moderação
            $table->timestamps();
            $table->index(['pet_id', 'diary_date']);
        });

        Schema::create('paws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('body', 500);
            $table->timestamps();
        });

        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('image')->nullable();
            $table->string('caption', 200)->nullable();
            $table->string('background')->default('from-orange-400 to-pink-500');
            $table->string('sticker')->nullable(); // emoji sticker
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['story_id', 'user_id']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vet_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('type')->default('checkup'); // checkup, vaccine, emergency, surgery, grooming, exam, return
            $table->string('reason', 500)->nullable();
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled, declined
            $table->text('vet_notes')->nullable();
            $table->timestamps();
            $table->index(['vet_id', 'scheduled_at']);
        });

        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->default('vaccine'); // vaccine, deworming, medication, weight, exam
            $table->string('title');
            $table->date('applied_on')->nullable();
            $table->date('next_due_on')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reportable');
            $table->string('reason');
            $table->string('status')->default('open'); // open, resolved, dismissed
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'reports', 'health_records', 'appointments', 'story_views', 'stories', 'comments', 'paws', 'posts', 'follows', 'pets'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
