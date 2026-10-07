<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        RateLimiter::for('bookings', fn (Request $request) => [
            Limit::perMinute(5)->by('minute:'.$request->ip()),
            Limit::perHour(20)->by('hour:'.$request->ip()),
        ]);
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower(is_string($request->input('email')) ? trim($request->input('email')) : '').'|'.$request->ip()),
            Limit::perMinute(30)->by($request->ip()),
        ]);
    }
}
