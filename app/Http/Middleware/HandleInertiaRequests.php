<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @param Request $request
     * @return string
     */
    public function rootView(Request $request): string
    {
        if (Str::startsWith($request->path(), 'widget')) {
            return 'widget';
        }

        return 'app';
    }

    /**
     * Is widget
     *
     * @param Request $request
     * @return bool
     */
    public function isWidget(Request $request): bool
    {
        return Str::startsWith($request->path(), 'widget');
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $isWidget = $this->isWidget($request);

        if ($isWidget) {
            config()->set('ziggy.only', ['widget.*']);
        }

        return array_merge(parent::share($request), [
            'auth' => $isWidget ? null : [
                'user' => $request->user(),
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
        ]);

//        return array_merge(parent::share($request), [
//            'auth' => [
//                'user' => $request->user(),
//            ],
//            'ziggy' => function () use ($request) {
//                return array_merge((new Ziggy)->toArray(), [
//                    'location' => $request->url(),
//                ]);
//            },
//        ]);
    }
}
