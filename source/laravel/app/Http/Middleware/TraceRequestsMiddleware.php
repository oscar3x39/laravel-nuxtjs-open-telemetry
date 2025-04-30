<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TraceRequestsMiddleware
{
    /**
     * Handle an incoming request and trace it.
     *
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tracer = Globals::tracerProvider()->getTracer('laravel-app');

        $span = $tracer->spanBuilder(sprintf('HTTP %s %s', $request->method(), $request->getPathInfo()))
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();

        $span->setAttributes([
            'http.method'     => $request->method(),
            'http.route'      => $request->route() ? $request->route()->uri() : $request->getPathInfo(),
            'http.target'     => $request->getRequestUri(),
            'http.user_agent' => $request->userAgent(),
            'http.host'       => $request->getHost(),
            'http.scheme'     => $request->getScheme(),
        ]);

        $scope = $span->activate();

        try {
            /** @var Response $response */
            $response = $next($request);

            $span->setAttribute('http.status_code', $response->getStatusCode());

            return $response;
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
