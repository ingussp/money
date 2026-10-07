<?php /** @var array|null $company */ ?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><?= e($title) ?></h3></div>
    <form action="<?= e($company ? url('companies/edit', ['id' => $company['id']]) : url('companies/create')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-group">
                <label><?= e(t('company_name')) ?></label>
                <input type="text" name="name" class="form-control" value="<?= e($company['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label><?= e(t('reg_number')) ?></label>
                <input type="text" name="reg_number" class="form-control" value="<?= e($company['reg_number'] ?? '') ?>">
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
            <a href="<?= e(url('companies')) ?>" class="btn btn-default"><?= e(t('cancel')) ?></a>
        </div>
    </form>
</div>
