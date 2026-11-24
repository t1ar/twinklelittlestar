<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function create(Community $community)
    {
        return view('posts.create', compact('community'));
    }

    public function store(Request $request, Community $community)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'body'  => ['nullable', 'string', 'max:10000'],
            'url'   => ['nullable', 'url', 'max:2048'],
        ]);

        if (empty($data['body']) && empty($data['url'])) {
            return back()->withErrors(['body' => 'Add some text or a link.'])->withInput();
        }

        $post = $community->posts()->create([
            'user_id' => $request->user()->id,
            'title'   => $data['title'],
            'body'    => $data['body'] ?? null,
            'url'     => $data['url'] ?? null,
        ]);

        return redirect()->route('posts.show', $post);
    }

    public function show(Post $post)
    {
        $post->load(['user', 'community']);

        return view('posts.show', compact('post'));
    }

    public function destroy(Request $request, Post $post)
    {
        $user = $request->user();

        $isMod = $post->community->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'mod')
            ->exists();

        abort_unless($user->id === $post->user_id || $isMod, 403);

        $community = $post->community;
        $post->delete();

        return redirect()->route('communities.show', $community);
    }
}