
<?php
require '../config/config.php';

$u = require_role(['customer']);

$stmt = $pdo->prepare(
    'SELECT id, name FROM customers WHERE user_id = ? LIMIT 1'
);
$stmt->execute([$u['id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    exit('Customer profile not found.');
}

$customerId = (int)$customer['id'];
$error = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (float)($_POST['quantity'] ?? 0);
    $requestedPrice = (float)($_POST['requested_price'] ?? 0);
    $notes = trim($_POST['customer_notes'] ?? '');

    $check = $pdo->prepare(
        'SELECT id, quantity FROM products
         WHERE id = ? AND quantity > 0'
    );
    $check->execute([$productId]);
    $product = $check->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $error = 'Please select an available product.';
    } elseif ($quantity <= 0) {
        $error = 'Enter a valid order quantity.';
    } elseif ($quantity > (float)$product['quantity']) {
        $error = 'Requested quantity exceeds available stock.';
    } elseif ($requestedPrice <= 0) {
        $error = 'Enter a valid proposed price.';
    } elseif (strlen($notes) > 2000) {
        $error = 'Your notes are too long.';
    } else {
        $insert = $pdo->prepare(
            "INSERT INTO customer_orders
             (customer_id, product_id, quantity, requested_price,
              status, customer_notes)
             VALUES (?, ?, ?, ?, 'Pending', ?)"
        );

        $insert->execute([
            $customerId,
            $productId,
            $quantity,
            $requestedPrice,
            $notes !== '' ? $notes : null
        ]);

        header('Location: orders.php?submitted=1');
        exit;
    }
}

if (isset($_GET['submitted'])) {
    $msg = 'Your order request was submitted successfully.';
}

$products = $pdo->query(
    "SELECT id, name, unit, quantity, selling_price
     FROM products
     WHERE quantity > 0
     ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$ordersStmt = $pdo->prepare(
    "SELECT o.*, p.name AS product_name, p.unit
     FROM customer_orders o
     INNER JOIN products p ON p.id = o.product_id
     WHERE o.customer_id = ?
     ORDER BY o.created_at DESC"
);
$ordersStmt->execute([$customerId]);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'My Orders';
require 'sidebar.php';
?>

<style>
.orders-header {
    background: linear-gradient(120deg, #123d2b, #28754d);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.order-panel {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
}

.order-panel label {
    display: block;
    font-weight: bold;
    margin: 14px 0 7px;
}

.order-panel input,
.order-panel select,
.order-panel textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
}

.order-panel button {
    background: #17643b;
    color: white;
    border: 0;
    padding: 13px 22px;
    border-radius: 7px;
    margin-top: 18px;
    cursor: pointer;
}

.order-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 17px;
}

.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    background: #e2e8f0;
    font-weight: bold;
    font-size: 13px;
}

.status-pending,
.status-negotiating {
    background: #fef3c7;
    color: #92400e;
}

.status-approved,
.status-completed {
    background: #dcfce7;
    color: #166534;
}

.status-rejected,
.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.notice {
    padding: 13px;
    border-radius: 7px;
    margin-bottom: 15px;
    background: #dcfce7;
    color: #166534;
}
</style>

<div class="orders-header">
    <h1>📦 My Orders</h1>
    <p>
        Submit rice order requests and track your negotiations
        and order progress.
    </p>
</div>

<?php if ($msg): ?>
    <div class="notice"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#fee2e2;padding:13px;border-radius:7px;margin-bottom:15px">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="order-panel">
    <h2>Submit a New Order</h2>

    <form method="post">
        <?= csrf_field() ?>

        <label>Select Rice Product</label>

        <select name="product_id" required>
            <option value="">Choose a product</option>

            <?php foreach ($products as $product): ?>
                <option value="<?= (int)$product['id'] ?>">
                    <?= e($product['name']) ?>
                    - <?= number_format((float)$product['selling_price'], 2) ?>
                    RWF / <?= e($product['unit']) ?>
                    (Stock: <?= number_format((float)$product['quantity'], 2) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label>Quantity Required</label>
        <input
            type="number"
            name="quantity"
            min="0.01"
            step="0.01"
            placeholder="Enter quantity"
            required
        >

        <label>Your Proposed Price (RWF per unit)</label>
        <input
            type="number"
            name="requested_price"
            min="0.01"
            step="0.01"
            placeholder="Enter your proposed price"
            required
        >

        <label>Additional Notes</label>
        <textarea
            name="customer_notes"
            rows="4"
            maxlength="2000"
            placeholder="Describe your order requirements..."
        ></textarea>

        <button type="submit">Submit Order Request</button>
    </form>
</div>

<div class="order-panel">
    <h2>My Order History</h2>

    <?php if (!$orders): ?>

        <p>You have not submitted any orders yet.</p>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <?php
            $statusClass = strtolower($order['status']);
            ?>

            <div class="order-item">

                <h3>Order #<?= (int)$order['id'] ?></h3>

                <p>
                    <strong>Product:</strong>
                    <?= e($order['product_name']) ?>
                </p>

                <p>
                    <strong>Quantity:</strong>
                    <?= number_format((float)$order['quantity'], 2) ?>
                    <?= e($order['unit']) ?>
                </p>

                <p>
                    <strong>Requested price:</strong>
                    <?= number_format((float)$order['requested_price'], 2) ?>
                    RWF
                </p>

                <p>
                    <strong>Agreed price:</strong>
                    <?= $order['agreed_price'] !== null
                        ? number_format((float)$order['agreed_price'], 2) . ' RWF'
                        : 'Not yet agreed' ?>
                </p>

                <p>
                    <strong>Total amount:</strong>
                    <?= $order['total_amount'] !== null
                        ? number_format((float)$order['total_amount'], 2) . ' RWF'
                        : 'Not yet calculated' ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <span class="status status-<?= e($statusClass) ?>">
                        <?= e($order['status']) ?>
                    </span>
                </p>

                <p>
                    <strong>Your notes:</strong><br>
                    <?= nl2br(e($order['customer_notes'] ?? 'None')) ?>
                </p>

                <p>
                    <strong>Product Manager's response:</strong><br>
                    <?= nl2br(e($order['manager_notes'] ?? 'Awaiting response')) ?>
                </p>

                <small>
                    Submitted: <?= e($order['created_at']) ?>
                </small>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>
</div>

</main>
</div>
</div>
</body>
</html>
