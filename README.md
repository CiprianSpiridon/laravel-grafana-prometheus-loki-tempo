# Laravel Grafana Prometheus Loki Tempo

A complete observability stack for Laravel applications using Grafana, Prometheus, Loki, and Tempo with OpenTelemetry integration.

## Stack Components

- **Laravel with FrankenPHP**: High-performance PHP application server
- **Prometheus**: Metrics collection and storage
- **Grafana**: Visualization and dashboarding platform
- **Loki**: Log aggregation system
- **Tempo**: Distributed tracing backend with OpenTelemetry support
- **MySQL**: Database server with MySQL exporter for metrics
- **Redis**: In-memory data store with Redis exporter for metrics
- **DynamoDB Local**: Local DynamoDB implementation

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Git

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/CiprianSpiridon/laravel-grafana-prometheus-loki-tempo.git
   cd laravel-grafana-prometheus-loki-tempo
   ```

2. Start the containers:
   ```bash
   docker compose up -d
   ```

3. Install Laravel dependencies:
   ```bash
   docker exec laravel-grafana-prometheus-loki-tempo-dev composer install
   ```

4. Generate application key:
   ```bash
   docker exec laravel-grafana-prometheus-loki-tempo-dev php artisan key:generate
   ```

5. Run migrations:
   ```bash
   docker exec laravel-grafana-prometheus-loki-tempo-dev php artisan migrate
   ```

## Accessing Services

- **Laravel Application**: http://localhost:3000
- **Grafana**: http://localhost:3001 (Default credentials: admin/admin)
- **Prometheus**: http://localhost:9090
- **PhpMyAdmin**: http://localhost:8080
- **Redis Commander**: http://localhost:8081
- **DynamoDB Admin**: http://localhost:8001

## Pre-configured Dashboards

Grafana comes with several pre-configured dashboards:

1. **Laravel Application Overview**: Overview of Laravel application metrics
2. **Laravel Logs Dashboard**: Visualization of Laravel logs from Loki
3. **Laravel Traces Dashboard**: Visualization of distributed traces from Tempo
4. **Laravel Horizon**: Monitoring for Laravel Horizon queues and workers
5. **MySQL Overview**: MySQL server metrics and performance monitoring
6. **Redis Dashboard**: Redis server metrics and monitoring
7. **Prometheus Overview**: Monitoring for the Prometheus server itself

## Observability Features

### Metrics (Prometheus)
- Application metrics: memory usage, uptime, request counts, etc.
- MySQL metrics: query performance, connections, etc.
- Redis metrics: memory usage, commands, connections, etc.

### Logs (Loki)
- Centralized log collection from Laravel application
- Structured log parsing and querying
- Log correlation with metrics and traces

### Traces (Tempo with OpenTelemetry)
- Distributed request tracing using OpenTelemetry protocol
- Auto-instrumentation via PHP OpenTelemetry extension
- Performance bottleneck identification
- Integration with logs and metrics

## OpenTelemetry Integration

This stack includes full OpenTelemetry integration for PHP:

- **Auto-instrumentation**: PHP extension for automatic tracing
- **Manual instrumentation**: Service provider for custom span creation
- **Configuration**: Environment variables for controlling trace sampling and export
- **Trace Viewing**: Tempo backend with Grafana for visualization

Test the tracing with the included endpoint:
```bash
curl http://localhost:3000/api/test-traces
```

Then view traces in Grafana's Explore view by selecting the Tempo data source.

## License

[MIT License](LICENSE)
