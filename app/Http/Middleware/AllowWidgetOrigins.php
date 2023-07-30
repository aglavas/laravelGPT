<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowWidgetOrigins
{
    /**
     * Handle CORS for widget routes
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->environment('local')) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Content-Security-Policy', "frame-ancestors *");
        } else {
            // For production environment
            $response->headers->set('Access-Control-Allow-Origin', 'https://my-site.com');
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' https://my-site.com");
        }

        return $response;
    }
}
