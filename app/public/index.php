<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\Logger;
use App\Models\Demo;

// Инициализация Eloquent (Capsule) и логгера
Bootstrap::db();
$log = Logger::make();

// --- Контекст входящего HTTP-запроса ---
$requestId   = bin2hex(random_bytes(8));
$method      = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri         = $_SERVER['REQUEST_URI'] ?? '/';
// Сырое тело запроса (для application/json и т.п.). Для обычной формы оно
// будет уже разобрано в $_POST, но для лога полезно видеть именно raw-тело
$rawBody     = (string) file_get_contents('php://input');

/**
 * Общая запись лога о запросе. Тело ответа подставляем в $context['response_body'].
 */
$logRequest = static function (array $context) use ($log, $requestId, $method, $uri, $rawBody): void {
    $log->info('http_request', $context + [
        'request_id'   => $requestId,
        'method'       => $method,
        'uri'          => $uri,
        'request_body' => $rawBody,
    ]);
};

// --- Обработка POST: добавление записи (через Eloquent) ---
if ($method === 'POST' && isset($_POST['note'])) {
    try {
        Demo::create(['note' => (string) $_POST['note']]);
        $log->info('note_created', ['request_id' => $requestId, 'note' => (string) $_POST['note']]);
    } catch (\Throwable $e) {
        $log->error('note_create_failed', ['request_id' => $requestId, 'error' => $e->getMessage()]);
    }

    // Post/Redirect/Get: тела ответа здесь физически нет, логируем только статус
    $logRequest([
        'response_status' => 302,
        'response_body'   => '(redirect to /)',
    ]);

    header('Location: /');
    exit;
}

// --- Чтение данных через Eloquent ---
$phpVersion  = PHP_VERSION;
$hasPdoPgsql = extension_loaded('pdo_pgsql');
$hostname    = gethostname();

$dbVersion = null;
$dbError   = null;
$notes     = [];

try {
    $capsule = Bootstrap::db();
    // В Query Builder Eloquent нет fetchColumn() — берём одно значение через scalar()
    $dbVersion = (string) $capsule->getConnection()->selectOne('SELECT version()')->version;
    $notes = Demo::orderByDesc('id')->get();
} catch (\Throwable $e) {
    $dbError = $e->getMessage();
    $log->error('db_read_failed', ['request_id' => $requestId, 'error' => $dbError]);
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// --- Весь HTML собираем в буфер, чтобы залогировать тело ответа ---
ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>nginx + php-fpm + PostgreSQL</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #1c1c1c; }
        h1 { font-size: 1.4rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; }
        dl { display: grid; grid-template-columns: max-content 1fr; gap: 0.3rem 1rem; background: #f6f6f6; padding: 1rem; border-radius: 6px; }
        dt { font-weight: 600; }
        dd { margin: 0; }
        .ok { color: #0a7a2f; }
        .fail { color: #b00020; }
        table { border-collapse: collapse; width: 100%; margin: 0.5rem 0 1rem; }
        th, td { border: 1px solid #ddd; padding: 0.4rem 0.6rem; text-align: left; font-size: 0.9rem; }
        th { background: #f0f0f0; }
        form { display: flex; gap: 0.5rem; }
        input[type=text] { flex: 1; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 0.5rem 1.2rem; border: 0; border-radius: 4px; background: #2563eb; color: #fff; cursor: pointer; }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <h1>nginx + php-fpm + PostgreSQL</h1>

    <dl>
        <dt>Версия PHP</dt>
        <dd><?= h($phpVersion) ?></dd>

        <dt>Расширение pdo_pgsql</dt>
        <dd class="<?= $hasPdoPgsql ? 'ok' : 'fail' ?>"><?= $hasPdoPgsql ? 'подключено' : 'отсутствует' ?></dd>

        <dt>Хост контейнера</dt>
        <dd><?= h((string) $hostname) ?></dd>

        <dt>Подключение к БД</dt>
        <dd class="<?= $dbError === null ? 'ok' : 'fail' ?>">
            <?= $dbError === null ? h($dbVersion ?? '') : 'ошибка: ' . h($dbError) ?>
        </dd>
    </dl>

    <h2>Таблица demo</h2>
    <?php if ($notes->isNotEmpty()): ?>
        <table>
            <tr><th>id</th><th>создано</th><th>note</th></tr>
            <?php foreach ($notes as $note): ?>
                <tr>
                    <td><?= h((string) $note->id) ?></td>
                    <td><?= h((string) $note->created_at) ?></td>
                    <td><?= h((string) $note->note) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Нет данных (таблица пуста или БД недоступна).</p>
    <?php endif; ?>

    <form method="post" action="/">
        <input type="text" name="note" placeholder="Новая запись" required maxlength="500">
        <button type="submit">Добавить</button>
    </form>
</body>
</html>
<?php
// Достаём сгенерированный HTML из буфера, отдаём клиенту и логируем
$responseBody = (string) ob_get_clean();
echo $responseBody;

$logRequest([
    'response_status' => http_response_code(),
    'response_body'   => $responseBody,
]);
