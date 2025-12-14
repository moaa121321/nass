<?php
session_start();
$error = '';

if (!isset($_SESSION['captcha'])) {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['captcha'] = $num1 + $num2;
    $_SESSION['captcha_num1'] = $num1;
    $_SESSION['captcha_num2'] = $num2;
} else {
    $num1 = $_SESSION['captcha_num1'];
    $num2 = $_SESSION['captcha_num2'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = intval($_POST['captcha'] ?? 0);
    if ($captcha !== $_SESSION['captcha']) {
        $error = 'Captcha is incorrect.';
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $_SESSION['captcha'] = $num1 + $num2;
        $_SESSION['captcha_num1'] = $num1;
        $_SESSION['captcha_num2'] = $num2;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } else {
        try {
            $pdo = require __DIR__ . '/config.php';
            $stmt = $pdo->prepare('SELECT username, password FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && password_verify($password, $row['password'])) {
                $_SESSION['user'] = $row['username'];
                unset($_SESSION['captcha'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);
                header('Location: index.php');
                exit;
            }
            $error = 'Email or password is incorrect.';
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<main class="auth container">
    <h2>Log In</h2>
    <?php if ($error): ?><div class="errors"><p><?php echo htmlspecialchars($error); ?></p></div><?php endif; ?>
    <form method="post" action="login.php" class="form">
        <label>Email<br><input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"></label>
        <label>Password<br><input type="password" name="password"></label>
        <label>Captcha: <?php echo $num1; ?> + <?php echo $num2; ?> = <input type="number" name="captcha" required></label>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
</main>
</body>
</html>
