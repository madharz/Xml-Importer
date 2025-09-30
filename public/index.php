<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../lib/Enqueue.php';

use Predis\Client;

$redis = new Client(['scheme'=>'tcp','host'=>'redis','port'=>6379]);

$messages = [];
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['xml_files'])) {
        $files = $_FILES['xml_files'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) { $errors[] = "Помилка: {$files['name'][$i]}"; continue; }

            $orig = $files['name'][$i];
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if ($ext !== 'xml') { $errors[] = "Лише .xml: $orig"; continue; }

            @mkdir('/var/www/uploads', 0777, true);
            $safe = bin2hex(random_bytes(8)).'.xml';
            $dest = "/var/www/uploads/$safe";
            if (!move_uploaded_file($files['tmp_name'][$i], $dest)) { $errors[] = "Не збережено: $orig"; continue; }

            try {
                $res = enqueueXmlFile($redis, $dest);
                $messages[] = "[$orig → {$res['fileId']}] queued {$res['queued']} / {$res['total']}";
            } catch (Throwable $e) {
                $errors[] = "Помилка розбору $orig: ".htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $errors[] = 'Файли не надійшли.';
    }
}
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>XML Importer — завантаження</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif;max-width:780px;margin:40px auto;padding:0 16px}
        .card{border:1px solid #ddd;border-radius:12px;padding:16px;margin-bottom:16px}
        .ok{color:#0a7f27}.err{color:#b00020}
        input[type=file]{margin:8px 0}
        button{padding:8px 14px;border:1px solid #333;border-radius:8px;background:#fff;cursor:pointer}
        .muted{color:#666}
    </style>
</head>
<body>
<h1>XML Importer</h1>

<div class="card">
    <form method="post" enctype="multipart/form-data">
        <label>Виберіть XML-файли (можна кілька):</label><br/>
        <input type="file" name="xml_files[]" accept=".xml" multiple required />
        <div class="muted">Ліміт приклад: до 5MB на файл</div>
        <br/>
        <button type="submit">Завантажити та поставити в чергу</button>
    </form>
</div>

<?php if ($messages): ?>
    <div class="card ok"><strong>Готово:</strong>
        <ul><?php foreach ($messages as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="card err"><strong>Помилки:</strong>
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card"><a href="/status.php">Переглянути статус імпорту</a></div>
</body>
</html>
