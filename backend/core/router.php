<?php

class Router
{
    private array $routes = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    public function get(string $path, $handler)
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler)
    {
        return $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler)
    {
        $route = [
            'method' => $method,
            'path' => $this->prefix . $path,
            'handler' => $handler,
            'middleware' => $this->groupMiddleware
        ];

        $this->routes[] = $route;
        return $this;
    }

    public function middleware(string|array $middleware)
    {
        $middleware = (array) $middleware;
        $this->routes[array_key_last($this->routes)]['middleware'] = array_merge(
            $this->routes[array_key_last($this->routes)]['middleware'],
            $middleware
        );

        return $this;
    }

    public function group(string $prefix, callable $callback, array $middleware = [])
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->prefix .= $prefix;
        $this->groupMiddleware = array_merge(
            $this->groupMiddleware,
            $middleware
        );

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;

        return $this;
    }


    public function withMiddleware(string|array $middleware)
    {
        $this->groupMiddleware = array_merge(
            $this->groupMiddleware,
            (array) $middleware
        );

        return $this;
    }

    public function run()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

        foreach ($this->routes as $route) {

            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('#\{([^/]+)\}#', '([^/]+)', $route['path']);

            if (!preg_match("#^$pattern$#", $uri, $matches)) {
                continue;
            }

            array_shift($matches);

            // 1. Jalankan middleware
            foreach ($route['middleware'] as $middleware) {
                $this->runMiddleware($middleware);
            }

            // 2. Jalankan handler
            $request = ($method === 'POST')
                ? array_merge($_POST, $_FILES)
                : $_GET;

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $method] = $handler;
                return (new $class)->$method($request, ...$matches);
            }

            return $handler($request, ...$matches);
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    private function runMiddleware(string $middleware)
    {
        // role:admin
        if (str_contains($middleware, ':')) {
            [$name, $param] = explode(':', $middleware, 2);
        } else {
            $name = $middleware;
            $param = null;
        }

        match ($name) {
            'auth' => AuthMiddleware::handle(),
            'csrf' => CsrfMiddleware::handle(),
            'role' => RoleMiddleware::handle($param),
            default => null
        };
    }
}
