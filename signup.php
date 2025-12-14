<?php
session_start();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $address = trim($_POST['address'] ?? '');
    if ($username === '') $errors[] = 'Kullanıcı adı girin.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
    if (strlen($password) < 6) $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    if ($password !== $confirm) $errors[] = 'Şifreler eşleşmiyor.';
    if (empty($errors)) {
        try {
            $pdo = require __DIR__ . '/config.php';
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Bu e-posta zaten kayıtlı.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE ip_address = ?');
                $stmt->execute([$ip]);
                if ($stmt->fetchColumn() >= 2) {
                    $errors[] = 'Bu IP adresinden maksimum hesap sayısı aşıldı.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare('INSERT INTO users (username, email, password, address, ip_address) VALUES (?, ?, ?, ?, ?)');
                    $ins->execute([$username, $email, $hash, $address ?: null, $ip]);
                    $_SESSION['user'] = $username;
                    header('Location: index.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<main class="auth container">
    <h2>Kayıt Ol</h2>
    <?php if ($errors): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="signup.php" class="form">
        <label>Kullanıcı Adı<br><input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"></label>
        <label>E-posta<br><input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"></label>
        <label>Şifre<br><input type="password" name="password"></label>
        <label>Şifre (tekrar)<br><input type="password" name="confirm"></label>
        <button type="submit">Kayıt Ol</button>
    </form>
    <p>Zaten hesabınız var mı? <a href="login.php">Giriş yap</a></p>
</main>
</body>
</html>
