<?php
namespace Core;

class Router {
    private $routes = [];
    private $notFoundCallback;
    
    // Add GET route
    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }
    
    // Add POST route
    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }
    
    // Add PUT route
    public function put($path, $callback) {
        $this->routes['PUT'][$path] = $callback;
    }
    
    // Add DELETE route
    public function delete($path, $callback) {
        $this->routes['DELETE'][$path] = $callback;
    }
    
    // Set 404 handler
    public function setNotFound($callback) {
        $this->notFoundCallback = $callback;
    }
    
    // Dispatch route
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = '/JandJLoanApp/api';
        
        // Remove base path from URI
        if(strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Handle OPTIONS method for CORS
        if($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            exit;
        }
        
        // Find matching route
        foreach($this->routes[$method] ?? [] as $routePath => $callback) {
            $routePath = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $routePath);
            $routePath = str_replace('/', '\/', $routePath);
            
            if(preg_match('/^' . $routePath . '$/', $uri, $matches)) {
                array_shift($matches);
                
                // Execute callback with parameters
                if(is_callable($callback)) {
                    return call_user_func_array($callback, $matches);
                }
                
                // Handle controller@method format
                if(is_string($callback) && strpos($callback, '@') !== false) {
                    list($controllerClass, $method) = explode('@', $callback);
                    $controllerClass = "\\Controllers\\{$controllerClass}";
                    $controller = new $controllerClass();
                    return call_user_func_array([$controller, $method], $matches);
                }
            }
        }
        
        // Handle 404
        if($this->notFoundCallback) {
            return call_user_func($this->notFoundCallback);
        }
        
        $this->jsonResponse(['error' => 'Route not found'], 404);
    }
    
    private function jsonResponse($data, $statusCode) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>