# Laravel-NuxtJS OpenTelemetry Integration

This project demonstrates how to implement OpenTelemetry observability in a full-stack application using Laravel (backend) and NuxtJS (frontend), with monitoring through Grafana LGTM (Loki, Grafana, Tempo, Mimir).

## Architecture

- **Backend**: Laravel PHP application with OpenTelemetry PHP instrumentation
- **Frontend**: NuxtJS application
- **Observability**: Grafana LGTM stack for logs, metrics, and traces
- **Database**: MySQL 8.0
- **Cache**: Redis 6.0
- **Web Server**: Nginx

## Prerequisites

- Docker and Docker Compose
- Git

## Project Structure

```
.
├── default.conf             # Nginx site configuration
├── docker-compose.yml       # Docker services configuration
├── dockerfiles/             # Custom Dockerfiles
│   └── php-otel/            # PHP with OpenTelemetry extension
├── nginx.conf               # Nginx main configuration
└── source/                  # Application source code
    ├── laravel/             # Laravel backend application
    └── nuxtjs/              # NuxtJS frontend application
```

## Getting Started

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/oscar3x39/laravel-nuxtjs-open-telemetry.git
   cd laravel-nuxtjs-open-telemetry
   ```

2. Start the Docker containers:
   ```bash
   docker-compose up -d
   ```

3. Install Laravel dependencies:
   ```bash
   docker-compose exec workspace composer install
   ```

4. Set up Laravel environment:
   ```bash
   docker-compose exec workspace cp .env.example .env
   docker-compose exec workspace php artisan key:generate
   docker-compose exec workspace php artisan migrate
   ```

5. Install NuxtJS dependencies (if needed):
   ```bash
   cd source/nuxtjs
   npm install
   ```

### Accessing the Applications

- **Laravel Backend**: http://localhost:8080/api
- **NuxtJS Frontend**: http://localhost:8080
- **Grafana Dashboard**: http://localhost:8888 (default credentials: admin/admin)

## OpenTelemetry Configuration

The project is configured to send telemetry data (traces, metrics, and logs) to the Grafana LGTM stack. The main configuration is in the `docker-compose.yml` file:

- **OTLP Endpoints**:
  - gRPC: Port 4317
  - HTTP: Port 4318

- **Environment Variables**:
  - `OTEL_SERVICE_NAME`: Service name for telemetry data
  - `OTEL_TRACES_EXPORTER`: OpenTelemetry traces exporter
  - `OTEL_METRICS_EXPORTER`: OpenTelemetry metrics exporter
  - `OTEL_LOGS_EXPORTER`: OpenTelemetry logs exporter
  - `OTEL_EXPORTER_OTLP_PROTOCOL`: Protocol for sending telemetry data
  - `OTEL_EXPORTER_OTLP_ENDPOINT`: Endpoint for the OpenTelemetry collector
  - `OTEL_PROPAGATORS`: Context propagation configuration

## Features

- Full-stack observability with distributed tracing
- Database query monitoring
- HTTP request/response monitoring
- Custom span attributes and events
- Cross-service trace correlation

## Development

### Adding Custom Instrumentation

To add custom instrumentation to your Laravel application:

```php
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

$tracer = \OpenTelemetry\API\GlobalTracerProvider::getTracer('app_or_component_name');

$span = $tracer->spanBuilder('operation_name')
    ->setSpanKind(SpanKind::KIND_SERVER)
    ->startSpan();

$scope = $span->activate();

try {
    // Your code here
    $span->setAttribute('attribute.name', 'value');
    
    // Record events
    $span->addEvent('event.name', [
        'key' => 'value',
    ]);
    
    $span->setStatus(StatusCode::STATUS_OK);
} catch (\Exception $e) {
    $span->recordException($e);
    $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
    throw $e;
} finally {
    $span->end();
    $scope->detach();
}
```

## Troubleshooting

- **No telemetry data in Grafana**: Ensure the OpenTelemetry collector is running and accessible from your application containers.
- **PHP OpenTelemetry extension issues**: Check that the extension is properly installed and enabled in your PHP configuration.

## License

[MIT](LICENSE)
