import { trace, SpanStatusCode } from '@opentelemetry/api'

export default defineNuxtPlugin( {
  name: 'error-hook',
  dependsOn: ['otel-init'],
  setup (nuxtApp) {
    const tracer = trace.getTracer('nuxt-frontend', '1.0.0')

    // Vue 組件錯誤
    nuxtApp.vueApp.config.errorHandler = (error, instance, info) => {
      tracer.startActiveSpan('vue-error', (span) => {
        if (error instanceof Error) {
          span.recordException(error)
          span.setStatus({ code: SpanStatusCode.ERROR, message: error.message })
        } else {
          span.recordException({ message: String(error) })
          span.setStatus({ code: SpanStatusCode.ERROR, message: String(error) })
        }
        span.setAttribute('info', info)
        span.setStatus({ code: SpanStatusCode.ERROR, message: error.message })
        span.end()
      })
    }

    // 全域 JS 錯誤
    window.onerror = function (message, url, line, column, error) {
      tracer.startActiveSpan('window-error', (span) => {
        const err = error || message
        if (err instanceof Error) {
          span.recordException(err)
          span.setStatus({ code: SpanStatusCode.ERROR, message: err.message })
        } else {
          span.recordException({ message: String(err) })
          span.setStatus({ code: SpanStatusCode.ERROR, message: String(err) })
        }
        span.setAttribute('url', url)
        span.setAttribute('line', line)
        span.setAttribute('column', column)
        span.end()
      })
    }

    // Promise 未捕捉錯誤
    window.onunhandledrejection = function (event) {
      tracer.startActiveSpan('unhandledrejection', (span) => {
        span.recordException(event.reason)
        span.setStatus({ code: SpanStatusCode.ERROR, message: String(event.reason) })
        span.end()
      })
    }
  }
})
