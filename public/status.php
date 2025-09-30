<?php
require __DIR__ . '/../vendor/autoload.php';
use Predis\Client;

$redis = new Client(['scheme'=>'tcp','host'=>'redis','port'=>6379]);

$queued    = (int)$redis->llen('queue:products');
$total     = (int)($redis->get('import:total') ?: 0);
$processed = (int)($redis->get('import:processed') ?: 0);
$pending   = max(0, $total - $processed);
$percent   = $total ? round($processed/$total*100,1) : 0.0;
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Статус імпорту</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;max-width:780px;margin:40px auto;padding:0 16px}
        .card{border:1px solid #ddd;border-radius:12px;padding:16px;margin-bottom:16px}
    </style>
</head>
<body>
<h1>Статус імпорту</h1>
<div class="card">
    <p><strong>Total:</strong> <?= $total ?></p>
    <p><strong>Processed:</strong> <?= $processed ?> (<?= $percent ?>%)</p>
    <p><strong>In queue:</strong> <?= $queued ?></p>
    <p><strong>Pending:</strong> <?= $pending ?></p>
</div>
<div class="card"><a href="/">← Повернутись до завантаження</a></div>
</body>
</html>
