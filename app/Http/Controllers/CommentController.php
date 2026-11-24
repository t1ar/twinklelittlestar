<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $data = $request->validate([
            'body'      => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        // a reply must belong to a comment on the same post
        if (!empty($data['parent_id'])) {
            $parent = Comment::findOrFail($data['parent_id']);
            abort_unless($parent->post_id === $post->id, 422);
        }

        $post->comments()->create([
            'user_id'   => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body'      => $data['body'],
        ]);

        return back();
    }

    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();

        $isMod = $comment->post->community->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'mod')
            ->exists();

        abort_unless($user->id === $comment->user_id || $isMod, 403);

        $comment->delete(); // replies are deleted too (cascade in the migration)

        return back();
    }
    
}
