<?php $msg = get_flash('success') ?: get_flash('error'); ?>
<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('register')) ?> &middot; <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
</head>
<body class="hold-transition register-page">
<div class="register-box">
    <div class="register-logo"><b><?= e(t('app_name')) ?></b></div>
    <div class="card">
        <div class="card-body register-card-body">
            <p class="login-box-msg"><?= e(t('register')) ?></p>
            <?php if ($msg): ?>
                <div class="alert alert-<?= get_flash('error') ? 'danger' : 'success' ?>"><?= e($msg) ?></div>
            <?php endif; ?>
            <form action="<?= e(url('auth/register')) ?>" method="post">
                <?= csrf_field() ?>
                <div class="input-group mb-3">
                    <input type="text" name="name" class="form-control" placeholder="<?= e(t('name')) ?>" required>
                    <div class="input-group-append"><div class="input-group-text"><i class="fas fa-user"></i></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="<?= e(t('email')) ?>" required>
                    <div class="input-group-append"><div class="input-group-text"><i class="fas fa-envelope"></i></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="<?= e(t('password')) ?>" required>
                    <div class="input-group-append"><div class="input-group-text"><i class="fas fa-lock"></i></div></div>
                </div>
                <div class="input-group mb-3">
                    <select name="role" class="form-control">
                        <option value="employee"><?= e(t('employee')) ?></option>
                        <option value="accountant"><?= e(t('accountant')) ?></option>
                        <option value="admin"><?= e(t('admin')) ?></option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-8"><a href="<?= e(url('auth/login')) ?>"><?= e(t('login')) ?></a></div>
                    <div class="col-4"><button type="submit" class="btn btn-primary btn-block"><?= e(t('register')) ?></button></div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
