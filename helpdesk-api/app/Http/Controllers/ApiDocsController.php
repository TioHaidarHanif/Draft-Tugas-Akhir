<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class ApiDocsController extends Controller
{
    public function index()
    {
        // Ambil semua route API
        $routes = collect(RouteFacade::getRoutes())
            ->filter(function ($route) {
                return Str::startsWith($route->uri(), 'api/');
            })
            ->map(function ($route) {
                return [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'middleware' => $route->gatherMiddleware(),
                ];
            });

        // Contoh batasan dan info tambahan (bisa diambil dari config/kode jika ingin lebih dinamis)
        $limitations = [
            'File upload hanya JPG/PNG/PDF, maksimal 10MB',
            'Akses endpoint tertentu dibatasi oleh role (admin, disposisi, user)',
            'Autentikasi menggunakan Sanctum (Bearer Token)',
        ];

        // Contoh penggunaan API
        $usage = [
            'login' => [
                'curl' => "curl -X POST https://yourdomain.com/api/auth/login -d 'email=admin@admin.com&password=secret'",
                'postman' => [
                    'method' => 'POST',
                    'url' => '/api/auth/login',
                    'body' => [
                        'email' => 'admin@admin.com',
                        'password' => 'secret',
                    ],
                ],
            ],
        ];

        return view('api-docs', compact('routes', 'limitations', 'usage'));
    }
}
