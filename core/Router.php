<?php

namespace Callie;

class Router {
    protected $routes = [];
    protected $prefix = '';

    public function group($prefix, $callback) {
        $previousPrefix = $this->prefix;
        $this->prefix .= $prefix;
        $callback($this);
        $this->prefix = $previousPrefix;
    }

    public function get($path, $handler) { $this->add('GET', $path, $handler); }
    public function post($path, $handler) { $this->add('POST', $path, $handler); }
    public function put($path, $handler) { $this->add('PUT', $path, $handler); }
    public function delete($path, $handler) { $this->add('DELETE', $path, $handler); }

    protected function add($method, $path, $handler) {
        $fullPath = $this->prefix . $path;
        
        // Convert /users/:id to regex /users/([^/]+)
        $pattern = preg_replace_callback('/:([a-zA-Z0-9_]+)/', function($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $fullPath);
        
        // Add start/end delimiters
        $pattern = "#^" . $pattern . "$#";

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch(Context $ctx) {
        // Handle Base Path (if app is running in subdirectory like /api)
        // For shared hosting, often we are in root or public_html/api
        // We need to strip the script name dir if present
        
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $requestPath = $ctx->path;
        
        // If script is in /api/index.php, scriptDir is /api
        // We want /api/users to become /users IF we defined route as /users
        // BUT, usually in PHP frameworks, we define full routes or rely on RewriteBase
        // Let's assume standard routing where route definition matches URI
        
        // Hotfix for subdirectory routing: 
        // If user visits /api/users, and we invoke index.php via .htaccess in /api,
        // The REQUEST_URI is /api/users.
        // If our route is defined as '/users', we need to match that.
        // But if we defined route as '/api/users', we match that.
        // Let's stick thereto absolute matching for now. The user should define routes relative to the entry point
        // OR we strip the base.
        
        if ($scriptDir !== '/' && strpos($requestPath, $scriptDir) === 0) {
            $requestPath = substr($requestPath, strlen($scriptDir));
        }
        if ($requestPath === '') $requestPath = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $ctx->method) continue;

            if (preg_match($route['pattern'], $requestPath, $matches)) {
                
                // Extract params
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                $ctx->params = $params;

                // Execute Handler
                try {
                    return call_user_func($route['handler'], $ctx);
                } catch (Exception $e) {
                    return $ctx->error($e->getMessage(), 500);
                }
            }
        }

        return $ctx->error('Not Found: ' . $requestPath, 404);
    }
}
