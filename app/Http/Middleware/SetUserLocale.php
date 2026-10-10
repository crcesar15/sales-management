<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        App::setLocale($locale instanceof UserLocale ? $locale->value : config('app.locale'));

        return $next($request);
    }
}
