<?php

namespace App\Console\Commands;

use App\Actions\EmbeddWeb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class DebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug {argument}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(EmbeddWeb $embeddWeb)
    {
        $argumentValue = $this->argument('argument');
        $this->info($argumentValue);
        $embeddWeb->handle($this);
        return self::SUCCESS;
    }
}
