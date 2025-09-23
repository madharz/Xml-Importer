<?php
require __DIR__ . '/vendor/autoload.php';

use Predis\Client;

$redis = new Client([
    'scheme' => 'tcp',
    'host'   => 'redis',
    'port'   => 6379,
]);

$xmlFile = '/var/www/data/input.xml';
if (!file_exists($xmlFile)) {
    fwrite(STDERR, "XML file not found: $xmlFile\n");
    exit(1);
}

$xml = simplexml_load_file($xmlFile);
if ($xml === false) {
    fwrite(STDERR, "Failed to parse XML\n");
    exit(1);
}

$total = count($xml->product);
$redis->set('import:total', $total);
$redis->set('import:processed', 0);

$queued = 0;
foreach ($xml->product as $p) {
    $name  = trim((string)$p->name);
    $price = (float)$p->price;

    if ($name === '' || $price < 0) {
        fwrite(STDERR, "Skip invalid item: '$name' / $price\n");
        continue;
    }

    $payload = json_encode(['name' => $name, 'price' => $price], JSON_UNESCAPED_UNICODE);

    $redis->rpush('queue:products', [$payload]);
    $queued++;
}

echo "Queued $queued / $total items to Redis (queue:products)\n";
