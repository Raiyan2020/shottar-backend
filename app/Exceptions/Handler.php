<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $this->renderable(function (PostTooLargeException $e, $request) {
            // لو API
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'The uploaded file is too large. Please choose a smaller file.',
                ], 413); // 413 Payload Too Large
            }

            // لو فورم عادي
            return redirect()->back()
                ->withInput()
                ->withErrors(['file' => 'The uploaded file is too large.']);
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*')) {
            return $this->renderApiException($request, $exception);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->view('errors.405', [], 405);
        }

        if ($exception instanceof NotFoundHttpException) {
            if (! ($request->is('ar/admin*') || $request->is('en/admin*'))) {
                return response()->view('errors.404', [], 404);
            }
        }

        return parent::render($request, $exception);
    }

    /**
     * §8 — كل ردود الـ API لازم تكون JSON بكود حالة صحيح.
     *
     * قبل كده كل الأخطاء كانت بترجع **HTTP 400** (لأن sendError الافتراضي 400)،
     * فالتطبيق مكانش يعرف يفرّق بين "الجلسة خلصت" و"فيه غلط في البيانات" —
     * وده اللي كان مخلّي شاشات المواد تفضل فاضية وزرار تسجيل الخروج ميشتغلش.
     */
    protected function renderApiException($request, Throwable $exception)
    {
        // توكن ناقص / ملغي / غلط → 401 عشان التطبيق يفضّي الجلسة ويروّح للّوجين
        if ($exception instanceof AuthenticationException) {
            return sendError(
                $request->header('lang') === 'en'
                    ? 'Unauthenticated. Please log in again.'
                    : 'انتهت الجلسة، يرجى تسجيل الدخول مرة أخرى.',
                [],
                401
            );
        }

        if ($exception instanceof ValidationException) {
            // بنسيب الكود 400 زي ما كان (التطبيق معتمد عليه) بس بنرجّع رسالة
            // الحقل الحقيقية بدل "The given data was invalid." + تفاصيل الأخطاء.
            return sendError(
                $exception->validator->errors()->first() ?: $exception->getMessage(),
                $exception->errors(),
                $exception->status === 422 ? 400 : $exception->status
            );
        }

        if ($exception instanceof AuthorizationException || $exception instanceof AccessDeniedHttpException) {
            return sendError($exception->getMessage() ?: 'This action is unauthorized.', [], 403);
        }

        if ($exception instanceof ModelNotFoundException) {
            return sendError('Resource not found.', [], 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return sendError('Endpoint not found.', [], 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return sendError('Method not allowed.', [], 405);
        }

        if ($exception instanceof ThrottleRequestsException) {
            return sendError('Too many requests. Please slow down.', [], 429);
        }

        // أي HttpException تانية بتحافظ على الكود بتاعها
        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();

            return sendError(
                $exception->getMessage() ?: 'Request failed.',
                [],
                $status >= 400 ? $status : 500
            );
        }

        // خطأ غير متوقع → 500، ومن غير تسريب تفاصيل داخلية في الإنتاج
        $this->report($exception);

        if (config('app.debug')) {
            return sendError($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ], 500);
        }

        return sendError(
            $request->header('lang') === 'en'
                ? 'Something went wrong, please try again.'
                : 'حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى.',
            [],
            500
        );
    }
}
