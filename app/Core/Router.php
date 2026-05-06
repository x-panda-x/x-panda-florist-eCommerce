<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

final class Router
{
    private Application $app;

    /**
     * @var array<string, array<string, Closure|string>>
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function get(string $path, Closure|string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, Closure|string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');
        $method = strtoupper($method);
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if ($handler instanceof Closure) {
            echo (string) $handler($this->app);
            return;
        }

        echo (string) $this->dispatchController($handler);
    }

    private function addRoute(string $method, string $path, Closure|string $handler): void
    {
        $this->routes[$method][$this->normalizePath($path)] = $handler;
    }

    private function normalizePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . trim($path, '/');
    }

    private function dispatchController(string $handler): mixed
    {
        [$controllerName, $action] = array_pad(explode('@', $handler, 2), 2, null);

        if ($controllerName === null || $action === null) {
            throw new \RuntimeException('Invalid route handler.');
        }

        $controllerClass = 'App\\Controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException('Controller not found: ' . $controllerClass);
        }

        $controller = new $controllerClass($this->app);

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException('Controller action not found: ' . $handler);
        }

        return $controller->{$action}();
    }
}
