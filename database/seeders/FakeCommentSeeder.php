<?php

namespace Database\Seeders;

use App\Comment;
use App\GrowthSession;
use Illuminate\Database\Seeder;

class FakeCommentSeeder extends Seeder
{
    public function run()
    {
        GrowthSession::all()->each(fn($socialMob) => Comment::factory()
            ->times(random_int(0, 3))
            ->create(['social_mob_id' => $socialMob->id])
        );
    }
}
