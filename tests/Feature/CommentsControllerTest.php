<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Database\Factories\UserFactory;
use Faker\Factory;
use Tests\TestCase;

class CommentsControllerTest extends TestCase
{
    public function test_open_comments_index_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->withSession(['banned' => false])
                         ->get('/comments');

        $response->assertStatus(200);
        $response->assertViewIs('comments.index');
        $response->assertSee('Create Comment', $escaped = true);
    }

    public function test_fails_to_open_comments_index_not_autenticated(){
        $response = $this->followingRedirects()
                         ->withSession(['banned' => false])
                         ->get('/comments');

        $response->assertStatus(200);
        $response->assertSee('Login', $escaped = true);
    }

    public function test_create_comment_authenticated_user(){
        $user = User::factory()->create();
        $faker = Factory::create();
        $title = $faker->realText(65);
        $text = $faker->realText(500);

        $response = $this->followingRedirects()
                         ->actingAs($user)
                         ->withSession(['banned' => false])
                         ->post('/comments/create', ['title' => $title, 'text' => $text]);

        $response->assertStatus(200);
        $response->assertSee($title, $escaped = true);
        $response->assertSee($text, $escaped = true);
    }

    public function test_delete_my_comment(){
        $faker = Factory::create();
        $title = $faker->realText(65);
        $text = $faker->realText(500);

        $user = User::factory()->create();
        $comment = [
            'id'    => $faker->uuid,
            'title' => $title,
            'text'  => $text,
            'user_id' => $user->id,
            'likes' => random_int(1, 1000)
        ];
        Comment::SaveRecord($comment);

        $response = $this->followingRedirects()
                         ->actingAs($user)
                         ->withSession(['banned' => false])
                         ->delete('/comments/' . $comment['id']);

        $response->assertStatus(200);
        $response->assertDontSee($title, $escaped = true);
        $response->assertDontSee($text, $escaped = true);

    }

    public function test_delete_other_users_comment(){
        $faker = Factory::create();
        $title = $faker->realText(65);
        $text = $faker->realText(500);

        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $comment = [
            'id'    => $faker->uuid,
            'title' => $title,
            'text'  => $text,
            'user_id' => $user2->id,
            'likes' => random_int(1, 1000)
        ];
        Comment::SaveRecord($comment);

        $response = $this->followingRedirects()
                         ->actingAs($user)
                         ->withSession(['banned' => false])
                         ->delete('/comments/' . $comment['id']);

        $response->assertStatus(200);
        $response->assertSee($title, $escaped = true);
        $response->assertSee($text, $escaped = true);
    }
}
