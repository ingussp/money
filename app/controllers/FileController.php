<?php
declare(strict_types=1);

class FileController extends Controller
{
    public function get(): void
    {
        require_login();
        $name = basename($_GET['name'] ?? '');
        $path = uploads_dir() . DIRECTORY_SEPARATOR . $name;
        if ($name === '' || !is_file($path)) {
            http_response_code(404);
            exit('File not found.');
        }
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . $name . '"');
        readfile($path);
        exit;
    }

    public function delete(): void
    {
        require_login();
        verify_csrf();
        $name = basename($_POST['name'] ?? '');
        $path = uploads_dir() . DIRECTORY_SEPARATOR . $name;
        if ($name !== '' && is_file($path)) {
            unlink($path);
        }
        log_action('delete file ' . $name);
        flash('success', 'File deleted.');
        redirect($_POST['redirect'] ?? 'dashboard');
    }
}
