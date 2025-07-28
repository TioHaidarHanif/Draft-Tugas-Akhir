# Role Middleware Documentation

## Overview

The `CheckRole` middleware is used to restrict access to routes based on user roles. This middleware verifies if the authenticated user has the required role(s) to access a specific route.

## How It Works

1. The middleware checks if the user is authenticated
2. If roles are specified, it checks if the user's role is in the list of allowed roles
3. If the user has the required role, the request proceeds to the next middleware or controller
4. If the user does not have the required role, a 403 Forbidden response is returned

## Supported Roles

The system supports the following user roles:

- `admin`: Administrative users with full access to all features
- `disposisi`: Staff members assigned to handle tickets
- `user`: Regular users (students) who can create and manage their own tickets

## Usage Examples

### Protecting Routes for a Single Role

```php
// Route accessible only by admin users
Route::middleware('role:admin')->get('/admin/dashboard', function() {
    // Only admins can access this route
});
```

### Protecting Routes for Multiple Roles

```php
// Route accessible by admin or disposisi users
Route::middleware('role:admin,disposisi')->get('/staff/dashboard', function() {
    // Both admin and disposisi roles can access this route
});
```

### Using with Route Groups

```php
// Group of routes accessible only by admin users
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
});
```

## Response Format

### Unauthorized (401)
If the user is not authenticated, the middleware returns:

```json
{
    "status": "error",
    "message": "Unauthorized access",
    "code": 401
}
```

### Forbidden (403)
If the user is authenticated but doesn't have the required role:

```json
{
    "status": "error",
    "message": "You do not have permission to access this resource",
    "code": 403
}
```

## Testing

The middleware has been tested with various scenarios to ensure it properly restricts access based on user roles. You can run the tests using:

```bash
php artisan test --filter=RoleMiddlewareTest
```
