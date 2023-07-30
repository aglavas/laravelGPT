<?php

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 *
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => $this->faker->randomElement(['user', 'assistant']),
            'content' => $this->faker->sentence(2, true),
            'conversation_id' => Conversation::factory()
        ];
    }

    /**
     * Create pending message
     *
     * @return self
     */
    public function pending(): self
    {
        return  $this->state([
            'role' => 'assistant',
            'content' => '',
        ]);
    }
}
