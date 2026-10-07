<?php
$user = current_user();
$locales = ['en' => 'English', 'lv' => 'Latviešu', 'et' => 'Eesti', 'fi' => 'Suomi', 'lt' => 'Lietuvių', 'pl' => 'Polski'];
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-globe"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <?php foreach ($locales as $code => $label): ?>
                    <a class="dropdown-item <?= current_locale() === $code ? 'active' : '' ?>" href="<?= e(url('settings/language', ['locale' => $code, 'back' => ($_GET['route'] ?? 'dashboard')])) ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= e(url('settings')) ?>">
                <i class="fas fa-user"></i> <?= e($user['name'] ?? '') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= e(url('auth/logout')) ?>">
                <i class="fas fa-sign-out-alt"></i> <?= e(t('logout')) ?>
            </a>
        </li>
    </ul>
</nav>
