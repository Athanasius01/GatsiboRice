
<?php
require 'config/config.php';

$page_title = 'Customer Registration';
$msg = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $msg = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $msg = 'Password must contain at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $msg = 'Passwords do not match.';
    } else {
        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                throw new RuntimeException(
                    'An account with this email already exists.'
                );
            }

            $check = $pdo->prepare(
                "SELECT id FROM customers WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                throw new RuntimeException(
                    'This email is already linked to a customer record. Please contact the company.'
                );
            }

            $stmt = $pdo->prepare("
                INSERT INTO customers
                    (name, phone, email, address)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $phone,
                $email,
                $address
            ]);

            $customerId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO users
                    (name, email, password_hash, role, status, employee_id)
                VALUES (?, ?, ?, 'customer', 'active', NULL)
            ");

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $userId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("
                UPDATE customers
                SET user_id = ?
                WHERE id = ?
            ");

            $stmt->execute([$userId, $customerId]);

            $pdo->commit();

            $success = true;
            $msg = 'Your customer account has been created successfully. You can now sign in.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $msg = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Registration | Gatsibo Rice</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6f1;
            color: #26352b;
        }

        .topbar {
            background: #174d32;
            color: white;
            padding: 22px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .brand {
            font-size: 23px;
            font-weight: bold;
        }

        .topbar a {
            color: white;
            text-decoration: none;
            margin-left: 18px;
        }

        .main {
            width: 92%;
            max-width: 560px;
            margin: 45px auto;
        }

        .card {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 5px 22px rgba(0,0,0,0.08);
        }

        h1 {
            color: #174d32;
            margin-top: 0;
        }

        .intro {
            color: #68756b;
            line-height: 1.6;
        }

        label {
            display: block;
            margin-top: 17px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ccd5cd;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #83b795;
            border-color: #174d32;
        }

        button {
            width: 100%;
            background: #174d32;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 6px;
            margin-top: 24px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #226a43;
        }

        .message {
            padding: 13px;
            border-radius: 6px;
            background: #fff3cd;
            color: #765b12;
            margin: 18px 0;
            line-height: 1.5;
        }

        .success {
            background: #e1f3e5;
            color: #205b32;
        }

        .signin {
            text-align: center;
            margin-top: 23px;
        }

        .signin a {
            color: #174d32;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            color: #718075;
            padding: 20px;
        }

        @media(max-width: 500px) {
            .card {
                padding: 22px;
            }

            .topbar {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="brand">🌾 GATSIBO RICE</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Customer Login</a>
    </nav>
</header>

<main class="main">
    <div class="card">

        <h1>Create Customer Account</h1>

        <p class="intro">
            Welcome to Gatsibo Rice Co. Ltd.
            Register to explore our rice products, contact our
            product manager and submit purchase requests.
        </p>

        <?php if ($msg): ?>
            <div class="message <?= $success ? 'success' : '' ?>">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>

            <p class="signin">
                <a href="login.php">Sign in to your customer account</a>
            </p>

        <?php else: ?>

            <form method="post">
                <?= csrf_field() ?>

                <label>Full Name *</label>
                <input type="text" name="name" required
                       value="<?= e($_POST['name'] ?? '') ?>">

                <label>Phone Number</label>
                <input type="text" name="phone"
                       value="<?= e($_POST['phone'] ?? '') ?>">

                <label>Email Address *</label>
                <input type="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>">

                <label>Address</label>
                <input type="text" name="address"
                       value="<?= e($_POST['address'] ?? '') ?>">

                <label>Password *</label>
                <input type="password" name="password"
                       minlength="8" required>

                <label>Confirm Password *</label>
                <input type="password" name="confirm_password"
                       minlength="8" required>

                <button type="submit">
                    Create Customer Account
                </button>
            </form>

        <?php endif; ?>

        <div class="signin">
            Already have an account?
            <a href="login.php">Sign in</a>
        </div>

    </div>
</main>

<footer class="footer">
    © <?= date('Y') ?> Gatsibo Rice Co. Ltd. All rights reserved.
</footer>

</body>
</html>
