<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with('pet', 'user')->withCount('reports')
            ->when($request->query('q'), fn ($query, $q) => $query->where('body', 'like', "%{$q}%"))
            ->when($request->query('filtro') === 'ocultos', fn ($query) => $query->whereNotNull('hidden_at'))
            ->when($request->query('filtro') === 'denunciados', fn ($query) => $query->has('reports'))
            ->latest()->simplePaginate(20)->withQueryString();

        return view('admin.posts.index', compact('posts'));
    }

    public function toggleHidden(Post $post)
    {
        $post->update(['hidden_at' => $post->hidden_at ? null : now()]);

        return back()->with('success', $post->hidden_at ? 'Post ocultado.' : 'Post visível novamente.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return back()->with('success', 'Post excluído.');
    }
}
