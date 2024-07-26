<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     *
     */   
public function handle($request, Closure $next)

{

    $response = $next($request);

    // Handle OPTIONS requests

    if ($request->isMethod('options')) {

        return response()->json([], 200)

            ->header('Access-Control-Allow-Origin', '*')

            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')

            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    }

    // Set CORS headers

    $response->headers->set('Access-Control-Allow-Origin, *'); // Replace '*' with your specific origin if needed

    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    return $response;

}
 
}
