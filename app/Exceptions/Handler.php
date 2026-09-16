<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

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

        // ПЕРЕХВАТЧИК 1: Ошибка роута
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('nova-api/*')) {
                return response()->json([
                    'error' => 'Route not found!',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 404);
            }
        });

        // ПЕРЕХВАТЧИК 2: Ошибка поиска модели в БД
        $this->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('nova-api/*')) {
                return response()->json([
                    'error' => 'Model not found in Database!',
                    'model' => $e->getModel(),
                    'ids' => $e->getIds(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 404);
            }
        });
    }
}
