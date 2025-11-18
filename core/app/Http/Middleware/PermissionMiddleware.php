<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
public function handle(Request $request, Closure $next): Response
{
    // skip auth checks for login route
   $user = Auth::guard('admin')->user();

if (!$user) {
    return response()->json(['error' => 'Unauthorized (admin guard)'], 401);
}

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $routePath = '/'.$request->path();
    $permission = Permission::where('name', $routePath)->first();
    if (!$permission) {
        // return response()->json([
        //     'status' => 'error',
        //     'message' => 'Permission not found: ' . $routePath
        // ], 403);
    }

    // if (!$user->can($permission->name)) {
    // return redirect()->route('unauthorised')->with('error', 'Access Denied');

    // }

    return $next($request);
}


}
