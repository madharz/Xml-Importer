<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/Enqueue.php';

use Predis\Client;

$redis = new Client(['scheme'=>'tcp','host'=>'redis','port'=>6379]);

$path = $argv[1] ?? '/var/www/data/input.xml';

$files = [];
if (is_dir($path)) {
    foreach (glob(rtrim($path, '/').'/*.xml') as $f) {
        if (is_file($f)) $files[] = $f;
    }
} else {
    $files[] = $path;
}

if (!$files) {
    fwrite(STDERR, "No XML files at: $path\n");
    exit(0);
}

$totalQueued = 0;
$totalItems  = 0;

foreach ($files as $file) {
    try {
        $res = enqueueXmlFile($redis, $file);
        echo "[{$res['fileId']}] queued {$res['queued']} / {$res['total']}\n";
        $totalQueued += $res['queued'];
        $totalItems  += $res['total'];
    } catch (Throwable $e) {
        fwrite(STDERR, "ERROR: ".$e->getMessage()."\n");
    }
}

echo "TOTAL queued: $totalQueued / $totalItems\n";