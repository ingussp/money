<?php /** @var array $invoice */ ?>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= e($invoice['invoice_number']) ?></h3>
                <div class="card-tools">
                    <?php if ($invoice['status'] === 'draft'): ?>
                        <form action="<?= e(url('einvoices/send')) ?>" method="post" class="d-inline">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$invoice['id'] ?>">
                            <button class="btn btn-sm btn-primary"><?= e(t('send')) ?></button>
                        </form>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-default" href="<?= e(url('einvoices/download', ['id' => $invoice['id']])) ?>"><i class="fas fa-download"></i> XML</a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4"><?= e(t('vendor')) ?></dt><dd class="col-sm-8"><?= e($invoice['vendor']) ?></dd>
                    <dt class="col-sm-4"><?= e(t('buyer')) ?></dt><dd class="col-sm-8"><?= e($invoice['buyer']) ?></dd>
                    <dt class="col-sm-4"><?= e(t('amount')) ?></dt><dd class="col-sm-8"><?= e(money($invoice['amount'], $invoice['currency'])) ?></dd>
                    <dt class="col-sm-4"><?= e(t('issue_date')) ?></dt><dd class="col-sm-8"><?= e($invoice['issue_date']) ?></dd>
                    <dt class="col-sm-4"><?= e(t('due_date')) ?></dt><dd class="col-sm-8"><?= e($invoice['due_date']) ?></dd>
                    <dt class="col-sm-4"><?= e(t('status')) ?></dt><dd class="col-sm-8"><?= status_badge($invoice['status']) ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">XML</h3></div>
            <div class="card-body"><pre class="bg-light p-3" style="max-height:400px;overflow:auto"><?= e($invoice['xml']) ?></pre></div>
        </div>
    </div>
</div>
