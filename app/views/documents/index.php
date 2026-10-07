<?php
/** @var array $documents @var array $categories @var string $search @var int $category @var string $status */
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i><?= e(t('documents')) ?></h3>
        <div class="card-tools">
            <a href="<?= e(url('documents/create')) ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?= e(t('documents_new')) ?></a>
        </div>
    </div>
    <div class="card-body">
        <form method="get" action="<?= e(url('documents')) ?>" class="form-row">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="<?= e(t('search')) ?>" value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-control">
                    <option value="0"><?= e(t('all_categories')) ?></option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $category === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value=""><?= e(t('all_statuses')) ?></option>
                    <?php foreach (['draft', 'submitted', 'pending', 'approved', 'rejected', 'paid'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(t($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-default btn-block"><i class="fas fa-search"></i> <?= e(t('filter')) ?></button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead><tr>
                <th><?= e(t('vendor')) ?></th><th><?= e(t('type')) ?></th><th><?= e(t('doc_number')) ?></th>
                <th><?= e(t('company_name')) ?></th><th><?= e(t('date')) ?></th>
                <th class="text-right"><?= e(t('amount')) ?></th><th><?= e(t('category')) ?></th>
                <th><?= e(t('status')) ?></th><th><?= e(t('digitized')) ?></th><th><?= e(t('duplicate')) ?></th>
                <th class="text-right"><?= e(t('actions')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($documents as $d): ?>
                <tr>
                    <td><a href="<?= e(url('documents/show', ['id' => $d['id']])) ?>"><?= e($d['vendor']) ?></a></td>
                    <td><?= e(t($d['type'])) ?></td>
                    <td><?= e($d['doc_number']) ?></td>
                    <td><?= e($d['company_name'] ?? '-') ?></td>
                    <td><?= e($d['date']) ?></td>
                    <td class="text-right"><?= e(money($d['amount'], $d['currency'])) ?></td>
                    <td><?= e($d['category_name'] ?? '-') ?></td>
                    <td><?= status_badge($d['status']) ?></td>
                    <td><?= digitized_badge($d['digitized'], (int)$d['is_verified']) ?></td>
                    <td><?= $d['duplicate_of'] ? '<span class="badge badge-danger">#' . (int)$d['duplicate_of'] . '</span>' : '-' ?></td>
                    <td class="text-right">
                        <a class="btn btn-xs btn-default" href="<?= e(url('documents/show', ['id' => $d['id']])) ?>"><i class="fas fa-eye"></i></a>
                        <a class="btn btn-xs btn-default" href="<?= e(url('documents/edit', ['id' => $d['id']])) ?>"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$documents): ?><tr><td colspan="11" class="text-center"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
