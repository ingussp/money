<?php
declare(strict_types=1);

class SettingsController extends Controller
{
    public function index(): void
    {
        require_login();
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $user = current_user();
            $name = trim($_POST['name'] ?? $user['name']);
            $email = trim($_POST['email'] ?? $user['email']);
            $locale = trim($_POST['locale'] ?? $user['locale']);
            $pdo->prepare('UPDATE users SET name = ?, email = ?, locale = ? WHERE id = ?')
                ->execute([$name, $email, $locale, $user['id']]);

            if (!empty($_POST['password'])) {
                $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                    ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $user['id']]);
            }

            foreach (['home_currency', 'default_vat'] as $key) {
                if (isset($_POST[$key])) {
                    set_setting($key, (string)$_POST[$key]);
                }
            }

            $_SESSION['locale'] = $locale;
            log_action('update profile');
            flash('success', 'Settings saved.');
            redirect('settings');
        }

        $user = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $user->execute([$_SESSION['user_id']]);
        $user = $user->fetch();

        $this->view('settings/index', [
            'title' => t('settings'),
            'activeRoute' => 'settings',
            'user' => $user,
            'homeCurrency' => get_setting('home_currency', 'EUR'),
            'defaultVat' => get_setting('default_vat', '21'),
        ]);
    }

    public function users(): void
    {
        require_role('admin');
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $role = in_array($_POST['role'] ?? '', ['admin', 'accountant', 'employee'], true) ? $_POST['role'] : 'employee';
            if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 6) {
                $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash('success', 'User created.');
            } else {
                flash('error', 'Invalid user data.');
            }
            redirect('settings/users');
        }

        $users = $pdo->query('SELECT id, name, email, role, locale, created_at FROM users ORDER BY id')->fetchAll();
        $this->view('settings/users', [
            'title' => t('users'),
            'activeRoute' => 'settings',
            'users' => $users,
        ]);
    }

    public function logs(): void
    {
        require_role('admin');
        $logs = db()->query(
            'SELECT l.*, u.name AS user_name FROM audit_log l
             LEFT JOIN users u ON u.id = l.user_id
             ORDER BY l.id DESC LIMIT 200'
        )->fetchAll();
        $this->view('settings/logs', [
            'title' => t('audit_log'),
            'activeRoute' => 'settings',
            'logs' => $logs,
        ]);
    }

    public function language(): void
    {
        $locale = $_GET['locale'] ?? $_POST['locale'] ?? DEFAULT_LOCALE;
        if (!in_array($locale, AVAILABLE_LOCALES, true)) {
            $locale = DEFAULT_LOCALE;
        }
        $_SESSION['locale'] = $locale;
        if (is_logged_in()) {
            db()->prepare('UPDATE users SET locale = ? WHERE id = ?')->execute([$locale, $_SESSION['user_id']]);
        }
        $back = $_GET['back'] ?? $_POST['redirect'] ?? 'dashboard';
        redirect($back);
    }
}
