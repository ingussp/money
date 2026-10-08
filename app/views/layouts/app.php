<?php $active = $_GET['r'] ?? 'dashboard'; $company = workspace(); $user = current_user(); $companies = Database::all('SELECT w.id,w.name FROM workspaces w JOIN memberships m ON m.workspace_id=w.id WHERE m.user_id=? ORDER BY w.name', [$user['id']]); ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($title) ?> | Money</title><link rel="stylesheet" href="<?= e(asset('app.css')) ?>"><script src="<?= e(asset('icons.js')) ?>" defer></script><script src="<?= e(asset('app.js')) ?>" defer></script></head>
<body class="app-body">
<a class="skip-link" href="#main">Skip to content</a>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url('dashboard')) ?>"><span class="brand-mark">m</span> money<span class="brand-dot">.</span></a>
    <form class="workspace-switch" action="<?= e(url('switch-workspace')) ?>" method="post"><?= csrf_field() ?><label for="workspace-switch">WORKSPACE</label><select id="workspace-switch" name="workspace_id" data-auto-submit aria-label="Switch workspace"><?php foreach ($companies as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], $company['id']) ?>><?= e($c['name']) ?></option><?php endforeach ?></select><noscript><button type="submit">Switch</button></noscript></form>
    <nav aria-label="Main navigation">
    <?php $nav = [
        ['dashboard','layout-dashboard','Overview',true], ['income','arrow-down-circle','Income',can_manage()], ['expenses','wallet','Expenses',true], ['documents','file-text','Documents',true], ['approvals','circle-check','Approvals',can_manage()], ['invoices','file-text','Invoices',can_manage()], ['reconciliation','refresh-cw','Reconciliation',can_manage()], ['reports','chart-no-axes-combined','Reports',can_manage()], ['contacts','building-2','Contacts',can_manage()], ['team','users','Team',$company['role']==='owner'], ['integrations','plug','Integrations',can_manage()]
    ]; foreach ($nav as [$route,$symbol,$label,$show]): if (!$show) continue; $isActive = $active === $route || ($active==='entry' && $route===(($type??'expense')==='income'?'income':'expenses')) || ($active==='invoice' && $route==='invoices'); ?>
    <a href="<?= e(url($route)) ?>" class="nav-item<?= $isActive ? ' active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= icon($symbol) ?><span><?= e($label) ?></span></a>
    <?php endforeach ?>
    </nav>
    <div class="sidebar-bottom"><?php if(can_manage()): ?><a class="nav-item<?= $active==='activity'?' active':'' ?>" href="<?= e(url('activity')) ?>"><?= icon('clock') ?>Activity</a><?php endif ?><a class="nav-item<?= $active==='settings'?' active':'' ?>" href="<?= e(url('settings')) ?>"><?= icon('settings') ?>Settings</a><div class="account"><span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'],0,1))) ?></span><div><strong><?= e($user['name']) ?></strong><small><?= e(ucfirst($company['role'])) ?></small></div><form action="<?= e(url('logout')) ?>" method="post"><?= csrf_field() ?><button class="icon-button" aria-label="Sign out" title="Sign out"><?= icon('arrow-right') ?></button></form></div></div>
</aside>
<button class="sidebar-scrim" aria-label="Close navigation" data-close-menu hidden></button>
<div class="app-shell">
<header class="topbar"><button class="icon-button mobile-menu" data-menu-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Open navigation"><?= icon('menu') ?></button><div class="breadcrumb"><span><?= e($company['name']) ?></span><?= icon('chevron-right') ?><strong><?= e($title) ?></strong></div><div class="topbar-tools"><span class="currency-label"><?= e($company['currency']) ?></span><a class="icon-button" href="<?= e(url('documents')) ?>" title="Documents" aria-label="Documents"><?= icon('file-text') ?></a><a class="avatar" href="<?= e(url('settings')) ?>" title="Your profile"><?= e(mb_strtoupper(mb_substr($user['name'],0,1))) ?></a></div></header>
<main id="main" class="main-content">
<?php require ROOT . '/app/views/partials/flash.php'; ?>
<?= $content ?>
</main><footer class="app-footer"><span>Money / Your finances, in focus.</span><span><?= e($company['currency']) ?> &middot; <?= date('Y') ?></span></footer>
</div></body></html>
