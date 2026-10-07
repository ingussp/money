<?php /** @var array|null $card @var array $companies */ ?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><?= e($title) ?></h3></div>
    <form action="<?= e($card ? url('cards/edit', ['id' => $card['id']]) : url('cards/create')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label><?= e(t('holder')) ?></label>
                    <input type="text" name="holder" class="form-control" value="<?= e($card['holder'] ?? '') ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label><?= e(t('company_name')) ?></label>
                    <select name="company_id" class="form-control">
                        <option value="0">-</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)($card['company_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label><?= e(t('card_type')) ?></label>
                    <select name="card_type" class="form-control">
                        <option value="virtual" <?= ($card['card_type'] ?? 'virtual') === 'virtual' ? 'selected' : '' ?>><?= e(t('virtual')) ?></option>
                        <option value="physical" <?= ($card['card_type'] ?? '') === 'physical' ? 'selected' : '' ?>><?= e(t('physical')) ?></option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label><?= e(t('provider')) ?></label>
                    <input type="text" name="provider" class="form-control" value="<?= e($card['provider'] ?? 'CardNet') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label><?= e(t('status')) ?></label>
                    <select name="status" class="form-control">
                        <?php foreach (['active', 'frozen', 'blocked', 'terminated'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($card['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= e(t($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label><?= e(t('limit')) ?></label>
                    <input type="number" step="0.01" name="limit" class="form-control" value="<?= e($card['limit'] ?? '') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label><?= e(t('currency')) ?></label>
                    <select name="currency" class="form-control">
                        <?php foreach (['EUR', 'USD', 'GBP'] as $cur): ?>
                            <option value="<?= $cur ?>" <?= ($card['currency'] ?? 'EUR') === $cur ? 'selected' : '' ?>><?= $cur ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
            <a href="<?= e(url('cards')) ?>" class="btn btn-default"><?= e(t('cancel')) ?></a>
        </div>
    </form>
</div>
