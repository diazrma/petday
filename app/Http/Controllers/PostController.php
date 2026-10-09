<?php

namespace App\Http\Controllers;

use App\Models\Paw;
use App\Models\Post;
use App\Notifications\PetDayNotification;
use App\Services\PetDetector;
use App\Rules\ContainsPet;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function store(Request $request, PetDetector $detector)
    {
        $pet = $request->user()->currentPet();
        if (! $pet) {
            return redirect()->route('pets.create')->with('error', 'Cadastre um pet antes de postar.');
        }

        $data = $request->validate([
            'body' => ['nullable', 'required_without:image', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:8192', new ContainsPet],
            'type' => ['required', Rule::in(array_keys(Catalog::POST_TYPES))],
            'mood' => ['nullable', Rule::in(array_keys(Catalog::MOODS))],
            'location' => ['nullable', 'string', 'max:80'],
            'diary_date' => ['nullable', 'date', 'before_or_equal:today', 'after:'.now()->subYears(30)->toDateString()],
        ], ['body.required_without' => 'Escreva algo ou adicione uma foto.']);

        if ($request->hasFile('image') && ! $detector->check($request->file('image'))['ok']) {
            return back()->withInput()->with('error', '🐾 Nossa IA não encontrou nenhum pet nessa foto. Pode ter gente junto, mas o pet precisa aparecer!');
        }

        $data['image'] = $this->storeImage($request->file('image'), 'posts');
        $data['diary_date'] = $data['diary_date'] ?? now()->toDateString();
        $data['user_id'] = $request->user()->id;

        $post = $pet->posts()->create($data);

        return redirect()->route('pets.show', [$pet, 'mes' => $post->diary_date->format('Y-m')])
            ->with('success', 'Registrado no diário de '.$pet->name.'! 📅');
    }

    public function show(Request $request, Post $post)
    {
        abort_if($post->hidden_at && ! $request->user()->isAdmin(), 404);
        $post->load(['pet.owner', 'comments' => fn ($q) => $q->with('pet', 'user')->oldest()]);
        $post->pawed = $post->paws()->where('user_id', $request->user()->id)->exists();
        $pawers = $post->paws()->with('user.activePet')->latest()->limit(12)->get();

        return view('posts.show', compact('post', 'pawers'));
    }

    public function destroy(Request $request, Post $post)
    {
        abort_unless($post->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        if ($post->image) {
            \Storage::disk('public')->delete($post->image);
        }
        $post->delete();

        return redirect()->route('pets.show', $post->pet)->with('success', 'Post removido.');
    }

    /** Dar/retirar patinha (substitui o "curtir"). */
    public function paw(Request $request, Post $post)
    {
        $user = $request->user();

        $pawed = DB::transaction(function () use ($post, $user) {
            $existing = Paw::where('post_id', $post->id)->where('user_id', $user->id)->first();
            if ($existing) {
                $existing->delete();
                $post->decrement('paws_count');

                return false;
            }
            Paw::create(['post_id' => $post->id, 'user_id' => $user->id]);
            $post->increment('paws_count');

            return true;
        });

        if ($pawed && $post->user_id !== $user->id) {
            $from = $user->currentPet()?->name ?? $user->name;
            $post->user->notify(new PetDayNotification('🐾', "{$from} deixou uma patinha no post de {$post->pet->name}", route('posts.show', $post)));
        }

        if ($request->wantsJson()) {
            return response()->json(['pawed' => $pawed, 'count' => $post->fresh()->paws_count]);
        }

        return back();
    }

    public function report(Request $request, Post $post)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        $post->reports()->firstOrCreate(
            ['user_id' => $request->user()->id, 'status' => 'open'],
            ['reason' => $data['reason']]
        );

        return back()->with('success', 'Obrigado! Nossa equipe vai analisar a denúncia.');
    }
}
