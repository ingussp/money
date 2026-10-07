<?php
/** @var array $stats @var array $recentDocs @var array $recentTxns */
function stat_card(string $icon, string $bg, string $label, string $value): string
{
    return '<div class="col-lg-3 col-6">
        <div class="small-box ' . $bg . '">
            <div class="inner"><h3>' . $value . '</h3><p>' . e($label) . '</p></div>
            <div class="icon"><i class="fas ' . $icon . '"></i></div>
        </div>
    </div>';
}
?>
<div class="row">
    <?= stat_card('fa-file-invoice', 'bg-info', t('stat_documents'), (string)$stats['documents']) ?>
    <?= stat_card('fa-bullseye', 'bg-success', t('stat_accuracy'), $stats['accuracy'] . '%') ?>
    <?= stat_card('fa-building', 'bg-warning', t('stat_companies'), (string)$stats['companies']) ?>
    <?= stat_card('fa-plug', 'bg-primary', t('stat_integrations'), (string)$stats['integrations']) ?>
    <?= stat_card('fa-credit-card', 'bg-danger', t('stat_cards'), (string)$stats['cards']) ?>
    <?= stat_card('fa-exchange-alt', 'bg-secondary', t('stat_transactions'), (string)$stats['transactions']) ?>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i><?= e(t('documents')) ?></h3>
                <div class="card-tools">
                    <a href="<?= e(url('documents/create')) ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?= e(t('documents_new')) ?></a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th><?= e(t('vendor')) ?></th><th><?= e(t('category')) ?></th><th><?= e(t('date')) ?></th><th class="text-right"><?= e(t('amount')) ?></th><th><?= e(t('status')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentDocs as $d): ?>
                        <tr>
                            <td><?= e($d['vendor']) ?></td>
                            <td><?= e($d['category_name'] ?? '-') ?></td>
                            <td><?= e($d['date']) ?></td>
                            <td class="text-right"><?= e(money($d['amount'], $d['currency'])) ?></td>
                            <td><span class="badge badge-<?= $d['status'] === 'approved' ? 'success' : ($d['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= e(t($d['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentDocs): ?><tr><td colspan="5" class="text-center"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-1"></i><?= e(t('reconciliation')) ?></h3>
                <div class="card-tools">
                    <a href="<?= e(url('reconciliation')) ?>" class="btn btn-sm btn-default"><?= e(t('view')) ?></a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th><?= e(t('merchant')) ?></th><th class="text-right"><?= e(t('amount')) ?></th><th><?= e(t('status')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentTxns as $x): ?>
                        <tr>
                            <td><?= e($x['merchant']) ?></td>
                            <td class="text-right"><?= e(money($x['amount'], $x['currency'])) ?></td>
                            <td><?= $x['matched_document_id'] ? '<span class="badge badge-success">' . e(t('matched')) . '</span>' : '<span class="badge badge-warning">' . e(t('unmatched')) . '</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentTxns): ?><tr><td colspan="3" class="text-center"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
