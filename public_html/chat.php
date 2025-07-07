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

// Создать таблицу сообщений, если нет
$pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
    username VARCHAR(64),
    first_name VARCHAR(64),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Обработка отправки сообщения (POST ajax)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['message'])) {
    $user_id = intval($_POST['user_id']);
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
    $message = trim($_POST['message']);
    if ($user_id && $message) {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, username, first_name, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $username, $first_name, $message]);
    }
    exit;
}

// Получить последние 50 сообщений
$stmt = $pdo->query("SELECT * FROM chat_messages ORDER BY id DESC LIMIT 50");
$messages = array_reverse($stmt->fetchAll());

// Если это ajax-запрос, возвращаем только HTML сообщений
if (isset($_GET['ajax'])) {
    echo '<div id="messages">';
    foreach($messages as $msg) {
        echo '<div class="msg">';
        echo '<span class="msg-user"><a href="profile.php?user_id=' . $msg['user_id'] . '" style="color:#2678b6;text-decoration:underline;">' . htmlspecialchars($msg['first_name'] ?: $msg['username'] ?: 'Гость') . '</a></span>';
        echo '<span class="msg-time">' . date('H:i', strtotime($msg['created_at'])) . '</span>';
        echo '<div class="msg-text">' . nl2br(htmlspecialchars($msg['message'])) . '</div>';
        echo '</div>';
    }
    echo '</div>';
    exit;
}
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Чат</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        #chat_wrap { max-width: 480px; margin: 32px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px 0 rgba(60,60,120,0.10); padding: 24px 18px 18px 18px; }
        #messages { max-height: 340px; overflow-y: auto; margin-bottom: 18px; }
        .msg { margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f0f0f0; }
        .msg-user { font-weight: 600; color: #2678b6; }
        .msg-time { font-size: 0.9em; color: #aaa; margin-left: 8px; }
        .msg-text { margin-top: 2px; }
        #chat_form { display: flex; gap: 8px; }
        #chat_input { flex: 1; padding: 8px; border-radius: 7px; border: 1px solid #ccc; font-size: 1em; }
        #chat_send { padding: 8px 18px; border-radius: 7px; border: none; background: #4caf50; color: #fff; font-size: 1em; cursor: pointer; }
        #back_btn { margin-bottom: 18px; background: #eee; border: none; border-radius: 7px; padding: 7px 18px; cursor: pointer; }
    </style>
    <script src="https://telegram.org/js/telegram-web-app.js?1"></script>
</head>
<body>
    <div id="chat_wrap">
        <button id="back_btn">← Назад</button>
        <div id="messages">
            <?php foreach($messages as $msg): ?>
                <div class="msg">
                    <span class="msg-user"><a href="profile.php?user_id=<?php echo $msg['user_id']; ?>" style="color:#2678b6;text-decoration:underline;"><?php echo htmlspecialchars($msg['first_name'] ?: $msg['username'] ?: 'Гость'); ?></a></span>
                    <span class="msg-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                    <div class="msg-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <form id="chat_form" autocomplete="off">
            <input type="text" id="chat_input" placeholder="Сообщение..." maxlength="500" required />
            <button type="submit" id="chat_send">Отправить</button>
        </form>
    </div>
    <script>
        // Получить данные пользователя из Telegram WebApp
        var tgUser = (window.DemoApp && DemoApp.initDataUnsafe.user) || (Telegram.WebApp.initDataUnsafe && Telegram.WebApp.initDataUnsafe.user) || {};
        
        // Функция автоскролла к последнему сообщению
        function scrollToBottom() {
            var messagesDiv = document.getElementById('messages');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        // Функция загрузки сообщений без перезагрузки страницы
        function loadMessages() {
            fetch('chat.php?ajax=1')
                .then(response => response.text())
                .then(html => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var newMessages = doc.getElementById('messages').innerHTML;
                    document.getElementById('messages').innerHTML = newMessages;
                    scrollToBottom();
                });
        }
        
        document.getElementById('back_btn').onclick = function() {
            window.location.href = 'index.php';
        };
        
        document.getElementById('chat_form').onsubmit = function(e) {
            e.preventDefault();
            var msg = document.getElementById('chat_input').value.trim();
            if (!msg) return;
            var formData = new FormData();
            formData.append('user_id', tgUser.id || 0);
            formData.append('username', tgUser.username || '');
            formData.append('first_name', tgUser.first_name || '');
            formData.append('message', msg);
            fetch('chat.php', { method: 'POST', body: formData })
                .then(() => { 
                    document.getElementById('chat_input').value = ''; 
                    loadMessages(); // Обновляем сообщения без перезагрузки
                });
        };
        
        // Автоскролл при загрузке страницы
        window.addEventListener('load', scrollToBottom);
        
        // Периодическое обновление сообщений каждые 3 секунды
        setInterval(loadMessages, 3000);
    </script>
</body>
</html> 