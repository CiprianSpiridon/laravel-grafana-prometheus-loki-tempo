<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use Illuminate\Support\Facades\DB;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\NoopTracer;
use OpenTelemetry\SDK\Common\Time\ClockFactory;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('otlp.tracer', function ($app) {
            try {
                // Create resource info with direct string attributes instead of constants
                $resource = ResourceInfo::create(Attributes::create([
                    'service.name' => config('opentelemetry.service.name'),
                    'service.version' => config('opentelemetry.service.version'),
                    'deployment.environment' => config('opentelemetry.service.environment'),
                ]));

                // Create exporter for Tempo
                $tempoEndpoint = config('opentelemetry.tempo_endpoint');
                Log::info('Connecting to OpenTelemetry endpoint: ' . $tempoEndpoint);

                $transportFactory = new OtlpHttpTransportFactory();
                $transport = $transportFactory->create($tempoEndpoint, 'application/json');
                $exporter = new SpanExporter($transport);

                // Create span processor - using BatchSpanProcessor for better performance
                $clock = ClockFactory::getDefault();
                $spanProcessor = new BatchSpanProcessor($exporter, $clock);

                // Create tracer provider with sampler
                $tracerProvider = new TracerProvider(
                    [$spanProcessor],
                    new AlwaysOnSampler(),
                    $resource
                );

                // Return tracer
                return $tracerProvider->getTracer('laravel-app');
            } catch (\Exception $e) {
                Log::error('Failed to initialize OpenTelemetry tracer: ' . $e->getMessage());
                Log::error($e->getTraceAsString());

                // Return a no-op tracer instead of failing
                return new NoopTracer();
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Check if tracing is enabled
        if (!config('opentelemetry.tracing.enabled', true)) {
            return;
        }

        // Register middleware in the web group
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', \App\Http\Middleware\TraceMiddleware::class);
        $router->pushMiddlewareToGroup('api', \App\Http\Middleware\TraceMiddleware::class);

        // Add database query listener for tracing
        if (config('app.env') !== 'testing' && config('opentelemetry.database_tracing.enabled', true)) {
            $this->setupDatabaseTracing();
        }
    }

    /**
     * Set up database query tracing
     */
    protected function setupDatabaseTracing(): void
    {
        try {
            DB::listen(function ($query) {
                $tracer = app('otlp.tracer');

                // Create a span for this query
                $span = $tracer->spanBuilder('db.query')
                    ->setSpanKind(SpanKind::KIND_CLIENT)
                    ->startSpan();

                // Set attributes
                $span->setAttribute('db.system', DB::connection()->getDriverName());
                $span->setAttribute('db.statement', $query->sql);
                $span->setAttribute('db.query_time_ms', $query->time);

                if (!empty($query->bindings) && config('opentelemetry.database_tracing.sanitize_bindings', true)) {
                    // Sanitize and limit bindings to avoid very large spans
                    $sanitizedBindings = $this->sanitizeBindings($query->bindings);
                    $span->setAttribute('db.parameters', json_encode($sanitizedBindings));
                }

                // End span
                $span->end();
            });
        } catch (\Exception $e) {
            Log::error('Failed to set up database tracing: ' . $e->getMessage());
        }
    }

    /**
     * Sanitize query bindings to remove sensitive data and limit size
     */
    protected function sanitizeBindings(array $bindings): array
    {
        $maxLength = config('opentelemetry.database_tracing.max_string_length', 100);

        $sanitized = [];
        foreach ($bindings as $key => $value) {
            if (is_string($value) && strlen($value) > $maxLength) {
                $value = substr($value, 0, $maxLength - 3) . '...';
            }

            // Mask potential sensitive data
            if (is_string($key) && preg_match('/password|token|secret|key/i', $key)) {
                $value = '********';
            }

            $sanitized[$key] = $value;
        }
        return $sanitized;
    }
}
