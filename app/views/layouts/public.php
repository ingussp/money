<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Money brings income, expenses, documents and your team into one clear workspace."><title><?= e($title) ?> | Money</title><link rel="stylesheet" href="<?= e(asset('app.css')) ?>"><?php if(($view??'')==='home'): ?><link rel="stylesheet" href="<?= e(asset('landing.css')) ?>"><?php endif ?><script src="<?= e(asset('icons.js')) ?>" defer></script><script src="<?= e(asset('app.js')) ?>" defer></script></head><body class="public-body"><a class="skip-link" href="#main">Skip to content</a>
<?php if(($view??'')!=='home'): ?><header class="public-header"><a class="brand" href="<?= e(url()) ?>"><span class="brand-mark">m</span> money<span class="brand-dot">.</span></a><a href="<?= e(url('home')) ?>" class="text-link">Back to home <?= icon('arrow-up-right') ?></a></header><?php endif ?>
<?= $content ?>
</body></html>
