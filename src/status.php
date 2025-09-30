<?php
require __DIR__ . '/vendor/autoload.php';
use Predis\Client;

$redis = new Client(['scheme'=>'tcp','host'=>'redis','port'=>6379]);

$fileId = $argv[1] ?? null;

if ($fileId) {
    $total     = (int)($redis->get("import:$fileId:total") ?: 0);
    $processed = (int)($redis->get("import:$fileId:processed") ?: 0);
    $queued    = (int)$redis->llen('queue:products');
    $pending   = max(0, $total - $processed);
    $percent   = $total ? round($processed / $total * 100, 1) : 0.0;

    echo "[$fileId] Total: $total | Processed: $processed ($percent%) | In queue: $queued | Pending: $pending\n";
} else {
    $total     = (int)($redis->get('import:total') ?: 0);
    $processed = (int)($redis->get('import:processed') ?: 0);
    $queued    = (int)$redis->llen('queue:products');
    $pending   = max(0, $total - $processed);
    $percent   = $total ? round($processed / $total * 100, 1) : 0.0;

    echo "GLOBAL Total: $total | Processed: $processed ($percent%) | In queue: $queued | Pending: $pending\n";
}
