<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

class ApiDocumentationController extends Controller
{
    public function index()
    {
        $apiRoutes = $this->getApiRoutes();
        $groupedRoutes = $this->groupRoutesByController($apiRoutes);
        
        return view('api-documentation', [
            'groupedRoutes' => $groupedRoutes,
            'apiInfo' => $this->getApiInfo(),
        ]);
    }
    
    private function getApiRoutes()
    {
        $routes = Route::getRoutes();
        $apiRoutes = [];
        
        foreach ($routes as $route) {
            // Filter only API routes
            if (strpos($route->uri(), 'api/') === 0) {
                $controller = '';
                $method = '';
                
                // Extract controller and method name from the action
                if (isset($route->action['controller'])) {
                    $parts = explode('@', $route->action['controller']);
                    $controller = $parts[0];
                    $method = $parts[1] ?? '';
                }
                
                // Get middleware information
                $middleware = $this->getRouteMiddleware($route);
                
                // Generate a description based on the method name if possible
                $description = $this->generateDescription($method);
                
                // Get parameters information
                $parameters = $this->getRouteParameters($route->uri());
                
                // Get method documentation if available
                $methodDoc = $this->getMethodDocumentation($controller, $method);
                
                $apiRoutes[] = [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'controller' => $controller,
                    'controllerMethod' => $method,
                    'middleware' => $middleware,
                    'description' => $methodDoc['description'] ?? $description,
                    'parameters' => $parameters,
                    'returns' => $methodDoc['returns'] ?? '',
                    'example' => $methodDoc['example'] ?? '',
                ];
            }
        }
        
        return $apiRoutes;
    }
    
    private function getRouteMiddleware($route)
    {
        $middleware = [];
        
        if (isset($route->action['middleware'])) {
            $middleware = (array) $route->action['middleware'];
            
            // Extract role information from middleware
            foreach ($middleware as $key => $value) {
                if (strpos($value, 'role:') === 0) {
                    $roles = str_replace('role:', '', $value);
                    $middleware['roles'] = explode(',', $roles);
                    unset($middleware[$key]);
                }
            }
        }
        
        return $middleware;
    }
    
    private function getRouteParameters($uri)
    {
        preg_match_all('/{([^}]+)}/', $uri, $matches);
        return $matches[1] ?? [];
    }
    
    private function generateDescription($methodName)
    {
        if (empty($methodName)) {
            return '';
        }
        
        // Convert camelCase to human-readable format
        $words = preg_split('/(?=[A-Z])/', $methodName);
        $description = implode(' ', $words);
        
        // Handle special method names
        $actionMap = [
            'index' => 'Get a list of',
            'show' => 'Get details of',
            'store' => 'Create a new',
            'update' => 'Update an existing',
            'destroy' => 'Delete a',
            'restore' => 'Restore a deleted',
        ];
        
        if (array_key_exists($methodName, $actionMap)) {
            $controller = explode('Controller', class_basename($methodName))[0] ?? '';
            $singularController = rtrim($controller, 's');
            return $actionMap[$methodName] . ' ' . $singularController;
        }
        
        return ucfirst($description);
    }
    
    private function groupRoutesByController($routes)
    {
        $grouped = [];
        
        foreach ($routes as $route) {
            $controllerName = $route['controller'];
            if (!empty($controllerName)) {
                $shortName = class_basename($controllerName);
                if (!isset($grouped[$shortName])) {
                    $grouped[$shortName] = [];
                }
                $grouped[$shortName][] = $route;
            } else {
                if (!isset($grouped['Other'])) {
                    $grouped['Other'] = [];
                }
                $grouped['Other'][] = $route;
            }
        }
        
        return $grouped;
    }
    
    private function getMethodDocumentation($controller, $method)
    {
        if (empty($controller) || empty($method) || !class_exists($controller)) {
            return [];
        }
        
        try {
            $reflectionClass = new ReflectionClass($controller);
            if (!$reflectionClass->hasMethod($method)) {
                return [];
            }
            
            $reflectionMethod = $reflectionClass->getMethod($method);
            $docComment = $reflectionMethod->getDocComment();
            
            if (!$docComment) {
                return [];
            }
            
            // Parse the doc comment to extract documentation
            $doc = [
                'description' => '',
                'parameters' => [],
                'returns' => '',
                'example' => '',
            ];
            
            // Extract description (first line)
            if (preg_match('/\/\*\*\s*\n\s*\*\s*(.*?)\n/s', $docComment, $matches)) {
                $doc['description'] = trim($matches[1]);
            }
            
            // Extract @param tags
            preg_match_all('/@param\s+(\S+)\s+\$(\S+)\s*(.*?)(?=\n\s*\*\s*@|\n\s*\*\/)/s', $docComment, $paramMatches, PREG_SET_ORDER);
            foreach ($paramMatches as $match) {
                $doc['parameters'][] = [
                    'type' => $match[1],
                    'name' => $match[2],
                    'description' => trim($match[3]),
                ];
            }
            
            // Extract @return tag
            if (preg_match('/@return\s+(\S+)\s*(.*?)(?=\n\s*\*\s*@|\n\s*\*\/)/s', $docComment, $returnMatch)) {
                $doc['returns'] = [
                    'type' => $returnMatch[1],
                    'description' => trim($returnMatch[2] ?? ''),
                ];
            }
            
            // Extract @example tag
            if (preg_match('/@example\s*(.*?)(?=\n\s*\*\s*@|\n\s*\*\/)/s', $docComment, $exampleMatch)) {
                $doc['example'] = trim($exampleMatch[1]);
            }
            
            return $doc;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function getApiInfo()
    {
        return [
            'title' => 'Helpdesk API Documentation',
            'version' => '1.0.0',
            'description' => 'This documentation provides information about all available endpoints in the Helpdesk API.',
            'authentication' => [
                'type' => 'Bearer Token',
                'description' => 'Most endpoints require authentication using a Bearer token. You can obtain a token by using the login endpoint.',
                'example' => 'Authorization: Bearer {your_token_here}',
            ],
            'fileUploadLimits' => [
                'description' => 'File uploads are limited to 10MB per file.',
                'formats' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
            ],
            'rateLimit' => [
                'description' => 'API requests are limited to 60 per minute per user.',
            ],
            'errorResponses' => [
                [
                    'code' => 400,
                    'name' => 'Bad Request',
                    'description' => 'The request was malformed or contains invalid parameters.',
                ],
                [
                    'code' => 401,
                    'name' => 'Unauthorized',
                    'description' => 'Authentication is required or the provided credentials are invalid.',
                ],
                [
                    'code' => 403,
                    'name' => 'Forbidden',
                    'description' => 'The authenticated user does not have permission to access the requested resource.',
                ],
                [
                    'code' => 404,
                    'name' => 'Not Found',
                    'description' => 'The requested resource was not found.',
                ],
                [
                    'code' => 422,
                    'name' => 'Unprocessable Entity',
                    'description' => 'Validation errors occurred.',
                ],
                [
                    'code' => 429,
                    'name' => 'Too Many Requests',
                    'description' => 'Rate limit exceeded.',
                ],
                [
                    'code' => 500,
                    'name' => 'Internal Server Error',
                    'description' => 'An unexpected error occurred on the server.',
                ],
            ],
        ];
    }
}
