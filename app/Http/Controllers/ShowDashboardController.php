<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SourceUrl;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShowDashboardController extends Controller
{
    /**
     * @param Request $request
     * @return \Inertia\Response
     */
    public function __invoke(Request $request): \Inertia\Response
    {
        return Inertia::render('Dashboard', [
            'urls' => SourceUrl::all()->map(fn (SourceUrl $url) => $url->url),
            'conversations' => Conversation::query()->latest()->get()->toArray()
        ]);
    }
}
