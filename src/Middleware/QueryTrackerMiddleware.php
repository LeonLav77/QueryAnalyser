<?php

namespace LeonLav77\QueryAnalyser\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class QueryTrackerMiddleware
{
	public function handle($request, Closure $next)
	{
		DB::connection()->enableQueryLog();
		return $next($request);
	}

	public function terminate($request, $response)
	{
		DB::connection()->disableQueryLog();
	}
}
