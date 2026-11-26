
<?php
require '../config/config.php';

$u = require_role(['product_manager']);

$page_title = 'Product Manager Workspace';

$totalProducts = (int)$pdo->query(
    'SELECT COUNT(*) FROM products'
)->fetchColumn();

$totalMessages = (int)$pdo->query(
    "SELECT COUNT(*) FROM customer_messages WHERE status = 'New'"
)->fetchColumn();

$totalCustomers = (int)$pdo->query(
    'SELECT COUNT(*) FROM customers'
)->fetchColumn();

require '../includes/header.php';
?>

<h1>Product Manager Workspace</h1>
<p class="muted">
    Welcome, <?= e($u['name']) ?>. Manage products and customer enquiries.
</p>

<div class="grid">
    <div class="panel">
        <h2><?= $totalProducts ?></h2>
        <p>Products in Catalogue</p>
    </div>

    <div class="panel">
        <h2><?= $totalMessages ?></h2>
        <p>New Customer Messages</p>
    </div>

    <div class="panel">
        <h2><?= $totalCustomers ?></h2>
        <p>Registered Customers</p>
    </div>
</div>

<div class="panel">
    <h2>Workspace Activities</h2>

    <p>
        <a class="btn" href="messages.php">
            Customer Messages & Negotiations
        </a>
    </p>

    <p>
        <a class="btn" href="orders.php">
            Manage Customer Orders
        </a>
    </p>

    <p>
        <a class="btn" href="../products.php">
            View Company Products
        </a>
    </p>
</div>

<?php require '../includes/footer.php'; ?>
