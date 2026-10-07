<?php /** @var array $cards */ ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-credit-card mr-1"></i><?= e(t('cards')) ?></h3>
        <div class="card-tools">
            <a href="<?= e(url('cards/create')) ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?= e(t('new_card')) ?></a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead><tr><th><?= e(t('holder')) ?></th><th><?= e(t('company_name')) ?></th><th><?= e(t('last4')) ?></th><th><?= e(t('card_type')) ?></th><th><?= e(t('provider')) ?></th><th class="text-right"><?= e(t('limit')) ?></th><th class="text-right"><?= e(t('spend')) ?></th><th><?= e(t('status')) ?></th><th class="text-right"><?= e(t('actions')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($cards as $c): ?>
                <tr>
                    <td><?= e($c['holder']) ?></td>
                    <td><?= e($c['company_name'] ?? '-') ?></td>
                    <td><code>**** <?= e($c['last4']) ?></code></td>
                    <td><?= e(t($c['card_type'])) ?></td>
                    <td><?= e($c['provider']) ?></td>
                    <td class="text-right"><?= e(money($c['limit'], $c['currency'])) ?></td>
                    <td class="text-right"><?= e(money($c['spend'], 'EUR')) ?></td>
                    <td><?= status_badge($c['status']) ?></td>
                    <td class="text-right">
                        <a class="btn btn-xs btn-default" href="<?= e(url('cards/edit', ['id' => $c['id']])) ?>"><i class="fas fa-edit"></i></a>
                        <form action="<?= e(url('cards/delete')) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= e(t('confirm_delete')) ?>')">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$cards): ?><tr><td colspan="9" class="text-center"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
