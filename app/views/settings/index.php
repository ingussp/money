<?php /** @var array $user @var string $homeCurrency @var string $defaultVat */ ?>
<div class="row">
    <div class="col-lg-6">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><?= e(t('profile')) ?></h3></div>
            <form action="<?= e(url('settings')) ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="form-group"><label><?= e(t('name')) ?></label><input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>"></div>
                    <div class="form-group"><label><?= e(t('email')) ?></label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>"></div>
                    <div class="form-group"><label><?= e(t('password')) ?></label><input type="password" name="password" class="form-control" placeholder="<?= e(t('password_hint')) ?>"></div>
                    <div class="form-group">
                        <label><?= e(t('language')) ?></label>
                        <select name="locale" class="form-control">
                            <?php foreach (AVAILABLE_LOCALES as $l): ?><option value="<?= $l ?>" <?= $user['locale'] === $l ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary"><?= e(t('save')) ?></button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><?= e(t('general')) ?></h3></div>
            <form action="<?= e(url('settings')) ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="form-group">
                        <label><?= e(t('home_currency')) ?></label>
                        <select name="home_currency" class="form-control">
                            <?php foreach (['EUR', 'USD', 'GBP'] as $c): ?><option value="<?= $c ?>" <?= $homeCurrency === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= e(t('default_vat')) ?> (%)</label>
                        <input type="number" step="0.1" name="default_vat" class="form-control" value="<?= e($defaultVat) ?>">
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary"><?= e(t('save')) ?></button></div>
            </form>
        </div>
    </div>
</div>
