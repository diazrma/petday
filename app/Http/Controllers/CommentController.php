<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Notifications\PetDayNotification;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:500']]);
        $user = $request->user();
        $pet = $user->currentPet();

        $post->comments()->create(['user_id' => $user->id, 'pet_id' => $pet?->id, 'body' => $data['body']]);
        $post->increment('comments_count');

        if ($post->user_id !== $user->id) {
            $post->user->notify(new PetDayNotification('💬', ($pet?->name ?? $user->name).' comentou no post de '.$post->pet->name, route('posts.show', $post)));
        }

        return back();
    }

    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();
        abort_unless($comment->user_id === $user->id || $comment->post->user_id === $user->id || $user->isAdmin(), 403);
        $comment->post->decrement('comments_count');
        $comment->delete();

        return back();
    }
}
