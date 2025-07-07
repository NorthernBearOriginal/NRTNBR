<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$pdo = new PDO($dsn, $user, $pass, $options);

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if (!$user_id) { echo 'Нет пользователя'; exit; }

// Получить пользователя
$stmt = $pdo->prepare("SELECT * FROM resources WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) { echo 'Пользователь не найден'; exit; }

// Количество построек
$stmt = $pdo->prepare("SELECT COUNT(*) FROM buildings WHERE user_id = ?");
$stmt->execute([$user_id]);
$buildings_count = $stmt->fetchColumn();
// Количество сообщений
$stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE user_id = ?");
$stmt->execute([$user_id]);
$messages_count = $stmt->fetchColumn();
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Профиль</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        #profile_wrap { max-width: 420px; margin: 48px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px 0 rgba(60,60,120,0.10); padding: 32px 18px 28px 18px; text-align: center; }
        #profile_nick { font-size: 1.5em; font-weight: 700; color: #2678b6; margin-bottom: 18px; }
        .stat { font-size: 1.1em; margin: 10px 0; color: #333; }
        .stat-label { color: #888; margin-right: 8px; }
        #back_btn { margin-top: 24px; background: #eee; border: none; border-radius: 7px; padding: 7px 18px; cursor: pointer; }
    </style>
</head>
<body>
    <div id="profile_wrap">
        <div id="profile_nick"><?php echo htmlspecialchars($user['first_name'] ?: $user['username'] ?: 'Гость'); ?></div>
        <div class="stat"><span class="stat-label">Еда:</span> <?php echo $user['food']; ?></div>
        <div class="stat"><span class="stat-label">Дерево:</span> <?php echo $user['wood']; ?></div>
        <div class="stat"><span class="stat-label">Железо:</span> <?php echo $user['iron']; ?></div>
        <div class="stat"><span class="stat-label">Серебро:</span> <?php echo $user['silver']; ?></div>
        <div class="stat"><span class="stat-label">Армия:</span> <?php echo $user['soldiers']; ?></div>
        <div class="stat"><span class="stat-label">Построек:</span> <?php echo $buildings_count; ?></div>
        <div class="stat"><span class="stat-label">Сообщений в чате:</span> <?php echo $messages_count; ?></div>
        <button id="back_btn">← Назад</button>
    </div>
    <script>
        document.getElementById('back_btn').onclick = function() {
            window.history.back();
        };
    </script>
</body>
</html> 