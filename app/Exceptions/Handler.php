<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Http\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Exception $e
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
        if (env('APP_DEBUG')) {
            return parent::render($request, $e);
        }

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $class = get_class($e);

        switch ($class) {
            case 'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException':
                $status = Response::HTTP_METHOD_NOT_ALLOWED;
                $e = new MethodNotAllowedHttpException([], 'HTTP_METHOD_NOT_ALLOWED', $e);
                break;
            case 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException':
                $status = Response::HTTP_NOT_FOUND;
                $e = new NotFoundHttpException('HTTP_NOT_FOUND', $e);
                break;
            case 'Illuminate\Auth\Access\AuthorizationException':
                $status = Response::HTTP_FORBIDDEN;
                $e = new AuthorizationException('HTTP_FORBIDDEN', $status);
                break;
            case 'Illuminate\Auth\AuthenticationException':
                $status = Response::HTTP_FORBIDDEN;
                $e = new AuthenticationException;
                break;
            case 'Dotenv\Exception\ValidationException':
                $status = Response::HTTP_BAD_REQUEST;
                $e = new \Dotenv\Exception\ValidationException('HTTP_BAD_REQUEST', $status, $e);
                break;
            default:
                $e = new HttpException($status, 'HTTP_INTERNAL_SERVER_ERROR');
                break;
        }

        return response()->json([
            'status' => $status,
            'message' => $e->getMessage()
        ], $status);
    }
}
