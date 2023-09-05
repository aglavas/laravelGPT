<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;
use \Probots\Pinecone\Client as Pinecone;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Actions::registerCommands();

        $pineConeKey = config('services.pinecone.key', null);
        $pineConeEnv = config('services.pinecone.environment', null);

        if ($pineConeKey && $pineConeEnv) {
            $this->app->bind(Pinecone::class, fn () => new Pinecone(
                config('services.pinecone.key'),
                config('services.pinecone.environment')
            ));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
