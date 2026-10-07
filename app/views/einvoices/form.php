<?php /** @var array|null $invoice @var array $companies */ ?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><?= e($title) ?></h3></div>
    <form action="<?= e(url('einvoices/create')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label><?= e(t('invoice_number')) ?></label>
                    <input type="text" name="invoice_number" class="form-control" value="<?= e($invoice['invoice_number'] ?? '') ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label><?= e(t('vendor')) ?></label>
                    <input type="text" name="vendor" class="form-control" value="<?= e($invoice['vendor'] ?? '') ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label><?= e(t('buyer')) ?></label>
                    <input type="text" name="buyer" class="form-control" value="<?= e($invoice['buyer'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label><?= e(t('amount')) ?></label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="form-group col-md-2">
                    <label><?= e(t('currency')) ?></label>
                    <select name="currency" class="form-control">
                        <?php foreach (['EUR', 'USD', 'GBP'] as $cur): ?><option value="<?= $cur ?>"><?= $cur ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label><?= e(t('issue_date')) ?></label>
                    <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label><?= e(t('due_date')) ?></label>
                    <input type="date" name="due_date" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label><?= e(t('company_name')) ?></label>
                <select name="company_id" class="form-control">
                    <option value="0">-</option>
                    <?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
            <a href="<?= e(url('einvoices')) ?>" class="btn btn-default"><?= e(t('cancel')) ?></a>
        </div>
    </form>
</div>
