<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$db   = 'bearbeaty3';
$user = 'bearbeaty3';
$pass = 'Snake239886';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$username = isset($data['username']) ? $data['username'] : '';
$first_name = isset($data['first_name']) ? $data['first_name'] : '';

if (!$user_id) {
    echo json_encode(['error' => 'No user_id']);
    exit;
}

// Создать таблицу, если не существует
$pdo->exec("CREATE TABLE IF NOT EXISTS visits (
    user_id BIGINT PRIMARY KEY,
    username VARCHAR(64),
    first_name VARCHAR(64),
    visits INT DEFAULT 0
)");

// Вставить или обновить посещения
$stmt = $pdo->prepare("INSERT INTO visits (user_id, username, first_name, visits) VALUES (?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE visits = visits + 1, username = VALUES(username), first_name = VALUES(first_name)");
$stmt->execute([$user_id, $username, $first_name]);

// Получить количество посещений
$stmt = $pdo->prepare("SELECT visits FROM visits WHERE user_id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if ($row) {
    echo json_encode(['visits' => $row['visits']]);
} else {
    echo json_encode(['visits' => 1]);
} 