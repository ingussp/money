<?php /** @var array $transactions @var string $filter */ ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exchange-alt mr-1"></i><?= e(t('reconciliation')) ?></h3>
        <div class="card-tools">
            <div class="btn-group">
                <a href="<?= e(url('reconciliation')) ?>" class="btn btn-sm btn-<?= $filter === '' ? 'primary' : 'default' ?>"><?= e(t('all')) ?></a>
                <a href="<?= e(url('reconciliation', ['filter' => 'unmatched'])) ?>" class="btn btn-sm btn-<?= $filter === 'unmatched' ? 'primary' : 'default' ?>"><?= e(t('unmatched')) ?></a>
                <a href="<?= e(url('reconciliation', ['filter' => 'matched'])) ?>" class="btn btn-sm btn-<?= $filter === 'matched' ? 'primary' : 'default' ?>"><?= e(t('matched')) ?></a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead><tr><th><?= e(t('merchant')) ?></th><th><?= e(t('card')) ?></th><th><?= e(t('date')) ?></th><th class="text-right"><?= e(t('amount')) ?></th><th><?= e(t('matched_document')) ?></th><th class="text-right"><?= e(t('actions')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= e($t['merchant']) ?></td>
                    <td><?= e($t['holder']) ?> (****<?= e($t['last4'] ?? '') ?>)</td>
                    <td><?= e($t['booked_at']) ?></td>
                    <td class="text-right"><?= e(money($t['amount'], $t['currency'])) ?></td>
                    <td>
                        <?php if ($t['matched_document_id']): ?>
                            <span class="badge badge-success">#<?= (int)$t['matched_document_id'] ?> <?= e($t['matched_vendor']) ?></span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?= e(t('unmatched')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <?php if ($t['matched_document_id']): ?>
                            <form action="<?= e(url('reconciliation/unmatch')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="transaction_id" value="<?= (int)$t['id'] ?>">
                                <button class="btn btn-xs btn-warning"><?= e(t('unmatch')) ?></button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#matchModal" data-txn="<?= (int)$t['id'] ?>" data-merchant="<?= e($t['merchant']) ?>"><?= e(t('match')) ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$transactions): ?><tr><td colspan="6" class="text-center"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="matchModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= e(url('reconciliation/match')) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="transaction_id" id="match-txn">
            <div class="modal-header"><h5 class="modal-title"><?= e(t('match')) ?>: <span id="match-merchant"></span></h5></div>
            <div class="modal-body">
                <label><?= e(t('select_document')) ?></label>
                <input type="number" name="document_id" class="form-control" placeholder="<?= e(t('document_id')) ?>" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= e(t('cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= e(t('match')) ?></button>
            </div>
        </form>
    </div>
</div>

<script>
$('#matchModal').on('show.bs.modal', function (e) {
    var b = $(e.relatedTarget);
    $('#match-txn').val(b.data('txn'));
    $('#match-merchant').text(b.data('merchant'));
});
</script>
