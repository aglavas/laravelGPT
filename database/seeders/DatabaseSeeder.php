<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Client\Pool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        Message::factory()
            ->count(6)
            ->for(Conversation::factory())
            ->state(new Sequence(
                ['role' => 'user'],
                ['role' => 'assistant'],
            ))->create();

         \App\Models\User::factory()->create([
             'name' => 'Test2 User',
             'email' => 'test2@example.com',
         ]);

         $urls = [
            'https://entreprenik.com/blog/non-sentient-civilizations',
            'https://entreprenik.com/blog/deadlines-and-timeframes-revisited',
            'https://entreprenik.com/blog/poor-definitions-productivity',
            'https://entreprenik.com/blog/scaling-a-saas-to-usd3m-year-on-the-back-of-a-monolith',
            'https://entreprenik.com/blog/any-sufficiently-advanced-technology'
         ];

         $embedUrl = 'http://localhost:8000/embed';

         Http::pool(fn (Pool $pool) => collect($urls)
             ->map(fn (string $url) => $pool->get($embedUrl, ['url' => $url])));
    }
}
