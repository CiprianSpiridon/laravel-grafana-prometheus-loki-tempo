<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('otlp.tracer', function ($app) {
            // Create resource info with direct string attributes instead of constants
            $resource = ResourceInfo::create(Attributes::create([
                'service.name' => config('app.name'),
                'service.version' => config('app.version', '1.0.0'),
                'deployment.environment' => config('app.env'),
            ]));

            // Create exporter for Tempo
            $tempoEndpoint = env('TEMPO_ENDPOINT', 'http://tempo:4318/v1/traces');
            $transportFactory = new OtlpHttpTransportFactory();
            $transport = $transportFactory->create($tempoEndpoint, 'application/json');
            $exporter = new SpanExporter($transport);

            // Create span processor
            $spanProcessor = new SimpleSpanProcessor($exporter);

            // Create tracer provider
            $tracerProvider = new TracerProvider(
                [$spanProcessor],
                new AlwaysOnSampler(),
                $resource
            );

            // Return tracer
            return $tracerProvider->getTracer('laravel-app');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware in the web group
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', \App\Http\Middleware\TraceMiddleware::class);
        $router->pushMiddlewareToGroup('api', \App\Http\Middleware\TraceMiddleware::class);
    }
}
