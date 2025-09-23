<?php
require __DIR__ . '/vendor/autoload.php';

use Predis\Client;

$redis = new Client(['scheme' => 'tcp', 'host' => 'redis', 'port' => 6379]);

$total     = (int) ($redis->get('import:total') ?: 0);
$processed = (int) ($redis->get('import:processed') ?: 0);
$queued    = $redis->llen('queue:products');

$pending   = max(0, $total - $processed);
$percent   = $total > 0 ? round(($processed / $total) * 100, 1) : 0.0;

echo "Total: $total | Processed: $processed ($percent%) | In queue: $queued | Pending: $pending\n";
