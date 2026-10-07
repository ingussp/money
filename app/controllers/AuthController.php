<?php
declare(strict_types=1);

class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $email = trim($_POST['email'] ?? '');
            $password = (string)($_POST['password'] ?? '');

            $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['locale'] = $user['locale'] ?: 'en';
                log_action('login');
                redirect('dashboard');
            }

            flash('error', 'Invalid email or password.');
            redirect('auth/login');
        }

        if (is_logged_in()) {
            redirect('dashboard');
        }
        $this->render('auth/login');
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        redirect('auth/login');
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $role = in_array($_POST['role'] ?? '', ['admin', 'accountant', 'employee'], true) ? $_POST['role'] : 'employee';

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                flash('error', 'Please provide a valid name, email and a password of at least 6 characters.');
                redirect('auth/register');
            }

            try {
                $stmt = db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            } catch (PDOException $e) {
                flash('error', 'An account with this email already exists.');
                redirect('auth/register');
            }

            flash('success', 'Account created. You can now log in.');
            redirect('auth/login');
        }

        $this->render('auth/register');
    }
}
