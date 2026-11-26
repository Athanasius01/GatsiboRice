
<?php
require 'config/config.php';

if (user()) {
    if (user()['role'] === 'customer') {
        go('customer/dashboard.php');
    }

    go('staff_login.php');
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users
         WHERE email = ? AND status = 'active'
         AND role = 'customer'"
    );

    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $u['id'],
            'name' => $u['name'],
            'email' => $u['email'],
            'role' => $u['role'],
            'employee_id' => $u['employee_id'] ?? null
        ];

        log_action('Customer Login', 'Customer signed in');

        go('customer/dashboard.php');

    } else {
        $err = 'Invalid customer email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Login | Gatsibo Rice</title>
<link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="login">
<div class="panel">

    <h1>🌾 Gatsibo Rice</h1>

    <p class="muted">Customer Portal</p>

    <h2>Customer Sign In</h2>

    <?php if ($err): ?>
        <div class="alert"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <?php csrf_field(); ?>

        <label>Email address</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <p>
            <button type="submit" class="btn">
                Customer Sign In
            </button>
        </p>

    </form>

    <p>
        New customer?
        <a href="register.php">Create an account</a>
    </p>

    <p>
        <a href="index.php">Return to homepage</a>
    </p>

    <hr>

    <p>
        Are you an employee or administrator?
        <a href="staff_login.php">Staff & Admin Login</a>
    </p>

</div>
</div>

</body>
</html>
