<?php
/** @var string $month @var int $companyId @var array $companies @var array $byCategory @var array $byCompany @var array $trend @var float $total */
$months = [];
for ($i = 0; $i < 12; $i++) {
    $months[] = date('Y-m', strtotime('first day of this month -' . $i . ' months'));
}
$trendLabels = array_map(function ($t) { return substr($t['month'], 5, 2) . '/' . substr($t['month'], 2, 2); }, $trend);
$trendData = array_column($trend, 'total');
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i><?= e(t('reports')) ?></h3>
        <div class="card-tools">
            <a href="<?= e(url('reports/export')) ?>" class="btn btn-sm btn-default"><i class="fas fa-file-csv"></i> CSV</a>
        </div>
    </div>
    <div class="card-body">
        <form method="get" action="<?= e(url('reports')) ?>" class="form-row">
            <div class="col-md-4">
                <select name="month" class="form-control">
                    <?php foreach ($months as $m): ?>
                        <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="company_id" class="form-control">
                    <option value="0"><?= e(t('all_companies')) ?></option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $companyId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary btn-block"><i class="fas fa-filter"></i> <?= e(t('filter')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-euro-sign"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= e(t('total_expenses')) ?></span>
                <span class="info-box-number"><?= e(money($total, 'EUR')) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= e(t('by_category')) ?></h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th><?= e(t('category')) ?></th><th class="text-right"><?= e(t('documents')) ?></th><th class="text-right"><?= e(t('total')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($byCategory as $r): ?>
                        <tr><td><?= e($r['category']) ?></td><td class="text-right"><?= (int)$r['n'] ?></td><td class="text-right"><?= e(money($r['total'], 'EUR')) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= e(t('by_company')) ?></h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th><?= e(t('company_name')) ?></th><th class="text-right"><?= e(t('total')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($byCompany as $r): ?>
                        <tr><td><?= e($r['company']) ?></td><td class="text-right"><?= e(money($r['total'], 'EUR')) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><?= e(t('monthly_trend')) ?></h3></div>
    <div class="card-body"><canvas id="trendChart" height="100"></canvas></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [{ label: 'EUR', data: <?= json_encode($trendData) ?>, backgroundColor: 'rgba(60,141,188,0.7)' }]
    },
    options: { scales: { yAxes: [{ ticks: { beginAtZero: true } }] } }
});
</script>
