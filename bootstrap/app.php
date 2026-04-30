<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApplyLocale;
use App\Http\Middleware\EnsureAdministrator;
use App\Http\Middleware\EnsureEmployeeSelfService;
use App\Http\Middleware\EnsurePayrollAccess;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->web(append: [
            ApplyLocale::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdministrator::class,
            'employee.self-service' => EnsureEmployeeSelfService::class,
            'payroll.access' => EnsurePayrollAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
