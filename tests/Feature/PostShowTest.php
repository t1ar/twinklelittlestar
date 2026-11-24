<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostShowTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_community_post_links_to_page_with_threaded_comments(): void
    {
        $user = User::factory()->create();
        $community = Community::create([
            'name' => 'Test community',
            'slug' => 'test-community',
            'creator_id' => $user->id,
        ]);
        $post = Post::create([
            'user_id' => $user->id,
            'community_id' => $community->id,
            'title' => 'Test post',
        ]);
        $parent = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'body' => 'Top-level comment',
        ]);
        Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => $parent->id,
            'body' => 'Nested reply',
        ]);

        $this->actingAs($user)
            ->get(route('communities.show', $community))
            ->assertOk()
            ->assertSee(route('posts.show', $post), false)
            ->assertSee('Test post');

        $this->actingAs($user)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('2 comments')
            ->assertSee('Top-level comment')
            ->assertSee('Nested reply');
    }
}
