<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // validating the json request
        $accept         =   $request->header('Accept');
        $contentType    =   $request->header('Content-Type');

        if($accept != 'application/json' || $contentType != 'application/json'){
            return response()->json(['message' => 'Only JSON requests are allowed'], 406);
        }
        return $next($request);
    }
}
