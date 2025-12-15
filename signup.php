<?php
session_start();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $address = trim($_POST['address'] ?? '');
    if ($username === '') $errors[] = 'Please enter a username.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (empty($errors)) {
        try {
            $pdo = require __DIR__ . '/config.php';
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'This email is already registered.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE ip_address = ?');
                $stmt->execute([$ip]);
                if ($stmt->fetchColumn() >= 2) {
                    $errors[] = 'Maximum account limit per IP exceeded.';
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
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign Up</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<main class="auth container">
    <h2>Sign Up</h2>
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
        <label>Username<br><input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"></label>
        <label>Email<br><input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"></label>
        <label>Password<br><input type="password" name="password"></label>
        <label>Confirm Password<br><input type="password" name="confirm"></label>
        <button type="submit">Sign Up</button>
    </form>
    <p>Already have an account? <a href="login.php">Log in</a></p>
</main>
</body>
</html>
