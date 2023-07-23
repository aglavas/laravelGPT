<?php

namespace App\Actions;

use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;

class AssessWebAccessRequirement
{
    use AsAction;

    public function handle(string $prompt): bool
    {
        $result = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'temperature' => 0,
            'max_tokens' => 1,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Your task is to answer whether or not the provided user prompt requires web access to answer. Reply directly with one word only, with no additional text: yes|no'
                ],
                [
                    'role' => 'user',
                    'content' => "Does this user prompt require or benefit from the use of web access to answer?\n\n$prompt"
                ],
            ]
        ]);

        return strtolower($result->choices[0]->message->content) == 'yes';
    }
}
