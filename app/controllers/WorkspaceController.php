<?php
declare(strict_types=1);

final class WorkspaceController
{
    public static function handle(string $route): void
    {
        if ($route === 'switch-workspace') {
            post_only();
            $id = (int) required('workspace_id');
            if (!Database::one('SELECT role FROM memberships WHERE workspace_id=? AND user_id=?', [$id, current_user()['id']])) abort_request(403, 'Workspace access denied');
            $_SESSION['workspace_id'] = $id;
            redirect('dashboard');
        }
        if (in_array($route, ['contacts', 'activity', 'integrations'], true)) require_manager();
        if ($route === 'team') require_owner();
        $error = null;
        try {
            if (is_post()) {
                match ($route) {
                    'contacts' => self::saveContact(),
                    'settings' => self::saveSettings(),
                    'team' => self::saveTeam(),
                    default => abort_request(405, 'Method not allowed'),
                };
            }
        } catch (ValidationException $e) { $error = $e->getMessage(); http_response_code(422); }
        $data = ['title' => ucfirst($route), 'error' => $error];
        if ($route === 'contacts') {
            $data['contacts'] = Database::all('SELECT * FROM contacts WHERE workspace_id=? ORDER BY name', [workspace_id()]);
            $data['contact'] = id_param() ? scoped('contacts', id_param()) : null;
        }
        if ($route === 'settings') $data['categories'] = Database::all('SELECT * FROM categories WHERE workspace_id=? ORDER BY type,name', [workspace_id()]);
        if ($route === 'team') {
            $data['members'] = Database::all('SELECT u.id,u.name,u.email,m.role FROM memberships m JOIN users u ON u.id=m.user_id WHERE m.workspace_id=? ORDER BY m.role,u.name', [workspace_id()]);
            $data['invitations'] = Database::all('SELECT * FROM invitations WHERE workspace_id=? AND accepted_at IS NULL AND expires_at>UTC_TIMESTAMP() ORDER BY id DESC', [workspace_id()]);
        }
        if ($route === 'activity') $data['events'] = Database::all('SELECT a.*,u.name FROM audit_events a JOIN users u ON u.id=a.user_id WHERE a.workspace_id=? ORDER BY a.id DESC LIMIT 200', [workspace_id()]);
        render($route, $data);
    }

    private static function saveContact(): never
    {
        $id = id_param();
        if ($id) scoped('contacts', $id);
        $name = required('name');
        $email = input('email');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new ValidationException('Enter a valid email.');
        $registration = input('registration_number');
        $address = input('address');
        if (strlen($email) > 190 || mb_strlen($registration) > 100 || mb_strlen($address) > 2000) throw new ValidationException('Contact details are too long.');
        Database::transaction(function () use ($id, $name, $email, $registration, $address) {
            if ($id) Database::query('UPDATE contacts SET name=?,email=?,registration_number=?,address=? WHERE id=? AND workspace_id=?', [$name, $email, $registration, $address, $id, workspace_id()]);
            else Database::insert('contacts', ['workspace_id' => workspace_id(), 'name' => $name, 'email' => $email, 'registration_number' => $registration, 'address' => $address]);
            audit('contact.saved', $name);
        });
        flash('Contact saved.');
        redirect('contacts');
    }

