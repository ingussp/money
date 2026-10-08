<?php
declare(strict_types=1);

final class AuthController
{
    public static function handle(string $route): void
    {
        if ($route === 'logout') {
            post_only();
            $_SESSION = [];
            session_destroy();
            redirect('login');
        }
        if (in_array($route, ['login', 'register'], true) && current_user()) redirect('dashboard');
        $error = null;
        $token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
        $invitation = null;
        if ($route === 'invite') {
            $invitation = Database::one('SELECT i.*,w.name AS workspace_name FROM invitations i JOIN workspaces w ON w.id=i.workspace_id WHERE token_hash=? AND accepted_at IS NULL AND expires_at>UTC_TIMESTAMP()', [hash('sha256', $token)]);
            if (!$invitation) abort_request(410, 'This invitation has expired or has already been used.');
            $_SESSION['pending_invite'] = $token;
        }
        try {
            if (is_post()) {
                match ($route) {
                    'login' => self::login(),
                    'register' => self::register(),
                    'forgot-password' => self::forgot(),
                    'reset-password' => self::reset($token),
                    'invite' => self::acceptInvitation($invitation),
                    default => null,
                };
            }
        } catch (ValidationException $e) {
            $error = $e->getMessage();
            http_response_code(422);
        }
        $titles = ['login' => 'Welcome back', 'register' => 'Create your workspace', 'forgot-password' => 'Reset your password', 'reset-password' => 'Choose a new password', 'invite' => 'Join your team'];
        render('auth', ['title' => $titles[$route], 'mode' => $route, 'error' => $error, 'token' => $token, 'invitation' => $invitation], 'public');
    }

    private static function rateLimit(string $purpose, string $email): string
    {
        $identifier = hash('sha256', $purpose . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        $count = Database::one('SELECT COUNT(*) AS n FROM login_attempts WHERE identifier=? AND created_at>DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)', [$identifier]);
        if ($count['n'] >= 15) throw new ValidationException('Too many attempts. Please try again in 15 minutes.');
        Database::insert('login_attempts', ['identifier' => $identifier]);
        return $identifier;
    }

    private static function email(): string
    {
        $email = strtolower(required('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new ValidationException('Enter a valid email address.');
        return $email;
    }

    public static function password(): string
    {
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) throw new ValidationException('Use a password between 12 and 72 characters.');
        if ($password !== ($_POST['password_confirmation'] ?? '')) throw new ValidationException('The passwords do not match.');
        return $password;
    }

    private static function login(): never
    {
        $email = self::email();
        $identifier = self::rateLimit('login', $email);
        $user = Database::one('SELECT * FROM users WHERE email=?', [$email]);
        $password = $_POST['password'] ?? '';
        $valid = is_string($password) && password_verify($password, $user['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (!$user || !$valid) throw new ValidationException('The email or password is incorrect.');
        Database::query('DELETE FROM login_attempts WHERE identifier=?', [$identifier]);
        $invite = $_SESSION['pending_invite'] ?? null;
        login_user($user);
        redirect($invite ? 'invite' : 'dashboard', $invite ? ['token' => $invite] : []);
    }

    private static function register(): never
    {
        $email = self::email();
        self::rateLimit('register', $email);
        $name = required('name');
        $company = required('company');
        $password = self::password();
        if (Database::one('SELECT id FROM users WHERE email=?', [$email])) throw new ValidationException('An account with this email already exists. Please sign in.');
        $currency = input('currency', 'EUR');
        if (!in_array($currency, ['EUR', 'USD', 'GBP'], true)) throw new ValidationException('Select a supported currency.');
        $id = Database::transaction(function () use ($name, $email, $password, $company, $currency) {
            $id = Database::insert('users', ['name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT)]);
            $workspaceId = Database::insert('workspaces', ['name' => $company, 'currency' => $currency]);
            Database::insert('memberships', ['workspace_id' => $workspaceId, 'user_id' => $id, 'role' => 'owner']);
            seed_categories($workspaceId);
            return $id;
        });
        login_user(Database::one('SELECT * FROM users WHERE id=?', [$id]));
        flash('Your workspace is ready. Start with your first income or expense.');
        redirect('dashboard');
    }

    private static function forgot(): never
    {
        $email = self::email();
        self::rateLimit('reset', $email);
        $user = Database::one('SELECT * FROM users WHERE email=?', [$email]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            Database::query('UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL', [$user['id']]);
            Database::insert('password_resets', ['user_id' => $user['id'], 'token_hash' => hash('sha256', $token), 'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600)]);
            send_account_link($email, 'Reset your Money password', rtrim(config('url'), '/') . '/index.php?r=reset-password&token=' . $token);
        }
        flash('If an account exists, a password reset link has been queued for delivery.');
        redirect('forgot-password');
    }

    private static function reset(string $token): never
    {
        $password = self::password();
        Database::transaction(function () use ($token, $password) {
            $reset = Database::one('SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>UTC_TIMESTAMP() FOR UPDATE', [hash('sha256', $token)]);
            if (!$reset) throw new ValidationException('This reset link has expired or has already been used.');
            Database::query('UPDATE users SET password=?,auth_version=auth_version+1 WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            Database::query('UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE user_id=?', [$reset['user_id']]);
        });
        $_SESSION = [];
        session_regenerate_id(true);
        flash('Your password has been updated. You can now sign in.');
        redirect('login');
    }

    private static function acceptInvitation(array $invitation): never
    {
        $user = current_user();
        if ($user && $user['email'] !== $invitation['email']) throw new ValidationException('Sign out and use the email address on this invitation.');
        if (!$user && Database::one('SELECT id FROM users WHERE email=?', [$invitation['email']])) throw new ValidationException('You already have an account. Sign in to accept this invitation.');
        $password = !$user ? self::password() : null;
        $name = !$user ? required('name') : '';
        $user = Database::transaction(function () use ($invitation, $user, $password, $name) {
            $fresh = Database::one('SELECT * FROM invitations WHERE id=? AND accepted_at IS NULL AND expires_at>UTC_TIMESTAMP() FOR UPDATE', [$invitation['id']]);
            if (!$fresh) throw new ValidationException('This invitation is no longer available.');
            if (!$user) {
                $id = Database::insert('users', ['name' => $name, 'email' => $invitation['email'], 'password' => password_hash($password, PASSWORD_DEFAULT)]);
                $user = Database::one('SELECT * FROM users WHERE id=?', [$id]);
            }
            if (!Database::one('SELECT role FROM memberships WHERE workspace_id=? AND user_id=?', [$invitation['workspace_id'], $user['id']])) {
                Database::insert('memberships', ['workspace_id' => $invitation['workspace_id'], 'user_id' => $user['id'], 'role' => $invitation['role']]);
            }
            Database::query('UPDATE invitations SET accepted_at=UTC_TIMESTAMP() WHERE id=?', [$invitation['id']]);
            return $user;
        });
        login_user($user);
        $_SESSION['workspace_id'] = $invitation['workspace_id'];
        flash('You have joined ' . $invitation['workspace_name'] . '.');
        redirect('dashboard');
    }
}
