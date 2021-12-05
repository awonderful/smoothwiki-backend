<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use App\Libraries\Result;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(Result::error('NOT_LOGGED_IN'));
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        $map = [
            '\App\Exceptions\SpaceNotExistException'    => 'SPACE_NOT_EXIST',
            '\App\Exceptions\ArticleNotExistException'  => 'ARTICLE_NOT_EXIST',
            '\App\Exceptions\ArticleRemovedException'   => 'ARTICLE_REMOVED',
            '\App\Exceptions\ArticleUpdatedException'   => 'ARTICLE_UPDATED',
            '\App\Exceptions\PageNotExistException'     => 'PAGE_NOT_EXIST',
            '\App\Exceptions\PageRemovedException'      => 'PAGE_REMOVED',
            '\App\Exceptions\PageUpdatedException'      => 'PAGE_UPDATED',
            '\App\Exceptions\TreeNodeNotExistException' => 'TREE_NODE_NOT_EXIST',
            '\App\Exceptions\TreeNotExistException'     => 'TREE_NOT_EXIST',
            '\App\Exceptions\TreeUpdatedException'      => 'TREE_UPDATED',
            '\App\Exceptions\PermissionDeniedException' => 'PERMISSION_DENIED',
            '\App\Exceptions\DuplicateEmailException'   => 'DUPLICATE_EMAIL'
        ];

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return response()->json(Result::get('INVALID_PARAM', $exception->errors()));
        }

        foreach ($map as $exceptionClass => $errorAlias) {
            if ($exception instanceof $exceptionClass) {
                return response()->json(Result::error($errorAlias));
            }
        }

        return parent::render($request, $exception);
    }
}
