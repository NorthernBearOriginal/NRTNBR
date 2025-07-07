<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['drop']) && $_GET['drop'] == '1') {
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
        $pdo->exec("DROP TABLE IF EXISTS buildings");
        $pdo->exec("DROP TABLE IF EXISTS resources");
        echo 'Таблицы resources и buildings удалены. Теперь обновите главную страницу для автоматического создания новых таблиц.';
    } catch(Exception $e) {
        echo 'Ошибка: ' . $e->getMessage();
    }
    exit;
}

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

// --- СНАЧАЛА СОЗДАЁМ ТАБЛИЦЫ ---
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
$pdo->exec("CREATE TABLE IF NOT EXISTS buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
    building_type VARCHAR(32),
    level INT DEFAULT 1,
    build_start INT,
    build_finish INT,
    last_produce INT,
    UNIQUE KEY (user_id, building_type),
    FOREIGN KEY (user_id) REFERENCES resources(user_id)
)");

$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$action = isset($data['action']) ? $data['action'] : '';

if (!$user_id) {
    echo json_encode(['error' => 'No user_id']);
    exit;
}

// --- Гарантировать стартовые ресурсы ---
$username = isset($data['username']) ? $data['username'] : '';
$first_name = isset($data['first_name']) ? $data['first_name'] : '';
$stmt = $pdo->prepare("SELECT user_id FROM resources WHERE user_id = ?");
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO resources (user_id, username, first_name, food, wood, iron, silver, soldiers) VALUES (?, ?, ?, 1000, 1000, 500, 100, 100)");
    $stmt->execute([$user_id, $username, $first_name]);
}

try {
    // Миграция: добавить level, если нет
    $pdo->exec("ALTER TABLE buildings ADD COLUMN level INT DEFAULT 1");
} catch (Exception $e) {}
try {
    // Миграция: добавить UNIQUE KEY, если нет
    $pdo->exec("ALTER TABLE buildings ADD UNIQUE KEY user_building (user_id, building_type)");
} catch (Exception $e) {}
try {
    // Миграция: выставить level=1 для старых записей
    $pdo->exec("UPDATE buildings SET level=1 WHERE level IS NULL");
} catch (Exception $e) {}

// --- Начисление ресурсов за все строения ---
$now = time();
$building_types = [
    'lumbermill' => ['resource' => 'wood'],
    'farm' => ['resource' => 'food'],
    'ironmine' => ['resource' => 'iron'],
    'silvermine' => ['resource' => 'silver'],
];
$total_add = ['wood'=>0, 'food'=>0, 'iron'=>0, 'silver'=>0];
foreach ($building_types as $type => $info) {
    $stmt = $pdo->prepare("SELECT * FROM buildings WHERE user_id = ? AND building_type = ? AND build_finish <= ?");
    $stmt->execute([$user_id, $type, $now]);
    $buildings = $stmt->fetchAll();
    foreach ($buildings as $b) {
        $last = $b['last_produce'] ?: $b['build_finish'];
        $minutes = floor(($now - $last) / 60);
        if ($minutes > 0) {
            $add = $minutes * 100 * $b['level'];
            $total_add[$info['resource']] += $add;
            $upd = $pdo->prepare("UPDATE buildings SET last_produce = ? WHERE id = ?");
            $upd->execute([$last + $minutes * 60, $b['id']]);
        }
    }
}
foreach ($total_add as $res => $val) {
    if ($val > 0) {
        $upd = $pdo->prepare("UPDATE resources SET $res = $res + ? WHERE user_id = ?");
        $upd->execute([$val, $user_id]);
    }
}

