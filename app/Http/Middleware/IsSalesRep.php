<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsSalesRep
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
        if (Auth::check() && Auth::user()->user_type == 'sales_rep' && (isset(Auth::user()->banned) ? !Auth::user()->banned : true)) {
            return $next($request);
        }
        else{
            abort(404);
        }
    }
}