<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Gate the flow pages: no Firebase session => back to the welcome / login page. */
class EnsureFirebaseSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('firebase_user')) {
            return redirect()->route('welcome')->with('auth_error', 'Inicia sesión para continuar.');
        }

        return $next($request);
    }
}
