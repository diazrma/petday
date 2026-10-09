<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Story;
use App\Models\StoryComment;
use App\Models\StoryReaction;
use App\Models\StoryView;
use App\Notifications\PetDayNotification;
use App\Services\PetDetector;
use App\Rules\ContainsPet;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoryController extends Controller
{
    public function store(Request $request, PetDetector $detector)
    {
        $pet = $request->user()->currentPet();
        abort_unless($pet, 422, 'Cadastre um pet primeiro.');

        $data = $request->validate([
            'image' => ['nullable', 'required_without:caption', 'image', 'max:8192', new ContainsPet],
            'caption' => ['nullable', 'string', 'max:200'],
            'background' => ['nullable', Rule::in(Catalog::STORY_BACKGROUNDS)],
            'sticker' => ['nullable', 'string', 'max:8'],
            'mood' => ['nullable', Rule::in(array_keys(Catalog::MOODS))],
            'caption_x' => ['nullable', 'integer', 'between:0,100'],
            'caption_y' => ['nullable', 'integer', 'between:0,100'],
        ], ['image.required_without' => 'Adicione uma foto ou um texto ao rastro.']);

        if ($request->hasFile('image') && ! $detector->check($request->file('image'))['ok']) {
            return back()->with('error', '🐾 Nossa IA não encontrou nenhum pet nessa foto. Pode ter gente junto, mas o pet precisa aparecer!');
        }

        $pet->stories()->create([
            'user_id' => $request->user()->id,
            'image' => $this->storeImage($request->file('image'), 'stories'),
            'caption' => $data['caption'] ?? null,
            'background' => $data['background'] ?? Catalog::STORY_BACKGROUNDS[0],
            'sticker' => $data['sticker'] ?? null,
            'mood' => $data['mood'] ?? null,
            'caption_x' => $data['caption_x'] ?? null,
            'caption_y' => $data['caption_y'] ?? null,
            'expires_at' => now()->addDay(),
        ]);

        return redirect()->route('feed')->with('success', 'Rastro deixado! 🐾 Ele vai desbotando até sumir em 24h.');
    }

    public function show(Request $request, Pet $pet)
    {
        $userId = $request->user()->id;
        $stories = $pet->stories()->active()->withCount('views')
            ->with(['comments' => fn ($q) => $q->with('user', 'pet')->oldest(), 'reactions.user'])
            ->oldest()->get();
        abort_if($stories->isEmpty(), 404);

        foreach ($stories as $story) {
            StoryView::firstOrCreate(['story_id' => $story->id, 'user_id' => $request->user()->id]);
        }

        // Próximo pet com stories ativos entre os que o usuário acompanha
        $petIds = $request->user()->pets()->pluck('id')->merge($request->user()->following()->pluck('pets.id'));
        $next = Pet::whereIn('id', $petIds)->where('id', '>', $pet->id)
            ->whereHas('stories', fn ($q) => $q->active())->orderBy('id')->first();

        // Dados que o visualizador (Alpine) manipula sem recarregar a página
        $payload = $stories->map(fn ($story) => [
            'id' => $story->id,
            'mine' => $story->reactions->firstWhere('user_id', $userId)?->kind,
            'counts' => $this->reactionCounts($story),
            'comments' => $story->comments->map(fn ($c) => $this->commentData($c, $request))->values(),
        ])->values();

        return view('stories.show', compact('pet', 'stories', 'next', 'payload'));
    }

    public function react(Request $request, Story $story)
    {
        abort_unless($story->expires_at->isFuture(), 410);
        $data = $request->validate(['kind' => ['required', Rule::in(array_keys(Catalog::STORY_REACTIONS))]]);
        $user = $request->user();

        $existing = StoryReaction::where('story_id', $story->id)->where('user_id', $user->id)->first();
        if ($existing?->kind === $data['kind']) {
            $existing->delete(); // tocar de novo desfaz
            $mine = null;
        } else {
            StoryReaction::updateOrCreate(['story_id' => $story->id, 'user_id' => $user->id], ['kind' => $data['kind']]);
            $mine = $data['kind'];

            if (! $existing && $story->user_id !== $user->id) {
                $r = Catalog::STORY_REACTIONS[$mine];
                $from = $user->currentPet()?->name ?? $user->name;
                $story->user->notify(new PetDayNotification($r['emoji'], "{$from} {$r['verb']} no rastro de {$story->pet->name}", route('stories.show', $story->pet)));
            }
        }

        return response()->json(['mine' => $mine, 'counts' => $this->reactionCounts($story)]);
    }

    public function comment(Request $request, Story $story)
    {
        abort_unless($story->expires_at->isFuture(), 410);
        $data = $request->validate(['body' => ['required', 'string', 'max:300']]);
        $user = $request->user();

        $comment = $story->comments()->create([
            'user_id' => $user->id,
            'pet_id' => $user->currentPet()?->id,
            'body' => $data['body'],
        ])->load('user', 'pet');

        if ($story->user_id !== $user->id) {
            $story->user->notify(new PetDayNotification('💬', "{$comment->authorName()} deixou um recado no rastro de {$story->pet->name}", route('stories.show', $story->pet)));
        }

        return response()->json($this->commentData($comment, $request), 201);
    }

    public function destroyComment(Request $request, StoryComment $comment)
    {
        $user = $request->user();
        abort_unless($comment->user_id === $user->id || $comment->story()->value('user_id') === $user->id || $user->isAdmin(), 403);
        $comment->delete();

        return response()->noContent();
    }

    private function reactionCounts(Story $story): array
    {
        $counts = $story->reactions()->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind');

        return collect(Catalog::STORY_REACTIONS)->map(fn ($r, $k) => (int) ($counts[$k] ?? 0))->all();
    }

    private function commentData(StoryComment $c, Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $c->id,
            'author' => $c->authorName(),
            'avatar' => $c->pet?->avatarUrl() ?? $c->user->avatarUrl(),
            'emoji' => $c->pet?->emoji() ?? '🙂',
            'body' => $c->body,
            'ago' => $c->created_at->diffForHumans(null, true),
            'canDelete' => $c->user_id === $user->id || $c->story->user_id === $user->id || $user->isAdmin(),
            'deleteUrl' => route('stories.comments.destroy', $c),
        ];
    }

    public function destroy(Request $request, Story $story)
    {
        abort_unless($story->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        $story->delete();

        return redirect()->route('feed')->with('success', 'Story removido.');
    }
}
