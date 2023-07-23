<?php

namespace App\Actions;

use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;

class CondenseText
{
    use AsAction;

    /**
     * @param string $text
     * @return string
     */
    public function handle(string $text): string
    {
        $result = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are really good at condensing text to the most important and insightful bits, removing unnecessary sections. Your task is to condense the given text, without data loss. The result should not be a description of the original, but a version which is just as useful and actionable. List items should be separated by new lines. Do not add any additional explanations or surrounding text. Just reply with the summary directly.'
                ],
                [
                    'role' => 'user',
                    'content' => 'Condense the following text, without any data loss: '.$text
                ],
            ]
        ]);

        return $result->choices[0]->message->content;
    }
}
