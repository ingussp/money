<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
$email = 'demo@money.local';
$password = getenv('MONEY_DEMO_PASSWORD') ?: '123';
$existing = Database::one('SELECT * FROM users WHERE email=?', [$email]);
if ($existing) {
    $resetPassword = in_array('--reset-password', $argv, true);
    if ($resetPassword) {
        Database::transaction(function () use ($existing, $password) {
            Database::query('UPDATE users SET password=?,auth_version=auth_version+1 WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $existing['id']]);
            Database::query('UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL', [$existing['id']]);
        });
    }
    $credentials = ['email' => $email, 'existing' => true, 'password_reset' => $resetPassword];
    if ($resetPassword || password_verify($password, $existing['password'])) $credentials['password'] = $password;
    echo json_encode($credentials, JSON_PRETTY_PRINT);
    exit;
}
Database::transaction(function () use ($email, $password) {
    $user = Database::insert('users', ['name' => 'Alex Morgan', 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT)]);
    $workspace = Database::insert('workspaces', ['name' => 'Forma Studio', 'currency' => 'EUR', 'registration_number' => 'DEMO-10248', 'address' => "24 Linden Street\nRiga, Latvia", 'monthly_budget' => 1500000]);
    Database::insert('memberships', ['workspace_id' => $workspace, 'user_id' => $user, 'role' => 'owner']);
    seed_categories($workspace);
    $categories = [];
    foreach (Database::all('SELECT * FROM categories WHERE workspace_id=?', [$workspace]) as $category) $categories[$category['name']] = $category['id'];
    $contacts = [];
    foreach (['Northstar Design', 'Linear Labs', 'Figma', 'Bolt Business', 'Studio Supply', 'Cloud Services'] as $name) {
        $contacts[$name] = Database::insert('contacts', ['workspace_id' => $workspace, 'name' => $name, 'email' => strtolower(str_replace(' ', '', $name)) . '@example.com', 'address' => 'Sample address', 'registration_number' => 'DEMO-' . count($contacts)]);
    }
    $first = new DateTimeImmutable('first day of this month');
    for ($i = 5; $i >= 0; --$i) {
        $date = $first->modify("-$i months");
        $specs = [
            ['income', 'Brand identity project', 1250000-$i*65000, 'Services', 'Northstar Design', 2],
            ['income', 'Monthly design partnership', 720000-$i*25000, 'Services', 'Linear Labs', 3],
            ['expense', 'Design software subscription', 14900, 'Software', 'Figma', 4],
            ['expense', 'Cloud infrastructure', 92500-$i*3500, 'Software', 'Cloud Services', 4],
            ['expense', 'Studio materials & equipment', 168000-$i*12000, 'Office', 'Studio Supply', 5],
            ['expense', 'Client meeting travel', 34800-$i*2500, 'Travel', 'Bolt Business', 6],
            ['expense', 'Freelance production support', 320000-$i*18000, 'Professional services', 'Northstar Design', 7],
        ];
        foreach ($specs as [$type,$description,$amount,$category,$contact,$day]) {
            $paidOn = $date->setDate((int)$date->format('Y'), (int)$date->format('m'), $day)->format('Y-m-d');
            Database::insert('entries', ['workspace_id' => $workspace, 'created_by' => $user, 'type' => $type, 'description' => $description, 'amount' => $amount, 'tax_amount' => (int) round($amount * 21 / 121), 'category_id' => $categories[$category], 'contact_id' => $contacts[$contact], 'entry_date' => $paidOn, 'paid_on' => $paidOn, 'status' => 'paid', 'reference' => ($type==='income'?'INV':'EXP').'-'.$date->format('ym').'-'.$day.'-'.substr((string)$amount,0,3), 'notes' => 'Sample data for the local demo workspace.']);
        }
    }
    foreach ([['Client lunch',8640,'Travel'],['Stock photography',12900,'Marketing'],['Office stationery',6850,'Office']] as [$description,$amount,$category]) {
        Database::insert('entries', ['workspace_id' => $workspace, 'created_by' => $user, 'type' => 'expense', 'description' => $description, 'amount' => $amount, 'category_id' => $categories[$category], 'entry_date' => date('Y-m-d'), 'status' => 'pending', 'reference' => 'EXP-'.strtoupper(bin2hex(random_bytes(2)))]);
    }
    Database::insert('audit_events', ['workspace_id' => $workspace, 'user_id' => $user, 'action' => 'workspace.created', 'description' => 'Sample workspace created for local preview']);
});
echo json_encode(['email' => $email, 'password' => $password, 'workspace' => 'Forma Studio'], JSON_PRETTY_PRINT);
