<?php
/** @var array $document @var array $lines */
$d = $document;
$canApprove = current_user() && in_array(current_user()['role'], ['admin', 'accountant'], true);
?>
<div class="row">
    <div class="col-lg-7">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i><?= e($d['vendor']) ?></h3>
                <div class="card-tools">
                    <?php if ($d['status'] !== 'rejected' && $canApprove): ?>
                        <form action="<?= e(url('documents/approve')) ?>" method="post" class="d-inline">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                            <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> <?= e(t('approve')) ?></button>
                        </form>
                        <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal"><i class="fas fa-times"></i> <?= e(t('reject')) ?></button>
                    <?php endif; ?>
                    <form action="<?= e(url('documents/digitize')) ?>" method="post" class="d-inline">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                        <button class="btn btn-sm btn-default"><i class="fas fa-robot"></i> <?= e(t('digitize')) ?></button>
                    </form>
                    <a class="btn btn-sm btn-default" href="<?= e(url('documents/edit', ['id' => $d['id']])) ?>"><i class="fas fa-edit"></i> <?= e(t('edit')) ?></a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3"><?= e(t('type')) ?></dt><dd class="col-sm-9"><?= e(t($d['type'])) ?></dd>
                    <dt class="col-sm-3"><?= e(t('doc_number')) ?></dt><dd class="col-sm-9"><?= e($d['doc_number'] ?? '-') ?></dd>
                    <dt class="col-sm-3"><?= e(t('date')) ?></dt><dd class="col-sm-9"><?= e($d['date']) ?></dd>
                    <dt class="col-sm-3"><?= e(t('company_name')) ?></dt><dd class="col-sm-9"><?= e($d['company_name'] ?? '-') ?></dd>
                    <dt class="col-sm-3"><?= e(t('category')) ?></dt><dd class="col-sm-9"><?= e($d['category_name'] ?? '-') ?></dd>
                    <dt class="col-sm-3"><?= e(t('amount')) ?></dt><dd class="col-sm-9"><?= e(money($d['amount'], $d['currency'])) ?></dd>
                    <dt class="col-sm-3"><?= e(t('amount_home')) ?></dt><dd class="col-sm-9"><?= e(money($d['amount_home'], 'EUR')) ?></dd>
                    <dt class="col-sm-3"><?= e(t('tax')) ?></dt><dd class="col-sm-9"><?= e(money($d['tax'], $d['currency'])) ?></dd>
                    <dt class="col-sm-3"><?= e(t('status')) ?></dt><dd class="col-sm-9"><?= status_badge($d['status']) ?></dd>
                    <dt class="col-sm-3"><?= e(t('digitized')) ?></dt><dd class="col-sm-9"><?= digitized_badge($d['digitized'], (int)$d['is_verified']) ?></dd>
                    <?php if ($d['duplicate_of']): ?>
                        <dt class="col-sm-3"><?= e(t('duplicate')) ?></dt><dd class="col-sm-9"><span class="badge badge-danger">#<?= (int)$d['duplicate_of'] ?> - <?= e($d['duplicate_vendor']) ?></span></dd>
                    <?php endif; ?>
                    <?php if (!empty($d['file_name'])): ?>
                        <dt class="col-sm-3"><?= e(t('file')) ?></dt><dd class="col-sm-9"><a href="<?= e(url('file/get', ['name' => $d['file_name']])) ?>" target="_blank"><?= e($d['file_name']) ?></a></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= e(t('line_items')) ?></h3></div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead><tr><th><?= e(t('description')) ?></th><th class="text-right"><?= e(t('quantity')) ?></th><th class="text-right"><?= e(t('unit_price')) ?></th><th class="text-right"><?= e(t('amount')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($lines as $l): ?>
                        <tr>
                            <td><?= e($l['description']) ?></td>
                            <td class="text-right"><?= e((string)$l['quantity']) ?></td>
                            <td class="text-right"><?= e(money($l['unit_price'], $d['currency'])) ?></td>
                            <td class="text-right"><?= e(money($l['amount'], $d['currency'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lines): ?><tr><td colspan="4" class="text-center text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= e(url('documents/reject')) ?>" method="post">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
            <div class="modal-header"><h5 class="modal-title"><?= e(t('reject')) ?></h5></div>
            <div class="modal-body">
                <textarea name="reason" class="form-control" placeholder="<?= e(t('reject_reason')) ?>"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= e(t('cancel')) ?></button>
                <button type="submit" class="btn btn-danger"><?= e(t('reject')) ?></button>
            </div>
        </form>
    </div>
</div>
