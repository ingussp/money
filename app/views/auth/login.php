<?php
/** Standalone login page. */
$msg = get_flash('success') ?: get_flash('error');
?>
<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('login')) ?> &middot; <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo"><b><?= e(t('app_name')) ?></b></div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg"><?= e(t('login')) ?></p>
            <?php if ($msg): ?>
                <div class="alert alert-<?= get_flash('error') ? 'danger' : 'success' ?>"><?= e($msg) ?></div>
            <?php endif; ?>
            <form action="<?= e(url('auth/login')) ?>" method="post">
                <?= csrf_field() ?>
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="<?= e(t('email')) ?>" required>
                    <div class="input-group-append"><div class="input-group-text"><i class="fas fa-envelope"></i></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="<?= e(t('password')) ?>" required>
                    <div class="input-group-append"><div class="input-group-text"><i class="fas fa-lock"></i></div></div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <a href="<?= e(url('auth/register')) ?>"><?= e(t('register')) ?></a>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block"><?= e(t('login')) ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <p class="mb-1 text-center text-muted"><small>Demo: admin@money.local / admin123</small></p>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
