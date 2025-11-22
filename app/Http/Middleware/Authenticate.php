<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Determine desired login route based on URL prefix
        $first = $request->segment(1);

        if (in_array($first, ['employee', 'employees'])) {
            if (RouteFacade::has('employee.login')) {
                return route('employee.login');
            }
        }

        if ($first === 'teacher') {
            if (RouteFacade::has('teacher.login')) {
                return route('teacher.login');
            }
        }

        if ($first === 'student') {
            if (RouteFacade::has('student.login')) {
                return route('student.login');
            }
        }

        // Fallbacks: prefer a 'login' named route, then employee.login, otherwise root
        if (RouteFacade::has('login')) {
            return route('login');
        }

        if (RouteFacade::has('employee.login')) {
            return route('employee.login');
        }

        return '/';
    }
}
