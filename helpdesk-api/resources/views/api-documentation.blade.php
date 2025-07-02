<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $apiInfo['title'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6cf7;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            padding-top: 56px;
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }
        
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            width: 250px;
            height: calc(100vh - 56px);
            background-color: #fff;
            box-shadow: 1px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            z-index: 1000;
            padding: 1rem 0;
        }
        
        .sidebar-link {
            display: block;
            padding: 0.5rem 1rem;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-link:hover {
            background-color: rgba(74, 108, 247, 0.1);
            color: var(--primary-color);
        }
        
        .sidebar-link.active {
            background-color: rgba(74, 108, 247, 0.2);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .api-section {
            margin-bottom: 3rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .api-section h2 {
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .endpoint-card {
            margin-bottom: 1.5rem;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .endpoint-header {
            display: flex;
            padding: 0.75rem 1rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .endpoint-method {
            font-weight: 600;
            margin-right: 1rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            min-width: 60px;
            text-align: center;
        }
        
        .method-get {
            background-color: #e7f5ff;
            color: #0c63e4;
        }
        
        .method-post {
            background-color: #d4edda;
            color: #28a745;
        }
        
        .method-put, .method-patch {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .method-delete {
            background-color: #f8d7da;
            color: #dc3545;
        }
        
        .endpoint-path {
            font-family: monospace;
            font-weight: 600;
            flex-grow: 1;
        }
        
        .endpoint-description {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .endpoint-body {
            padding: 1rem;
            display: none;
            background-color: #fff;
        }
        
        .endpoint-body.active {
            display: block;
        }
        
        .badge-auth {
            background-color: #e7f5ff;
            color: #0c63e4;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin-right: 0.5rem;
        }
        
        .badge-role {
            background-color: #d4edda;
            color: #28a745;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin-right: 0.5rem;
        }
        
        .parameters-table, .responses-table {
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .parameters-table th, .responses-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .parameter-name {
            font-family: monospace;
            font-weight: 600;
        }
        
        .parameter-type {
            font-size: 0.85rem;
            color: var(--secondary-color);
        }
        
        .code-block {
            background-color: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 1rem;
            font-family: monospace;
            overflow-x: auto;
            margin-bottom: 1rem;
        }
        
        .sidebar-mobile-toggle {
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar-mobile-toggle {
                display: block;
                position: fixed;
                bottom: 20px;
                right: 20px;
                background-color: var(--primary-color);
                color: white;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                text-align: center;
                line-height: 50px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                z-index: 1001;
                cursor: pointer;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">{{ $apiInfo['title'] }}</a>
            <div class="d-flex">
                <span class="navbar-text text-white">Version: {{ $apiInfo['version'] }}</span>
            </div>
        </div>
    </nav>

    <div class="sidebar" id="sidebar">
        <div class="p-3">
            <div class="fw-bold mb-2">Table of Contents</div>
            <a href="#introduction" class="sidebar-link">Introduction</a>
            <a href="#authentication" class="sidebar-link">Authentication</a>
            <a href="#rate-limiting" class="sidebar-link">Rate Limiting</a>
            <a href="#file-uploads" class="sidebar-link">File Uploads</a>
            <a href="#error-handling" class="sidebar-link">Error Handling</a>
            
            <div class="fw-bold mt-3 mb-2">Endpoints</div>
            @foreach($groupedRoutes as $controller => $routes)
                <a href="#{{ \Illuminate\Support\Str::slug($controller) }}" class="sidebar-link">{{ $controller }}</a>
            @endforeach
        </div>
    </div>

    <div class="main-content">
        <div class="api-section" id="introduction">
            <h2>Introduction</h2>
            <p>{{ $apiInfo['description'] }}</p>
            <p>This documentation is automatically generated from the API routes and controllers in the application. It provides information about all available endpoints, their parameters, and authentication requirements.</p>
        </div>

        <div class="api-section" id="authentication">
            <h2>Authentication</h2>
            <p>{{ $apiInfo['authentication']['description'] }}</p>
            <div class="code-block">
                {{ $apiInfo['authentication']['example'] }}
            </div>
            <h5>Authentication Flow</h5>
            <ol>
                <li>Register a new user using the <code>/api/auth/register</code> endpoint</li>
                <li>Log in using the <code>/api/auth/login</code> endpoint to obtain a token</li>
                <li>Include the token in the Authorization header for all authenticated requests</li>
            </ol>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> The token will expire after 24 hours, after which you'll need to log in again.
            </div>
        </div>

        <div class="api-section" id="rate-limiting">
            <h2>Rate Limiting</h2>
            <p>{{ $apiInfo['rateLimit']['description'] }}</p>
            <p>When you exceed the rate limit, you'll receive a 429 Too Many Requests response with a Retry-After header indicating how many seconds to wait before making another request.</p>
        </div>

        <div class="api-section" id="file-uploads">
            <h2>File Uploads</h2>
            <p>{{ $apiInfo['fileUploadLimits']['description'] }}</p>
            <p>Allowed file formats:</p>
            <ul>
                @foreach($apiInfo['fileUploadLimits']['formats'] as $format)
                    <li><code>{{ $format }}</code></li>
                @endforeach
            </ul>
            <p>File uploads should be sent as <code>multipart/form-data</code>.</p>
        </div>

        <div class="api-section" id="error-handling">
            <h2>Error Handling</h2>
            <p>The API uses conventional HTTP response codes to indicate the success or failure of a request.</p>
            <table class="table responses-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($apiInfo['errorResponses'] as $error)
                        <tr>
                            <td><code>{{ $error['code'] }}</code></td>
                            <td>{{ $error['name'] }}</td>
                            <td>{{ $error['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <h5>Example Error Response</h5>
            <div class="code-block">
{
    "status": "error",
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password field is required."
        ]
    }
}
            </div>
        </div>

        @foreach($groupedRoutes as $controller => $routes)
            <div class="api-section" id="{{ \Illuminate\Support\Str::slug($controller) }}">
                <h2>{{ $controller }}</h2>
                
                @foreach($routes as $route)
                    <div class="endpoint-card">
                        <div class="endpoint-header" onclick="toggleEndpoint(this)">
                            <div class="endpoint-method method-{{ strtolower(explode('|', $route['method'])[0]) }}">
                                {{ explode('|', $route['method'])[0] }}
                            </div>
                            <div class="endpoint-path">{{ $route['uri'] }}</div>
                            <div><i class="fas fa-chevron-down"></i></div>
                        </div>
                        <div class="endpoint-body">
                            <div class="endpoint-description">{{ $route['description'] }}</div>
                            
                            <div class="mt-3">
                                @if(in_array('auth:sanctum', $route['middleware']))
                                    <span class="badge-auth"><i class="fas fa-lock"></i> Authentication Required</span>
                                @endif
                                
                                @if(isset($route['middleware']['roles']))
                                    @foreach($route['middleware']['roles'] as $role)
                                        <span class="badge-role"><i class="fas fa-user-shield"></i> {{ ucfirst($role) }} Role</span>
                                    @endforeach
                                @endif
                            </div>
                            
                            @if(count($route['parameters']) > 0)
                                <h5 class="mt-3">URL Parameters</h5>
                                <table class="table parameters-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($route['parameters'] as $param)
                                            <tr>
                                                <td class="parameter-name">{{ $param }}</td>
                                                <td>The ID of the {{ str_replace('_', ' ', $param) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                            
                            <h5 class="mt-3">Example Request</h5>
                            <div class="code-block">
curl -X {{ explode('|', $route['method'])[0] }} \
    {{ in_array('auth:sanctum', $route['middleware']) ? '-H "Authorization: Bearer {your_token_here}" \\' : '' }}
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    {{ explode('|', $route['method'])[0] === 'POST' || explode('|', $route['method'])[0] === 'PUT' || explode('|', $route['method'])[0] === 'PATCH' ? '-d \'{"example": "value"}\' \\' : '' }}
    {{ config('app.url') }}/{{ $route['uri'] }}
                            </div>
                            
                            <h5 class="mt-3">Example Response</h5>
                            <div class="code-block">
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": {
        "id": 1,
        "name": "Example",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
    }
}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="sidebar-mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <script>
        function toggleEndpoint(element) {
            const body = element.nextElementSibling;
            body.classList.toggle('active');
            const icon = element.querySelector('.fa-chevron-down');
            if (icon) {
                icon.classList.toggle('fa-chevron-up');
            }
        }
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        // Highlight active section on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.api-section');
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            
            function highlightNavigation() {
                let scrollPosition = window.scrollY;
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop - 100;
                    const sectionBottom = sectionTop + section.offsetHeight;
                    
                    if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                        const id = section.getAttribute('id');
                        
                        sidebarLinks.forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === `#${id}`) {
                                link.classList.add('active');
                            }
                        });
                    }
                });
            }
            
            window.addEventListener('scroll', highlightNavigation);
            highlightNavigation(); // Initial check
        });
    </script>
</body>
</html>
