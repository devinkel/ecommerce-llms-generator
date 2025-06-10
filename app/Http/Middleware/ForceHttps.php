<?php

namespace App\Http\Middleware;

use Closure;

class ForceHttps
{
    public function handle($request, Closure $next)
    {
        if (
            (!$request->isSecure()) &&
            (!app()->environment('local')) // opcional: evita redirecionar em dev
        ) {
            $redirectUrl = 'https://' . $request->getHttpHost() . $request->getRequestUri();
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
