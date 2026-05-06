<?php

declare(strict_types=1);

namespace App\Core;

final class Application
{
    private string $basePath;

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    private ?Database $database = null;

    private Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->config = $this->loadConfig();
        $this->router = new Router($this);
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->router->dispatch(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/'
        );
    }

    public function getBasePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @return array<string, mixed>
     */
    public function config(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? $default;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function database(): Database
    {
        if ($this->database === null) {
            $databaseConfig = $this->config('database');
            $defaultConnection = $databaseConfig['default'] ?? 'mysql';

            $this->database = Database::getInstance(
                $databaseConfig['connections'][$defaultConnection] ?? []
            );
        }

        return $this->database;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        return [
            'app' => require $this->getBasePath('config/app.php'),
            'database' => require $this->getBasePath('config/database.php'),
        ];
    }

    private function loadRoutes(): void
    {
        foreach (['routes/web.php', 'routes/admin.php'] as $routeFile) {
            $router = $this->router;
            require $this->getBasePath($routeFile);
        }
    }
}
