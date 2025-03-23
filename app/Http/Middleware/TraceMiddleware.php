<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TraceMiddleware
{
    /**
     * Handle an incoming request with distributed tracing.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $tracer = app('otlp.tracer');

        // Extract trace context from headers if present
        $traceparent = $request->header('traceparent');
        $tracestate = $request->header('tracestate');

        // Generate a trace ID if not present in headers
        $traceId = $traceparent ? explode('-', $traceparent)[1] : Str::uuid()->toString();

        try {
            // Determine span name, ensuring it's never null
            $spanName = 'http_request';

            if ($request->route()) {
                $routeName = $request->route()->getName();
                $routeUri = $request->route()->uri();
                $spanName = $routeName ?: $routeUri ?: $request->path() ?: 'http_request';
            } else {
                $spanName = $request->path() ?: 'http_request';
            }

            // Start a span for this request
            $span = $tracer->spanBuilder($spanName)
                ->setSpanKind(1) // Using integer constants: 1 = SERVER
                ->startSpan();

            $span->setAttribute('http.method', $request->method());
            $span->setAttribute('http.url', $request->fullUrl());

            // Set route information when available
            if ($request->route()) {
                $span->setAttribute('http.route', $request->route()->uri() ?: 'unknown');
            }

            $span->setAttribute('http.user_agent', $request->userAgent() ?: 'unknown');

            // Add trace ID to request for logging
            $request->attributes->set('trace_id', $traceId);

            // Continue with the request
            $response = $next($request);

            // Add response information to span
            if ($response instanceof Response) {
                $span->setAttribute('http.status_code', $response->getStatusCode());

                if ($response->getStatusCode() >= 400) {
                    $span->setStatus(2); // Using integer constants: 2 = ERROR
                }
            }

            // End the span
            $span->end();
        } catch (\Exception $e) {
            // Fallback in case of OpenTelemetry errors
            Log::error('OpenTelemetry error: ' . $e->getMessage());
            $response = $next($request);
        }

        return $response;
    }
}
