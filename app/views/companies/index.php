<?php /** @var array $companies */ ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= e(t('companies')) ?></h3>
        <div class="card-tools">
            <a href="<?= e(url('companies/create')) ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?= e(t('new_company')) ?></a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead><tr><th>#</th><th><?= e(t('company_name')) ?></th><th><?= e(t('reg_number')) ?></th><th><?= e(t('stat_documents')) ?></th><th class="text-right"><?= e(t('actions')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($companies as $c): ?>
                <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['reg_number']) ?></td>
                    <td><span class="badge badge-info"><?= (int)$c['doc_count'] ?></span></td>
                    <td class="text-right">
                        <a class="btn btn-xs btn-default" href="<?= e(url('companies/edit', ['id' => $c['id']])) ?>"><i class="fas fa-edit"></i></a>
                        <form action="<?= e(url('companies/delete')) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= e(t('confirm_delete')) ?>')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$companies): ?><tr><td colspan="5" class="text-center"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
