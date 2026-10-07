<?php /** @var array $users */ ?>
<div class="row">
    <div class="col-lg-5">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><?= e(t('new_user')) ?></h3></div>
            <form action="<?= e(url('settings/users')) ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="form-group"><label><?= e(t('name')) ?></label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label><?= e(t('email')) ?></label><input type="email" name="email" class="form-control" required></div>
                    <div class="form-group"><label><?= e(t('password')) ?></label><input type="password" name="password" class="form-control" required></div>
                    <div class="form-group">
                        <label><?= e(t('role')) ?></label>
                        <select name="role" class="form-control">
                            <option value="employee"><?= e(t('employee')) ?></option>
                            <option value="accountant"><?= e(t('accountant')) ?></option>
                            <option value="admin"><?= e(t('admin')) ?></option>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary"><?= e(t('create')) ?></button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= e(t('users')) ?></h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>#</th><th><?= e(t('name')) ?></th><th><?= e(t('email')) ?></th><th><?= e(t('role')) ?></th><th><?= e(t('language')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr><td><?= (int)$u['id'] ?></td><td><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td><?= status_badge($u['role']) ?></td><td><?= e($u['locale']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
