import { WebTracerProvider } from '@opentelemetry/sdk-trace-web'
import { BatchSpanProcessor } from '@opentelemetry/sdk-trace-base'
import { OTLPTraceExporter } from '@opentelemetry/exporter-trace-otlp-http'
import {
  resourceFromAttributes,
  defaultResource
} from '@opentelemetry/resources'
import { ZoneContextManager } from '@opentelemetry/context-zone'
export default defineNuxtPlugin({
  name: 'otel-init',
  setup () {
    const customResource = resourceFromAttributes({
      'service.name': 'frontend',
      'service.version': '1.0.0'
    })
    const mergedResource = defaultResource().merge(customResource)
    const provider = new WebTracerProvider({
      resource: mergedResource,
      spanProcessors: [
        new BatchSpanProcessor(
          new OTLPTraceExporter({
            url: 'http://localhost:5318/v1/traces'
          })
        )
      ]
    })
    provider.register({
      contextManager: new ZoneContextManager()
    })
  }
})