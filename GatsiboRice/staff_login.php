
<?php
require 'config/config.php';

if (user()) {
    if (user()['role'] === 'customer') {
        go('customer/dashboard.php');
    }

    go('index.php');
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users
         WHERE email = ? AND status = 'active'
         AND role != 'customer'"
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

        log_action('Staff Login', 'Staff member signed in');

        if ($u['role'] === 'employee') {
            go('employee/dashboard.php');
        }

        if ($u['role'] === 'product_manager') {
            go('product_manager/dashboard.php');
        }

        go('index.php');

    } else {
        $err = 'Invalid staff email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Login | Gatsibo Rice</title>
<link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="login">
<div class="panel">

    <h1>🌾 Gatsibo Rice</h1>

    <p class="muted">Company Administration Portal</p>

    <h2>Staff & Admin Sign In</h2>

    <?php if ($err): ?>
        <div class="alert"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="POST" action="staff_login.php">

        <?php csrf_field(); ?>

        <label>Staff Email Address</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <p>
            <button type="submit" class="btn">
                Staff Sign In
            </button>
        </p>

    </form>

    <p>
        <a href="index.php">Return to homepage</a>
    </p>

    <hr>

    <p>
        Are you a customer?
        <a href="login.php">Customer Login</a>
    </p>

</div>
</div>

</body>
</html>
