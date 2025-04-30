<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenTelemetry\API\Trace\Span;
use Throwable;
use OpenTelemetry\API\Trace\StatusCode;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Report or log an exception.
     *
     * @param Throwable $e
     * @return void
     */
    public function report(Throwable $e): void
    {
        // 額外處理 OpenTelemetry 報告（可選）
        $span = Span::getCurrent();
        if ($span->isRecording()) {
            $span->recordException($e);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return JsonResponse
     */
    public function render($request, Throwable $e): JsonResponse
    {
        // 取得當前 OpenTelemetry Span
        $span = Span::getCurrent();

        // 如果存在正在記錄的 Span
        if ($span->isRecording()) {
            try {
                // 記錄例外資訊到 Span
                $span->recordException($e);

                // 設定 Span 狀態為錯誤
                $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());

                // 設定 HTTP 狀態碼屬性
                if (method_exists($e, 'getStatusCode')) {
                    $span->setAttribute('http.status_code', $e->getStatusCode());
                }
            } finally {
                // 無論如何都強制結束 Span（防止記憶體洩漏）
                $span->end();
            }
        }

        return response()->json([
            'error' => 'Server Error',
            'message' => env('APP_DEBUG') ? $e->getMessage() : 'An error occurred. Please contact support.',
            'status_code' => StatusCode::STATUS_ERROR,
            'trace_id' => $span->getContext()->getTraceId()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
