<?php

use App\Mail\ExceptionMail;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Exception $exception) {
            if (app()->environment('local')) {
                return;
            }

            if ($exception instanceof Illuminate\Auth\AuthenticationException) {
                return;
            }

            if (method_exists($exception, 'getStatusCode') && in_array($exception->getStatusCode(), [403, 404, 405, 419])) {
                return;
            }

            try {
                $e = FlattenException::createFromThrowable($exception);
                $handler = new HtmlErrorRenderer(true); // boolean, true raises debug flag...
                $css = $handler->getStylesheet();
                $content = $handler->getBody($e);
                $url = Request::fullUrl();

                $userinfo = [];
                if (auth()->check()) {
                    $userinfo['User ID'] = auth()->user()->id;
                    $userinfo['User Email'] = auth()->user()->email;
                }
                $userinfo_string = '';
                foreach ($userinfo as $k => $v) {
                    $userinfo_string .= sprintf('%s: %s', $k, $v).'<br>';
                }

                Mail::send(new ExceptionMail($userinfo_string, $content, $url, $css));
            } catch (Throwable $ex) {
                dd($ex);
            }
        });
    })->create();