// --- Действия ---
$build_actions = [
    'build_lumbermill' => ['type'=>'lumbermill'],
    'build_farm' => ['type'=>'farm'],
    'build_ironmine' => ['type'=>'ironmine'],
    'build_silvermine' => ['type'=>'silvermine'],
];
foreach ($build_actions as $action_name => $params) {
    if ($action === $action_name) {
        $type = $params['type'];
        // Проверить, есть ли уже строение
        $stmt = $pdo->prepare("SELECT * FROM buildings WHERE user_id = ? AND building_type = ?");
        $stmt->execute([$user_id, $type]);
        $b = $stmt->fetch();
        $level = 1;
        $costs = ['food'=>0, 'wood'=>0, 'iron'=>0];
        $build_minutes = 5;
        if ($type === 'lumbermill') {
            $costs['food'] = 200;
        } elseif ($type === 'farm') {
            $costs['wood'] = 200;
        } elseif ($type === 'ironmine') {
            $costs['food'] = 200;
            $costs['wood'] = 200;
        } elseif ($type === 'silvermine') {
            $costs['food'] = 200;
            $costs['wood'] = 200;
            $costs['iron'] = 100;
        }
        if ($b) {
            $level = $b['level'] + 1;
            foreach ($costs as $k => &$v) { $v = $v * pow(2, $b['level']); }
            unset($v);
            $build_minutes = 5 * pow(2, $b['level']);
            if ($b['build_finish'] > $now) {
                echo json_encode(['error' => 'Улучшение уже строится']);
                exit;
            }
        }
        // Проверить ресурсы
        $stmt = $pdo->prepare("SELECT food, wood, iron FROM resources WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $res = $stmt->fetch();
        $enough = true;
        foreach ($costs as $k => $v) {
            if ($v > 0 && $res[$k] < $v) $enough = false;
        }
        if (!$enough) {
            echo json_encode(['error' => 'Недостаточно ресурсов для строительства']);
            exit;
        }
        // Списать ресурсы
        $upd = $pdo->prepare("UPDATE resources SET food = food - ?, wood = wood - ?, iron = iron - ? WHERE user_id = ?");
        $upd->execute([$costs['food'], $costs['wood'], $costs['iron'], $user_id]);
        $build_start = $now;
        $build_finish = $now + $build_minutes * 60;
        if ($b) {
            $stmt = $pdo->prepare("UPDATE buildings SET level = ?, build_start = ?, build_finish = ?, last_produce = 0 WHERE id = ?");
            $stmt->execute([$level, $build_start, $build_finish, $b['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO buildings (user_id, building_type, level, build_start, build_finish, last_produce) VALUES (?, ?, 1, ?, ?, 0)");
            $stmt->execute([$user_id, $type, $build_start, $build_finish]);
        }
    }
}

// --- Вернуть список строений и ресурсы ---
$stmt = $pdo->prepare("SELECT building_type, level, build_start, build_finish FROM buildings WHERE user_id = ? ORDER BY build_finish ASC");
$stmt->execute([$user_id]);
$buildings = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT food, wood, iron, silver, soldiers FROM resources WHERE user_id = ?");
$stmt->execute([$user_id]);
$res = $stmt->fetch();

// Формируем список строений для фронта
$buildings_out = [];
foreach ($buildings as $b) {
    $status = ($b['build_finish'] > $now) ? 'building' : 'ready';
    $time_left = max(0, $b['build_finish'] - $now);
    $buildings_out[] = [
        'type' => $b['building_type'],
        'level' => $b['level'],
        'status' => $status,
        'time_left' => $time_left
    ];
}

$out = [
    'resources' => $res,
    'buildings' => $buildings_out
];

// После подключения к БД, добавить отладку:
$debug = [];
try {
    $debug['resources_structure'] = [];
    $q = $pdo->query("DESCRIBE resources");
    while($row = $q->fetch(PDO::FETCH_ASSOC)) $debug['resources_structure'][] = $row;
    $q = $pdo->prepare("SELECT * FROM resources WHERE user_id = ?");
    $q->execute([$user_id]);
    $debug['user_row'] = $q->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e) { $debug['debug_error'] = $e->getMessage(); }

$out['debug'] = $debug;

echo json_encode($out); 