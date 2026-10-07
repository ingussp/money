<?php
declare(strict_types=1);

class IntegrationController extends Controller
{
    public function index(): void
    {
        require_login();
        $integrations = db()->query('SELECT * FROM integrations WHERE is_visible = 1 ORDER BY name')->fetchAll();
        $this->view('integrations/index', [
            'title' => t('integrations'),
            'activeRoute' => 'integrations',
            'integrations' => $integrations,
        ]);
    }

    public function connect(): void
    {
        require_role('admin');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('UPDATE integrations SET connected = 1 WHERE id = ?')->execute([$id]);
        log_action('connect integration #' . $id);
        flash('success', 'Integration connected.');
        redirect('integrations');
    }

    public function disconnect(): void
    {
        require_role('admin');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('UPDATE integrations SET connected = 0 WHERE id = ?')->execute([$id]);
        log_action('disconnect integration #' . $id);
        flash('success', 'Integration disconnected.');
        redirect('integrations');
    }

    public function sync(): void
    {
        require_role('admin');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('UPDATE integrations SET last_sync_at = ? WHERE id = ?')->execute([date('c'), $id]);
        log_action('sync integration #' . $id);
        flash('success', 'Integration synchronised.');
        redirect('integrations');
    }
}
