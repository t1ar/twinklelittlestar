<?php

namespace App\Http\Controllers;

use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function index()
    {
        $communities = Community::withCount(['members', 'posts'])->latest()->paginate(12);

        return view('communities.index', compact('communities'));
    }

    public function create()
    {
        return view('communities.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:50', 'unique:communities,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = Str::slug($data['name']);

        if (Community::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'That name is too similar to an existing community.'])->withInput();
        }

        $community = Community::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'creator_id' => $request->user()->id,
        ]);

        // the creator automatically becomes a moderator
        $community->members()->attach($request->user()->id, ['role' => 'mod']);

        return redirect()->route('communities.show', $community);
    }

    public function show(Community $community)
    {
        $posts = $community->posts()->with('user')->latest()->paginate(15);

        $isMember = auth()->check()
            && $community->members()->where('user_id', auth()->id())->exists();

        return view('communities.show', compact('community', 'posts', 'isMember'));
    }

    public function join(Community $community)
    {
        $community->members()->syncWithoutDetaching([auth()->id() => ['role' => 'member']]);

        return back();
    }

    public function leave(Community $community)
    {
        $community->members()->detach(auth()->id());

        return back();
    }
}