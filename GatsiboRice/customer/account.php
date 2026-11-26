
<?php
require '../config/config.php';

$u = require_role(['customer']);

$stmt = $pdo->prepare(
    'SELECT * FROM customers WHERE user_id = ? LIMIT 1'
);
$stmt->execute([$u['id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    exit('Customer profile not found.');
}

$page_title = 'My Account';
require 'sidebar.php';
?>

<h1>My Account</h1>
<p>View your registered customer information.</p>

<div style="background:white;padding:25px;border-radius:10px;max-width:650px;box-shadow:0 2px 8px #ddd">

    <h2>Customer Information</h2>

    <p><strong>Full Name:</strong>
        <?= e($customer['name']) ?>
    </p>

    <p><strong>Email:</strong>
        <?= e($customer['email']) ?>
    </p>

    <p><strong>Phone:</strong>
        <?= e($customer['phone'] ?? 'Not provided') ?>
    </p>

    <p><strong>Address:</strong>
        <?= e($customer['address'] ?? 'Not provided') ?>
    </p>

    <p><strong>Account Status:</strong> Active</p>

</div>

</main>
</div>
</div>
</body>
</html>
