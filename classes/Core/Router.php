<?php

namespace App\Core;

class Router
{
    private static ?Router $instance = null;

    private array $getRoutes = [];
    private array $postRoutes = [];

    private function __construct()
    {
    }

    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new Router();
        }

        return self::$instance;
    }

    public function get(string $route, callable $handler): void
    {
        $this->getRoutes[$route] = $handler;
    }

    public function post(string $route, callable $handler): void
    {
        $this->postRoutes[$route] = $handler;
    }

    public function dispatch(string $route, string $method): void
    {
        $method = strtoupper($method);

        $routes = $method === 'POST'
            ? $this->postRoutes
            : $this->getRoutes;

        if (!isset($routes[$route])) {
            http_response_code(404);
            echo 'Сторінку не знайдено.';
            return;
        }

        call_user_func($routes[$route]);
    }
}