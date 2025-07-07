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
$pdo->exec("CREATE TABLE IF NOT EXISTS resources (
    user_id BIGINT PRIMARY KEY,
    username VARCHAR(64),
    first_name VARCHAR(64),
    food INT DEFAULT 1000,
    wood INT DEFAULT 1000,
    iron INT DEFAULT 500,
    silver INT DEFAULT 100,
    soldiers INT DEFAULT 100
)");

// Проверить, есть ли пользователь
$stmt = $pdo->prepare("SELECT food, wood, iron, silver, soldiers FROM resources WHERE user_id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if ($row) {
    // Уже есть, вернуть ресурсы
    echo json_encode($row);
} else {
    // Новый пользователь, добавить с начальными ресурсами
    $stmt = $pdo->prepare("INSERT INTO resources (user_id, username, first_name, food, wood, iron, silver, soldiers) VALUES (?, ?, ?, 1000, 1000, 500, 100, 100)");
    $stmt->execute([$user_id, $username, $first_name]);
    echo json_encode(['food'=>1000, 'wood'=>1000, 'iron'=>500, 'silver'=>100, 'soldiers'=>100]);
} 