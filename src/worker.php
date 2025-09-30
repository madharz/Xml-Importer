<?php
require __DIR__ . '/vendor/autoload.php';

use Predis\Client;


const WORKER_DELAY_SECONDS = 0;

$redis = new Client(['scheme'=>'tcp','host'=>'redis','port'=>6379]);

$dsn = "pgsql:host=postgres;port=5432;dbname=xml_db;";
$pdo = new PDO($dsn, "user", "password", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$insert = $pdo->prepare("INSERT INTO products (name, price) VALUES (:name, :price)");

echo "Worker started. Waiting for jobs...\n";

while (true) {

    $res = $redis->blpop(['queue:products'], 0);
    if (!$res || count($res) < 2) continue;

    $payload = $res[1];
    $data = json_decode($payload, true);

    if (!is_array($data) || !isset($data['name'], $data['price'])) {
        fwrite(STDERR, "Bad payload: $payload\n");
        continue;
    }

    $insert->execute([
        ':name'  => $data['name'],
        ':price' => $data['price'],
    ]);

    $redis->incr('import:processed');

    if (!empty($data['source'])) {
        $redis->incr("import:{$data['source']}:processed");
    }

    echo "Inserted: " . json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;

    if (WORKER_DELAY_SECONDS > 0) {
        sleep(WORKER_DELAY_SECONDS);
    }
}
