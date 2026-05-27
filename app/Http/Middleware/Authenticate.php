<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Si es una petición que espera JSON, no redirigir — devolver null
        return $request->expectsJson() ? null : route('login');
    }
}
