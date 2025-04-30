<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\Span;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('OTEL_TRACE_QUERY')) {
            DB::listen(function ($query) {
                $span = Span::getCurrent();
                $sql = $query->sql;
                $bindings = $query->bindings;
                $time = $query->time;

                $span->addEvent('DB Query', [
                    'sql' => vsprintf(str_replace('?', '%s', $sql), $bindings),
                    'time_ms' => $time,
                ]);
            });
        }
    }
}
