<?php /** @var array $integrations */ ?>
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-plug mr-1"></i><?= e(t('integrations')) ?></h3></div>
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead><tr><th><?= e(t('name')) ?></th><th><?= e(t('type')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('last_sync')) ?></th><th class="text-right"><?= e(t('actions')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($integrations as $i): ?>
                <tr>
                    <td><i class="fas fa-puzzle-piece mr-1 text-info"></i><?= e($i['name']) ?></td>
                    <td><?= e(t($i['type'])) ?></td>
                    <td><?= $i['connected'] ? '<span class="badge badge-success">' . e(t('connected')) . '</span>' : '<span class="badge badge-secondary">' . e(t('disconnected')) . '</span>' ?></td>
                    <td><?= e($i['last_sync_at'] ?: '-') ?></td>
                    <td class="text-right">
                        <?php if ($i['connected']): ?>
                            <form action="<?= e(url('integrations/sync')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                <button class="btn btn-xs btn-default"><i class="fas fa-sync"></i> <?= e(t('sync')) ?></button>
                            </form>
                            <form action="<?= e(url('integrations/disconnect')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                <button class="btn btn-xs btn-danger"><?= e(t('disconnect')) ?></button>
                            </form>
                        <?php else: ?>
                            <form action="<?= e(url('integrations/connect')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                <button class="btn btn-xs btn-success"><?= e(t('connect')) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
