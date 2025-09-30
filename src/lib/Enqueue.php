<?php

use Predis\Client;

function enqueueXmlFile(Client $redis, string $xmlFile): array {
    if (!file_exists($xmlFile)) {
        throw new RuntimeException("XML file not found: $xmlFile");
    }

    $xml = simplexml_load_file($xmlFile);
    if ($xml === false) {
        throw new RuntimeException("Failed to parse XML: $xmlFile");
    }

    $fileId = basename($xmlFile);
    $total  = count($xml->product);

    $redis->set("import:$fileId:total", $total);
    $redis->set("import:$fileId:processed", 0);

    $globalTotal = (int)($redis->get('import:total') ?: 0);
    $redis->set('import:total', $globalTotal + $total);

    $queued = 0;

    foreach ($xml->product as $p) {
        $name  = trim((string)$p->name);
        $price = (float)$p->price;

        if ($name === '' || $price < 0) {

            continue;
        }

        $payload = json_encode([
            'name'   => $name,
            'price'  => $price,
            'source' => $fileId
        ], JSON_UNESCAPED_UNICODE);


        $redis->rpush('queue:products', [$payload]);
        $queued++;
    }

    return ['fileId' => $fileId, 'total' => $total, 'queued' => $queued];
}