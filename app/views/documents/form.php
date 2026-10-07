<?php
/** @var array|null $document @var array $lines @var array $categories @var array $companies */
$d = $document;
$isEdit = $d !== null;
$action = $isEdit ? url('documents/edit', ['id' => $d['id']]) : url('documents/create');
?>
<form action="<?= e($action) ?>" method="post" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="row">
    <div class="col-lg-8">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><?= e(t('document_details')) ?></h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label><?= e(t('vendor')) ?> *</label>
                        <input type="text" name="vendor" class="form-control" value="<?= e($d['vendor'] ?? '') ?>" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label><?= e(t('type')) ?></label>
                        <select name="type" class="form-control">
                            <?php foreach (['receipt', 'invoice'] as $t): ?>
                                <option value="<?= $t ?>" <?= ($d['type'] ?? 'receipt') === $t ? 'selected' : '' ?>><?= e(t($t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label><?= e(t('doc_number')) ?></label>
                        <input type="text" name="doc_number" class="form-control" value="<?= e($d['doc_number'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label><?= e(t('date')) ?></label>
                        <input type="date" name="date" class="form-control" value="<?= e($d['date'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label><?= e(t('amount')) ?> *</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="<?= e($d['amount'] ?? '') ?>" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label><?= e(t('currency')) ?></label>
                        <select name="currency" class="form-control">
                            <?php foreach (['EUR', 'USD', 'GBP', 'SEK', 'NOK', 'DKK', 'PLN', 'CHF'] as $cur): ?>
                                <option value="<?= $cur ?>" <?= ($d['currency'] ?? 'EUR') === $cur ? 'selected' : '' ?>><?= $cur ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label><?= e(t('tax')) ?></label>
                        <input type="number" step="0.01" name="tax" class="form-control" value="<?= e($d['tax'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label><?= e(t('status')) ?></label>
                        <select name="status" class="form-control">
                            <?php foreach (['draft', 'submitted', 'pending', 'approved', 'paid'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($d['status'] ?? 'submitted') === $s ? 'selected' : '' ?>><?= e(t($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label><?= e(t('company_name')) ?></label>
                        <select name="company_id" class="form-control">
                            <option value="0">-</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)($d['company_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label><?= e(t('category')) ?> <small class="text-muted">(<?= e(t('ai_auto')) ?>)</small></label>
                        <select name="category_id" class="form-control">
                            <option value="0">-</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)($d['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label><?= e(t('digitized')) ?></label>
                        <select name="digitized" class="form-control">
                            <?php foreach (['robo', 'human', 'none'] as $m): ?>
                                <option value="<?= $m ?>" <?= ($d['digitized'] ?? 'robo') === $m ? 'selected' : '' ?>><?= e(t($m)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><?= e(t('file')) ?></label>
                    <input type="file" name="file" class="form-control-file">
                    <?php if (!empty($d['file_name'])): ?><small class="text-muted"><?= e($d['file_name']) ?></small><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= e(t('line_items')) ?></h3>
                <div class="card-tools"><button type="button" class="btn btn-sm btn-default" onclick="addLine()"><i class="fas fa-plus"></i></button></div>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th><?= e(t('description')) ?></th><th style="width:110px"><?= e(t('quantity')) ?></th><th style="width:130px"><?= e(t('unit_price')) ?></th><th style="width:130px"><?= e(t('amount')) ?></th><th style="width:40px"></th></tr></thead>
                    <tbody id="lines-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= e(t('save')) ?></button>
        <a href="<?= e(url('documents')) ?>" class="btn btn-default"><?= e(t('cancel')) ?></a>
    </div>
</div>
</form>

<template id="line-template">
    <tr>
        <td><input type="text" name="lines[description][]" class="form-control"></td>
        <td><input type="number" step="0.01" name="lines[quantity][]" class="form-control line-qty" value="1"></td>
        <td><input type="number" step="0.01" name="lines[unit_price][]" class="form-control line-price" value="0"></td>
        <td><input type="number" step="0.01" name="lines[amount][]" class="form-control line-amt" value="0"></td>
        <td><button type="button" class="btn btn-xs btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>

<script>
var existing = <?= json_encode($lines, JSON_UNESCAPED_UNICODE) ?>;
function row(desc, qty, price, amt) {
    var tr = document.getElementById('line-template').content.firstElementChild.cloneNode(true);
    tr.querySelector('[name="lines[description][]"]').value = desc || '';
    tr.querySelector('[name="lines[quantity][]"]').value = qty || 1;
    tr.querySelector('[name="lines[unit_price][]"]').value = price || 0;
    tr.querySelector('[name="lines[amount][]"]').value = amt || 0;
    return tr;
}
function addLine(desc, qty, price, amt) {
    document.getElementById('lines-body').appendChild(row(desc, qty, price, amt));
}
existing.forEach(function (l) { addLine(l.description, l.quantity, l.unit_price, l.amount); });
if (!existing.length) { addLine(); }
</script>
