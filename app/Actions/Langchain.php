<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Lorisleiva\Actions\Concerns\AsAction;

class Langchain
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'langchain';

    /**
     * @var Command
     */
    private Command $command;

    /**
     * @return void
     */
    public function handle(): void
    {
        Process::env([
            'OPENAI_API_KEY' => config('openai.api_key')
        ])
            ->path(base_path())
            ->run('bash python.sh main.py', function (string $type, string $output) {
                $this->command->comment($output);
            });
    }

    /**
     * @param Command $command
     * @return void
     */
    public function asCommand(Command $command): void
    {
        $this->command = $command;
        $this->handle();
    }
}
