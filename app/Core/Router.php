<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int,array{method:string,pattern:string,handler:mixed,middleware:array<int,string>,name:?string}> */
    private array $routes = [];

    /** @var array<int,string> */
    private array $groupMiddleware = [];

    public function get(string $pattern, mixed $handler, ?string $name = null): self
    {
        return $this->add('GET', $pattern, $handler, $name);
    }

    public function post(string $pattern, mixed $handler, ?string $name = null): self
    {
        return $this->add('POST', $pattern, $handler, $name);
    }

    public function put(string $pattern, mixed $handler, ?string $name = null): self
    {
        return $this->add('PUT', $pattern, $handler, $name);
    }

    public function delete(string $pattern, mixed $handler, ?string $name = null): self
    {
        return $this->add('DELETE', $pattern, $handler, $name);
    }

    public function group(array $middleware, callable $callback): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = array_merge($previous, $middleware);
        $callback($this);
        $this->groupMiddleware = $previous;
    }

    private function add(string $method, string $pattern, mixed $handler, ?string $name): self
    {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $this->groupMiddleware,
            'name'       => $name,
        ];

        return $this;
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                $class = $this->middlewareClass($middleware);
                if (class_exists($class)) {
                    (new $class())->handle($request);
                }
            }

            $this->callHandler($route['handler'], $request, $params);

            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Không tìm thấy trang'], 'layouts/main');
    }

    private function middlewareClass(string $middleware): string
    {
        return 'App\\Middleware\\' . $middleware;
    }

    private function callHandler(mixed $handler, Request $request, array $params): void
    {
        if (is_callable($handler)) {
            $handler($request, $params);

            return;
        }

        [$class, $action] = $handler;
        $controller = new $class();
        $controller->{$action}($params);
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if ($regex === null) {
            return null;
        }

        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
