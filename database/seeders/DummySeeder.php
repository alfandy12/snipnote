<?php

namespace Database\Seeders;

use App\Models\Note;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory(50, ['password' => 12345678 ])->create();

        \App\Models\Note::factory(100)
            ->create()
            ->each(function ($note) {
                $users = \App\Models\User::inRandomOrder()->take(3)->get();
                foreach ($users as $index => $user) {
                    DB::table('note_accesses')->insert([
                        'note_id' => $note->id,
                        'user_id' => $user->id,
                        'is_owner' => $index === 0,
                    ]);
                }

                // 5 komentar random
                $commenters = \App\Models\User::inRandomOrder()->take(5)->get();
                foreach ($commenters as $user) {
                    DB::table('note_comments')->insert([
                        'note_id' => $note->id,
                        'user_id' => $user->id,
                        'comment' => fake()->sentence,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }
}
