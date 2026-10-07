<?php
declare(strict_types=1);

/**
 * Base controller providing view rendering with the AdminLTE layout.
 */
abstract class Controller
{
    /** Render a view inside the authenticated AdminLTE layout. */
    protected function view(string $template, array $data = []): void
    {
        $contentFile = APP_PATH . '/views/' . $template . '.php';
        if (!is_file($contentFile)) {
            throw new RuntimeException('View not found: ' . $template);
        }
        extract($data, EXTR_SKIP);
        require APP_PATH . '/views/layout/app.php';
    }

    /** Render a standalone view (no layout), e.g. the login page. */
    protected function render(string $template, array $data = []): void
    {
        $file = APP_PATH . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('View not found: ' . $template);
        }
        extract($data, EXTR_SKIP);
        require $file;
    }
}
