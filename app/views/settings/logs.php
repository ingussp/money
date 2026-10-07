<?php /** @var array $logs */ ?>
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-history mr-1"></i><?= e(t('audit_log')) ?></h3></div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped">
            <thead><tr><th>#</th><th><?= e(t('user')) ?></th><th><?= e(t('action')) ?></th><th><?= e(t('date')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr><td><?= (int)$l['id'] ?></td><td><?= e($l['user_name']) ?></td><td><?= e($l['action']) ?></td><td><?= e($l['created_at']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="4" class="text-center"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
