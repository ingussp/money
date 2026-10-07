<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>404 &middot; <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container text-center pt-5">
        <h1 class="display-1">404</h1>
        <p class="lead">Page not found.</p>
        <a class="btn btn-primary" href="<?= e(url('dashboard')) ?>"><?= e(t('dashboard')) ?></a>
    </div>
</body>
</html>
