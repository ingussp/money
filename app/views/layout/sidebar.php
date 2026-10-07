<?php
/** @var string $activeRoute current route segment for menu highlighting. */
$activeRoute = $activeRoute ?? '';
$user = current_user();
$isAdmin = $user && in_array($user['role'], ['admin', 'accountant'], true);

function menu_item(string $route, string $icon, string $label, string $activeRoute): string
{
    $active = ($activeRoute === $route) ? ' active' : '';
    return '<li class="nav-item' . $active . '"><a href="' . e(url($route)) . '" class="nav-link"><i class="nav-icon fas ' . $icon . '"></i><p>' . e($label) . '</p></a></li>';
}
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= e(url('dashboard')) ?>" class="brand-link">
        <i class="fas fa-wallet brand-image" style="font-size:1.6rem;line-height:1.4;margin-left:.5rem;"></i>
        <span class="brand-text font-weight-light"><?= e(t('app_name')) ?></span>
    </a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle fa-2x" style="color:#c2c7d0;"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= e($user['name'] ?? '') ?></a>
                <small class="text-muted"><?= e(t($user['role'] ?? 'employee')) ?></small>
            </div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <?= menu_item('dashboard', 'fa-tachometer-alt', t('dashboard'), $activeRoute) ?>
                <?= menu_item('companies', 'fa-building', t('companies'), $activeRoute) ?>
                <?= menu_item('documents', 'fa-file-invoice', t('documents'), $activeRoute) ?>
                <?= menu_item('reports', 'fa-file-alt', t('reports'), $activeRoute) ?>
                <?= menu_item('cards', 'fa-credit-card', t('cards'), $activeRoute) ?>
                <?= menu_item('reconciliation', 'fa-exchange-alt', t('reconciliation'), $activeRoute) ?>
                <?= menu_item('einvoices', 'fa-envelope-open-text', t('e_invoices'), $activeRoute) ?>
                <?= menu_item('integrations', 'fa-plug', t('integrations'), $activeRoute) ?>
                <li class="nav-header"><?= e(t('settings')) ?></li>
                <?= menu_item('settings', 'fa-cog', t('account_settings'), $activeRoute) ?>
            </ul>
        </nav>
    </div>
</aside>
