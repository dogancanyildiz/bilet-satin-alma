<?php
/**
 * Simple Router System
 * Basit routing sistemi
 */

class Router {
    private $routes = [];
    private $currentRoute = null;

    public function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash
        $requestPath = rtrim($requestPath, '/');
        if (empty($requestPath)) {
            $requestPath = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestPath)) {
                $this->currentRoute = $route;
                return $this->executeHandler($route['handler']);
            }
        }

        // 404 - Route not found
        http_response_code(404);
        include __DIR__ . '/../views/404.php';
    }

    private function matchPath($routePath, $requestPath) {
        // Simple exact match for now
        return $routePath === $requestPath;
    }

    private function executeHandler($handler) {
        if (is_callable($handler)) {
            return call_user_func($handler);
        } elseif (is_string($handler)) {
            include __DIR__ . '/../views/' . $handler;
        }
    }

    public function getCurrentRoute() {
        return $this->currentRoute;
    }
}

// Global router instance
$router = new Router();