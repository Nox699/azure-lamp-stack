<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$databaseError = null;
$formError = null;
$result = null;

try {
    $connection = db_connection();
    ensure_schema($connection);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '' || $message === '') {
            $formError = 'Navn og besked skal udfyldes.';
            http_response_code(422);
        } elseif (strlen($name) > 100 || strlen($message) > 1000) {
            $formError = 'Input er for langt.';
            http_response_code(422);
        } else {
            $statement = $connection->prepare(
                'INSERT INTO messages (name, message) VALUES (?, ?)'
            );
            $statement->bind_param('ss', $name, $message);
            $statement->execute();

            header('Location: /', true, 303);
            exit;
        }
    }

    $result = $connection->query(
        'SELECT id, name, message, created_at
         FROM messages
         ORDER BY id DESC
         LIMIT 50'
    );
} catch (Throwable $exception) {
    http_response_code(503);
    $databaseError = 'Applikationen kunne ikke kontakte databasen.';
}
?>
<!doctype html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Azure LAMP</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 760px; margin: 3rem auto; padding: 0 1rem; line-height: 1.5; }
        form { display: grid; gap: .75rem; margin: 1.5rem 0; }
        input, textarea, button { font: inherit; padding: .65rem; }
        textarea { min-height: 7rem; resize: vertical; }
        .error { padding: .75rem; border: 1px solid currentColor; }
        li { margin-bottom: .75rem; }
        small { opacity: .7; }
    </style>
</head>
<body>
    <h1>LAMP-stack virker!</h1>
    <p>Apache + PHP kører i én container, og MariaDB kører privat i en anden.</p>

    <?php if ($databaseError !== null): ?>
        <p class="error" role="alert"><?= htmlspecialchars($databaseError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <?php if ($formError !== null): ?>
            <p class="error" role="alert"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post" action="/">
            <label>
                Navn
                <input type="text" name="name" maxlength="100" required>
            </label>
            <label>
                Besked
                <textarea name="message" maxlength="1000" required></textarea>
            </label>
            <button type="submit">Send</button>
        </form>

        <h2>Beskeder</h2>
        <ul>
            <?php while ($row = $result->fetch_assoc()): ?>
                <li data-message-id="<?= (int) $row['id'] ?>">
                    <strong><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>:</strong>
                    <?= nl2br(htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8')) ?>
                    <br>
                    <small><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
