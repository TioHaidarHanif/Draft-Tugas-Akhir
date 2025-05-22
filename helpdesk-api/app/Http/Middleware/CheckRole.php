<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|array  $roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if the user is authenticated
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
                'code' => 401
            ], 401);
        }

        // If no roles are specified or the user's role is in the allowed roles, proceed
        if (empty($roles) || in_array($request->user()->role, $roles)) {
            return $next($request);
        }

        // If the user's role is not in the allowed roles, return a forbidden response
        return response()->json([
            'status' => 'error',
            'message' => 'You do not have permission to access this resource',
            'code' => 403
        ], 403);
    }
}
