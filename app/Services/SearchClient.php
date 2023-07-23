<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SearchClient
{
    public function search(string $query)
    {
        $key = config('services.google.search_key');
        $cx = config('services.google.search_cx');

        return Http::acceptJson()
            ->get("https://www.googleapis.com/customsearch/v1?key=$key&cx=$cx&q=$query")
            ->json();
    }
}
