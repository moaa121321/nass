<?php
session_start();
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
require __DIR__ . '/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Get all users who have chatted with admin
$chats = $pdo->query("
    SELECT DISTINCT sender_username AS username,
           (SELECT message FROM chat WHERE (sender_username = c.sender_username AND receiver_username = 'admin') OR (sender_username = 'admin' AND receiver_username = c.sender_username) ORDER BY created_at DESC LIMIT 1) AS last_message,
           (SELECT created_at FROM chat WHERE (sender_username = c.sender_username AND receiver_username = 'admin') OR (sender_username = 'admin' AND receiver_username = c.sender_username) ORDER BY created_at DESC LIMIT 1) AS last_time,
           (SELECT COUNT(*) FROM chat WHERE sender_username = c.sender_username AND receiver_username = 'admin' AND is_read = FALSE) AS unread
    FROM chat c
    WHERE receiver_username = 'admin' OR sender_username = 'admin'
    GROUP BY sender_username
    ORDER BY last_time DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Chat</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .chat-list { display: flex; flex-direction: column; }
        .chat-item { padding: 10px; border: 1px solid #ccc; margin: 5px; cursor: pointer; }
        .chat-item.unread { background: #ffe; }
        .chat-window { display: none; max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-top: 20px; }
        .message { margin: 5px 0; }
        .message.sent { text-align: right; }
        .message.received { text-align: left; }
        .message-input { width: 80%; padding: 5px; }
        .send-btn { padding: 5px 10px; }
    </style>
</head>
<body>
<div class="bg-anim" aria-hidden="true"></div>
<header class="site-header">
    <nav class="nav-left">
        <div class="menu-wrap left">
            <button id="menuBtn" class="menu-button" aria-label="Menu">⋮</button>
            <div id="menuDropdown" class="menu-dropdown" aria-hidden="true">
                <a href="index.php">Home</a>
                <a href="admin.php">Admin Panel</a>
                <a href="notifications.php">Notifications</a>
                <a href="?action=logout">Log Out</a>
            </div>
        </div>
    </nav>
</header>

<main class="container">
    <h2>Chat with Users</h2>
    <div class="chat-list">
        <?php foreach ($chats as $chat): ?>
            <div class="chat-item <?php if ($chat['unread'] > 0) echo 'unread'; ?>" data-user="<?php echo htmlspecialchars($chat['username']); ?>">
                <strong><?php echo htmlspecialchars($chat['username']); ?></strong>
                <p><?php echo htmlspecialchars(substr($chat['last_message'], 0, 50)); ?>...</p>
                <small><?php echo htmlspecialchars($chat['last_time']); ?></small>
                <?php if ($chat['unread'] > 0): ?><span>(<?php echo $chat['unread']; ?> unread)</span><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="chatWindow" class="chat-window">
        <h3 id="chatUser"></h3>
        <div id="messages"></div>
        <form id="messageForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" id="receiver" name="receiver" value="">
            <input type="text" id="messageInput" name="message" class="message-input" placeholder="Type message..." required>
            <button type="submit" class="send-btn">Send</button>
        </form>
    </div>
</main>

<script>
document.addEventListener('click', function(e){
    var btn = document.getElementById('menuBtn');
    var dd = document.getElementById('menuDropdown');
    if (!btn) return;
    if (btn.contains(e.target)) {
        var hidden = dd.getAttribute('aria-hidden') === 'true';
        dd.style.display = hidden ? 'block' : 'none';
        dd.setAttribute('aria-hidden', hidden ? 'false' : 'true');
    } else {
        if (!dd.contains(e.target)) {
            dd.style.display = 'none';
            dd.setAttribute('aria-hidden','true');
        }
    }
});

var currentChatUser = null;
var messagePoll = null;
var chatsPoll = null;

function attachChatItemHandlers() {
    document.querySelectorAll('.chat-item').forEach(function(item){
        item.addEventListener('click', function(){
            var user = this.getAttribute('data-user');
            openChat(user);
        });
    });
}

attachChatItemHandlers();

function openChat(user) {
    currentChatUser = user;
    document.getElementById('chatUser').textContent = 'Chat with ' + user;
    document.getElementById('receiver').value = user;
    document.getElementById('chatWindow').style.display = 'block';
    loadMessages(user);
    if (messagePoll) clearInterval(messagePoll);
    messagePoll = setInterval(function(){ loadMessages(user); }, 2000);
}

function loadMessages(otherUser) {
    fetch('get_messages.php?other=' + encodeURIComponent(otherUser) + '&_=' + Date.now())
    .then(response => response.json())
    .then(data => {
        var messagesDiv = document.getElementById('messages');
        messagesDiv.innerHTML = '';
        data.messages.forEach(function(msg){
            var div = document.createElement('div');
            div.className = 'message ' + (msg.sender_username === 'admin' ? 'sent' : 'received');
            div.textContent = msg.message;
            messagesDiv.appendChild(div);
        });
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    });
}

function updateChatList() {
    fetch('get_chats.php?_=' + Date.now())
    .then(response => response.json())
    .then(data => {
        if (!data.chats) return;
        var container = document.querySelector('.chat-list');
        container.innerHTML = '';
        data.chats.forEach(function(chat){
            var div = document.createElement('div');
            div.className = 'chat-item ' + (chat.unread > 0 ? 'unread' : '');
            div.setAttribute('data-user', chat.username);
            div.innerHTML = '<strong>' + escapeHtml(chat.username) + '</strong>' +
                            '<p>' + escapeHtml((chat.last_message||'').substring(0,50)) + '...</p>' +
                            '<small>' + escapeHtml(chat.last_time||'') + '</small>' +
                            (chat.unread > 0 ? '<span>(' + chat.unread + ' unread)</span>' : '');
            container.appendChild(div);
        });
        attachChatItemHandlers();
    });
}

// Start periodic chat list polling
chatsPoll = setInterval(updateChatList, 3000);
updateChatList();

function escapeHtml(s) {
    return s ? s.replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }) : '';
}

document.getElementById('messageForm').addEventListener('submit', function(e){
    e.preventDefault();
    var formData = new FormData(this);
    fetch('send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('messageInput').value = '';
            loadMessages(document.getElementById('receiver').value);
        } else {
            alert(data.error);
        }
    });
});
// Floating background blobs (same behavior as index)
(function(){
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var container = document.querySelector('.bg-anim');
    if (!container) return;
    var colors = ['#4cc9f0','#ffd166','#ef476f','#06d6a0','#b892ff'];
    var count = Math.min(10, Math.max(5, Math.floor(window.innerWidth / 160)));
    for (var i=0;i<count;i++){
        var el = document.createElement('div');
        el.className = 'blob ' + (Math.random() > 0.75 ? 'small' : '');
        var size = Math.round(60 + Math.random() * 160);
        el.style.width = size + 'px';
        el.style.height = size + 'px';
        el.style.left = Math.round(Math.random() * 100) + '%';
        el.style.top = Math.round(Math.random() * 100) + '%';
        var c = colors[Math.floor(Math.random()*colors.length)];
        el.style.background = 'radial-gradient(circle at 30% 30%, '+c+'33, rgba(255,255,255,0.02) 60%)';
        var dur = 12 + Math.random() * 16;
        var dx = (Math.random()*40 - 20) + 'px';
        var dy = (Math.random()*40 - 10) + 'px';
        el.style.setProperty('--tx', dx);
        el.style.setProperty('--ty', dy);
        el.style.setProperty('--s2', (1 + Math.random()*0.06).toFixed(3));
        el.style.animationDuration = dur + 's';
        el.style.animationDelay = (-Math.random()*dur) + 's';
        el.style.opacity = 0.32 + Math.random()*0.6;
        container.appendChild(el);
    }
})();
</script>
</body>
</html>