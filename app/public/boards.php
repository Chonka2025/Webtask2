<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\Logger;
use App\Models\Board;
use App\Models\User;

session_start();

Bootstrap::db();
$log = Logger::make();

// --- Контекст запроса (для лога) ---
$requestId = bin2hex(random_bytes(8));
$method    = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri       = $_SERVER['REQUEST_URI'] ?? '/boards.php';
$rawBody   = (string) file_get_contents('php://input');

/** Флеш-сообщение в сессии (показывается после redirect — паттерн PRG). */
$flash = static function (string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
};

// --- POST: обработка CRUD-действий → потом redirect (Post/Redirect/Get) ---
if ($method === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        $title    = trim((string) ($_POST['title'] ?? ''));
        $ownerId  = (int) ($_POST['owner_user_id'] ?? 0);
        $isPublic = isset($_POST['is_public']);

        // Серверная валидация (не полагаемся только на required в HTML).
        // Для delete название/владелец не нужны — проверяем только create/update.
        if (in_array($action, ['create', 'update'], true)) {
            if ($title === '') {
                throw new RuntimeException('Название не может быть пустым');
            }
            if (!User::where('user_id', $ownerId)->exists()) {
                throw new RuntimeException('Владелец (пользователь) не найден');
            }
        }

        switch ($action) {
            case 'create':
                Board::create([
                    'title'         => $title,
                    'owner_user_id' => $ownerId,
                    'is_public'     => $isPublic,
                ]);
                $flash('ok', 'Доска создана');
                break;

            case 'update':
                $board = Board::find((int) ($_POST['board_id'] ?? 0));
                if ($board === null) {
                    throw new RuntimeException('Доска не найдена');
                }
                $board->update([
                    'title'         => $title,
                    'owner_user_id' => $ownerId,
                    'is_public'     => $isPublic,
                ]);
                $flash('ok', 'Доска обновлена');
                break;

            case 'delete':
                $board = Board::find((int) ($_POST['board_id'] ?? 0));
                if ($board === null) {
                    throw new RuntimeException('Доска не найдена');
                }
                $board->delete();
                $flash('ok', 'Доска удалена');
                break;

            default:
                throw new RuntimeException('Неизвестное действие');
        }

        $log->info('board_crud', [
            'request_id'   => $requestId,
            'action'       => $action,
            'method'       => $method,
            'uri'          => $uri,
            'request_body' => $rawBody,
        ]);
    } catch (\Throwable $e) {
        $log->error('board_crud_failed', [
            'request_id' => $requestId,
            'action'     => $action,
            'error'      => $e->getMessage(),
        ]);
        $flash('err', 'Ошибка: ' . $e->getMessage());
    }

    // Post/Redirect/Get — чтобы F5 не повторял действие
    header('Location: boards.php');
    exit;
}

// --- GET: данные для отображения ---
$boards  = Board::with('owner')->orderBy('board_id')->get();
$users   = User::orderBy('name')->get();
$editing = isset($_GET['edit']) ? Board::find((int) $_GET['edit']) : null;

// Забираем флеш-сообщение (один раз)
$message = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// --- Собираем HTML в буфер, чтобы залогировать тело ответа ---
ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Доски — CRUD</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 860px; margin: 2rem auto; padding: 0 1rem; color: #1c1c1c; }
        h1 { font-size: 1.4rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; }
        nav a { margin-right: 1rem; }
        .flash { padding: .6rem 1rem; border-radius: 6px; margin: 1rem 0; }
        .flash.ok { background: #e6f6ec; color: #0a7a2f; }
        .flash.err { background: #fdecea; color: #b00020; }
        form.card { background: #f6f6f6; padding: 1rem; border-radius: 6px; display: grid; gap: .6rem; max-width: 420px; }
        form.card label { display: grid; gap: .2rem; font-size: .9rem; }
        form.card input[type=text], form.card select { padding: .45rem; border: 1px solid #ccc; border-radius: 4px; }
        form.card .row { display: flex; gap: .6rem; align-items: center; }
        button { padding: .5rem 1.2rem; border: 0; border-radius: 4px; background: #2563eb; color: #fff; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        button.danger { background: #b00020; }
        button.danger:hover { background: #8f001a; }
        a.btn { display: inline-block; padding: .3rem .7rem; background: #eee; border-radius: 4px; text-decoration: none; color: #1c1c1c; }
        table { border-collapse: collapse; width: 100%; margin: .5rem 0 1rem; }
        th, td { border: 1px solid #ddd; padding: .45rem .6rem; text-align: left; font-size: .9rem; }
        th { background: #f0f0f0; }
        td .actions { display: flex; gap: .4rem; align-items: center; }
    </style>
</head>
<body>
    <nav>
        <a href="index.php">← Главная</a>
        <a href="boards.php">Доски (CRUD)</a>
    </nav>

    <h1>Управление досками (CRUD)</h1>

    <?php if ($message !== null): ?>
        <div class="flash <?= h($message['type']) ?>"><?= h($message['msg']) ?></div>
    <?php endif; ?>

    <h2><?= $editing !== null ? 'Редактирование доски #' . (int) $editing->board_id : 'Новая доска' ?></h2>
    <form class="card" method="post" action="boards.php">
        <?php if ($editing !== null): ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="board_id" value="<?= (int) $editing->board_id ?>">
        <?php else: ?>
            <input type="hidden" name="action" value="create">
        <?php endif; ?>

        <label>Название
            <input type="text" name="title" required maxlength="255"
                   value="<?= h((string) ($editing->title ?? '')) ?>">
        </label>

        <label>Владелец
            <select name="owner_user_id" required>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u->user_id ?>"
                        <?= (int) ($editing->owner_user_id ?? 0) === (int) $u->user_id ? 'selected' : '' ?>>
                        <?= h($u->name) ?> (<?= h($u->email) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="row">
            <input type="checkbox" name="is_public" <?= !empty($editing->is_public) ? 'checked' : '' ?>>
            Публичная доска
        </label>

        <div class="row">
            <button type="submit"><?= $editing !== null ? 'Сохранить' : 'Создать' ?></button>
            <?php if ($editing !== null): ?>
                <a class="btn" href="boards.php">Отмена</a>
            <?php endif; ?>
        </div>
    </form>

    <h2>Все доски (<?= count($boards) ?>)</h2>
    <?php if ($boards->isEmpty()): ?>
        <p>Досок пока нет — создайте первую выше.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>id</th><th>Название</th><th>Владелец</th><th>Публичная</th><th>Создана</th><th>Действия</th>
            </tr>
            <?php foreach ($boards as $b): ?>
                <tr>
                    <td><?= (int) $b->board_id ?></td>
                    <td><?= h($b->title) ?></td>
                    <td><?= h($b->owner->name ?? '—') ?></td>
                    <td><?= $b->is_public ? 'да' : 'нет' ?></td>
                    <td><?= h((string) $b->created_datetime) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn" href="boards.php?edit=<?= (int) $b->board_id ?>">править</a>
                            <form method="post" action="boards.php" style="display:inline"
                                  onsubmit="return confirm('Удалить доску «<?= h($b->title) ?>»?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="board_id" value="<?= (int) $b->board_id ?>">
                                <button class="danger" type="submit">удалить</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
<?php
// Достаём HTML из буфера, отдаём клиенту и логируем запрос с телом ответа
$responseBody = (string) ob_get_clean();
echo $responseBody;

$log->info('http_request', [
    'request_id'    => $requestId,
    'method'        => $method,
    'uri'           => $uri,
    'request_body'  => $rawBody,
    'response_status' => http_response_code(),
    'response_body' => $responseBody,
]);
