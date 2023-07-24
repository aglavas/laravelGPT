<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;

class Transcribe
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'transcribe';

    /**
     * @var Command
     */
    private Command $command;

    /**
     * @return void
     */
    public function handle(): void
    {
        /** @var FilesystemAdapter $sourcePath */
        $sourcePath = Storage::disk('audio_source');
        $transcriptPath = Storage::disk('audio_transcripts');
        $baseSourcePath = $sourcePath->getConfig()['root'];
        $fullTranscriptPath = $transcriptPath->getConfig()['root'];
        $inputPath = $baseSourcePath . '/monologue.ogg';
        $whisperCommand = "whisper --output_format=json --model=small.en --output_dir=$fullTranscriptPath $inputPath";
        Process::timeout(300)
            ->path(base_path())
            ->run($whisperCommand, function (string $type, string $output) {
                $this->command->comment($output);
            });
    }

    public function asCommand(Command $command)
    {
        $this->command = $command;
        $this->handle();
    }
}
