<?php /** @var array $invoices */ ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i><?= e(t('einvoices')) ?></h3>
        <div class="card-tools">
            <a href="<?= e(url('einvoices/create')) ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?= e(t('new_einvoice')) ?></a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead><tr><th><?= e(t('invoice_number')) ?></th><th><?= e(t('vendor')) ?></th><th><?= e(t('buyer')) ?></th><th class="text-right"><?= e(t('amount')) ?></th><th><?= e(t('issue_date')) ?></th><th><?= e(t('due_date')) ?></th><th><?= e(t('status')) ?></th><th class="text-right"><?= e(t('actions')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $i): ?>
                <tr>
                    <td><a href="<?= e(url('einvoices/show', ['id' => $i['id']])) ?>"><?= e($i['invoice_number']) ?></a></td>
                    <td><?= e($i['vendor']) ?></td>
                    <td><?= e($i['buyer']) ?></td>
                    <td class="text-right"><?= e(money($i['amount'], $i['currency'])) ?></td>
                    <td><?= e($i['issue_date']) ?></td>
                    <td><?= e($i['due_date']) ?></td>
                    <td><?= status_badge($i['status']) ?></td>
                    <td class="text-right">
                        <a class="btn btn-xs btn-default" href="<?= e(url('einvoices/download', ['id' => $i['id']])) ?>"><i class="fas fa-download"></i></a>
                        <?php if ($i['status'] === 'draft'): ?>
                            <form action="<?= e(url('einvoices/send')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                <button class="btn btn-xs btn-primary"><?= e(t('send')) ?></button>
                            </form>
                        <?php elseif ($i['status'] === 'sent'): ?>
                            <form action="<?= e(url('einvoices/mark-paid')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                <button class="btn btn-xs btn-success"><?= e(t('mark_paid')) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$invoices): ?><tr><td colspan="8" class="text-center"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
