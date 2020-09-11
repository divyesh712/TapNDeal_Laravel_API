<?php

namespace App\Http\Middleware;

use Closure;

class manufactureCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!session()->get('manufacture'))
        {
            return redirect('/login');
        }
        return $next($request);
    }
}
