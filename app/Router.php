<?php
declare(strict_types=1);

/**
 * Minimal router that dispatches `?route=controller/action` to controller methods.
 */
final class Router
{
    public function dispatch(): void
    {
        $route = trim((string)($_GET['route'] ?? 'dashboard'), '/');
        $route = $route === '' ? 'dashboard' : $route;

        $parts = explode('/', $route);
        $controller = $this->normalize($parts[0] ?? 'dashboard');
        $action = $this->normalize($parts[1] ?? 'index');

        $class = $this->controllerClass($controller);
        $file = APP_PATH . '/controllers/' . $class . '.php';

        if (!is_file($file)) {
            $this->notFound();
        }

        require_once $file;
        if (!class_exists($class)) {
            $this->notFound();
        }

        $instance = new $class();
        if (!method_exists($instance, $action)) {
            $this->notFound();
        }

        $instance->{$action}();
    }

    private function normalize(string $s): string
    {
        $s = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $s) ?? '');
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $s)));
    }

    private function controllerClass(string $name): string
    {
        $map = [
            'Companies' => 'CompanyController',
            'Documents' => 'DocumentController',
            'Reports' => 'ReportController',
            'Cards' => 'CardController',
            'Einvoices' => 'EInvoiceController',
            'Integrations' => 'IntegrationController',
        ];
        return $map[$name] ?? ($name . 'Controller');
    }

    private function notFound(): void
    {
        http_response_code(404);
        require APP_PATH . '/views/404.php';
        exit;
    }
}
