<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();

        User::factory(10)->create();

        $i = 0;
        do {
            $comment = [
                'id'    => $faker->uuid,
                'title' => $faker->realText(65),
                'text'  => $faker->realText(500),
                'user_id' => User::all()->random()->id,
                'likes' => random_int(1, 1000)
            ];
            Comment::SaveRecord($comment);
            $i++;
        }while($i < 200);
    }
}
