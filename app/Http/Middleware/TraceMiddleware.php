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

            // Log the span name for debugging
            Log::debug("Creating span for: $spanName", [
                'traceparent' => $traceparent,
                'path' => $request->path(),
                'method' => $request->method()
            ]);

            // Start a span for this request
            $span = $tracer->spanBuilder($spanName)
                ->setSpanKind(1) // SERVER - SpanKind::KIND_SERVER 
                ->startSpan();

            $span->setAttribute('http.method', $request->method());
            $span->setAttribute('http.url', $request->fullUrl());
            $span->setAttribute('http.host', $request->getHost());
            $span->setAttribute('http.scheme', $request->getScheme());
            $span->setAttribute('http.client_ip', $request->ip());

            // Set route information when available
            if ($request->route()) {
                $span->setAttribute('http.route', $request->route()->uri() ?: 'unknown');

                if ($routeName = $request->route()->getName()) {
                    $span->setAttribute('http.route_name', $routeName);
                }
            }

            $span->setAttribute('http.user_agent', $request->userAgent() ?: 'unknown');

            // Add trace ID to request for logging
            $request->attributes->set('trace_id', $traceId);

            // If we can get the actual context trace ID from the span
            try {
                $contextTraceId = $span->getContext()->getTraceId();
                if ($contextTraceId) {
                    $request->attributes->set('otel_trace_id', $contextTraceId);
                    Log::debug("OpenTelemetry trace ID: $contextTraceId");
                }
            } catch (\Exception $e) {
                // Ignore if we can't access the context
            }

            // Continue with the request
            $response = $next($request);

            // Add response information to span
            if ($response instanceof Response) {
                $statusCode = $response->getStatusCode();
                $span->setAttribute('http.status_code', $statusCode);

                if ($statusCode >= 400) {
                    $span->setStatus(2); // 2 = ERROR, StatusCode::STATUS_ERROR
                } else {
                    $span->setStatus(1); // 1 = OK, StatusCode::STATUS_OK
                }

                // Record response size if available
                $contentLength = $response->headers->get('Content-Length');
                if ($contentLength) {
                    $span->setAttribute('http.response_content_length', (int) $contentLength);
                }

                // Add trace ID to response headers for client-side tracing
                if ($contextTraceId = $request->attributes->get('otel_trace_id')) {
                    $response->headers->set('X-Trace-ID', $contextTraceId);
                }
            }

            // End the span
            $span->end();

            Log::debug("Completed trace for: $spanName", [
                'trace_id' => $request->attributes->get('otel_trace_id', $traceId),
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response instanceof Response ? $response->getStatusCode() : 'unknown'
            ]);
        } catch (\Exception $e) {
            // Fallback in case of OpenTelemetry errors
            Log::error('OpenTelemetry error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $response = $next($request);
        }

        return $response;
    }
}
