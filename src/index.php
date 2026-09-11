<?php
$host = "db";
$db   = getenv("MYSQL_DATABASE");
$user = getenv("MYSQL_USER");
$pass = getenv("MYSQL_PASSWORD");

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Databaseforbindelse fejlede: " . $conn->connect_error);
}

// Opret tabel hvis den ikke findes
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Håndter indsendelse af formular
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $conn->prepare("INSERT INTO messages (name, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST["name"], $_POST["message"]);
    $stmt->execute();
}

$result = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head><title>LAMP Test</title></head>
<body>
    <h1>LAMP-stack virker!</h1>
    <form method="post">
        <input type="text" name="name" placeholder="Navn" required>
        <input type="text" name="message" placeholder="Besked" required>
        <button type="submit">Send</button>
    </form>
    <ul>
    <?php while ($row = $result->fetch_assoc()): ?>
        <li><strong><?= htmlspecialchars($row["name"]) ?>:</strong> <?= htmlspecialchars($row["message"]) ?></li>
    <?php endwhile; ?>
    </ul>
</body>
</html>