    private static function saveSettings(): never
    {
        $action = input('action');
        if ($action === 'profile') {
            Database::query('UPDATE users SET name=? WHERE id=?', [required('name'), current_user()['id']]);
        } elseif ($action === 'password') {
            $currentPassword = $_POST['current_password'] ?? '';
            if (!is_string($currentPassword) || !password_verify($currentPassword, current_user()['password'])) throw new ValidationException('Your current password is incorrect.');
            $password = AuthController::password();
            Database::query('UPDATE users SET password=?,auth_version=auth_version+1 WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), current_user()['id']]);
            ++$_SESSION['auth_version'];
            session_regenerate_id(true);
        } elseif ($action === 'workspace') {
            require_owner();
            $name = required('name');
            $budget = cents(input('monthly_budget', '0'), true);
            $registration = input('registration_number');
            $address = input('address');
            if (mb_strlen($registration) > 100 || mb_strlen($address) > 2000) throw new ValidationException('Company details are too long.');
            Database::query('UPDATE workspaces SET name=?,registration_number=?,address=?,monthly_budget=? WHERE id=?', [$name, $registration, $address, $budget, workspace_id()]);
            audit('workspace.updated', $name);
        } elseif ($action === 'category') {
            require_manager();
            $type = input('type');
            $name = required('name', 100);
            if (!in_array($type, ['income', 'expense'], true)) throw new ValidationException('Choose a category type.');
            if (Database::one('SELECT id FROM categories WHERE workspace_id=? AND type=? AND name=?', [workspace_id(), $type, $name])) throw new ValidationException('This category already exists.');
            Database::insert('categories', ['workspace_id' => workspace_id(), 'type' => $type, 'name' => $name]);
            audit('category.created', $name);
        } elseif ($action === 'new-workspace') {
            $name = required('name');
            $currency = input('currency');
            if (!in_array($currency, ['EUR', 'USD', 'GBP'], true)) throw new ValidationException('Choose a supported currency.');
            $id = Database::transaction(function () use ($name, $currency) {
                $id = Database::insert('workspaces', ['name' => $name, 'currency' => $currency]);
                Database::insert('memberships', ['workspace_id' => $id, 'user_id' => current_user()['id'], 'role' => 'owner']);
                seed_categories($id);
                return $id;
            });
            $_SESSION['workspace_id'] = $id;
        } else throw new ValidationException('Unknown settings action.');
        flash('Settings saved.');
        redirect('settings');
    }

    private static function saveTeam(): never
    {
        $action = input('action');
        if ($action === 'invite') {
            $email = strtolower(required('email'));
            $role = input('role');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['manager', 'member'], true)) throw new ValidationException('Choose a valid email and role.');
            if (Database::one('SELECT m.user_id FROM memberships m JOIN users u ON u.id=m.user_id WHERE m.workspace_id=? AND u.email=?', [workspace_id(), $email])) throw new ValidationException('This person is already a member.');
            $token = bin2hex(random_bytes(32));
            Database::transaction(function () use ($email, $role, $token) {
                Database::query('DELETE FROM invitations WHERE workspace_id=? AND email=? AND accepted_at IS NULL', [workspace_id(), $email]);
                Database::insert('invitations', ['workspace_id' => workspace_id(), 'email' => $email, 'role' => $role, 'token_hash' => hash('sha256', $token), 'expires_at' => gmdate('Y-m-d H:i:s', time() + 604800)]);
                audit('team.invited', $email . ' as ' . $role);
            });
            $link = rtrim(config('url'), '/') . '/index.php?r=invite&token=' . $token;
            send_account_link($email, 'Join ' . workspace()['name'] . ' on Money', $link);
            $_SESSION['invite_link'] = $link;
        } elseif ($action === 'revoke') {
            $invite = scoped('invitations', id_param());
            Database::query('DELETE FROM invitations WHERE id=? AND workspace_id=? AND accepted_at IS NULL', [$invite['id'], workspace_id()]);
            audit('team.invitation_revoked', $invite['email']);
        } elseif (in_array($action, ['role', 'remove'], true)) {
            $id = (int) required('user_id');
            $member = Database::one('SELECT * FROM memberships WHERE workspace_id=? AND user_id=?', [workspace_id(), $id]);
            if (!$member || $member['role'] === 'owner') throw new ValidationException('The workspace owner cannot be changed here.');
            if ($action === 'remove') Database::query('DELETE FROM memberships WHERE workspace_id=? AND user_id=?', [workspace_id(), $id]);
            else {
                $role = input('role');
                if (!in_array($role, ['manager', 'member'], true)) throw new ValidationException('Choose a valid role.');
                Database::query('UPDATE memberships SET role=? WHERE workspace_id=? AND user_id=?', [$role, workspace_id(), $id]);
            }
            audit('team.' . $action, 'User #' . $id);
        } else throw new ValidationException('Unknown team action.');
        flash('Team updated.');
        redirect('team');
    }
}
