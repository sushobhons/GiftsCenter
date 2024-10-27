<?php

namespace App\Http\Middleware;

use Closure;

class AddExpiresHeader
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Add Expires header for caching
        $response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600)); // Set expiration time (1 hour in this example)

        return $response;
    }
}