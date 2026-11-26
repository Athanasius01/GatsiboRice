
<?php
require '../config/config.php';

$u = require_role(['product_manager']);

$page_title = 'Customer Order Management';
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $agreedPriceInput = trim($_POST['agreed_price'] ?? '');
    $managerNotes = trim($_POST['manager_notes'] ?? '');

    $allowedStatuses = ['Negotiating', 'Approved', 'Rejected'];

    if ($orderId < 1 || !in_array($status, $allowedStatuses, true)) {
        $error = 'Invalid order information.';
    } elseif (
        $agreedPriceInput !== '' &&
        (!is_numeric($agreedPriceInput) || (float)$agreedPriceInput <= 0)
    ) {
        $error = 'Please enter a valid agreed price.';
    } elseif (strlen($managerNotes) > 2000) {
        $error = 'Manager response is too long.';
    } elseif ($status === 'Approved' && $agreedPriceInput === '') {
        $error = 'Enter the agreed price before approving the order.';
    } else {
        $check = $pdo->prepare(
            "SELECT o.id, o.quantity, o.status,
                    p.quantity AS available_stock
             FROM customer_orders o
             INNER JOIN products p ON p.id = o.product_id
             WHERE o.id = ?"
        );
        $check->execute([$orderId]);
        $order = $check->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $error = 'Order not found.';
        } elseif ($order['status'] === 'Completed' || $order['status'] === 'Cancelled') {
            $error = 'This order can no longer be changed.';
        } elseif (
            $status === 'Approved' &&
            (float)$order['quantity'] > (float)$order['available_stock']
        ) {
            $error = 'There is not enough stock to approve this order.';
        } else {
            $price = $agreedPriceInput === ''
                ? null
                : (float)$agreedPriceInput;

            $total = $price === null
                ? null
                : (float)$order['quantity'] * $price;

            $update = $pdo->prepare(
                "UPDATE customer_orders
                 SET agreed_price = ?,
                     total_amount = ?,
                     manager_notes = ?,
                     status = ?,
                     reviewed_by = ?,
                     reviewed_at = NOW()
                 WHERE id = ?"
            );

            $update->execute([
                $price,
                $total,
                $managerNotes !== '' ? $managerNotes : null,
                $status,
                $u['id'],
                $orderId
            ]);

            $msg = 'Order #' . $orderId . ' updated successfully.';
        }
    }
}

$orders = $pdo->query(
    "SELECT o.*,
            c.name AS customer_name,
            c.email AS customer_email,
            c.phone AS customer_phone,
            c.address AS customer_address,
            p.name AS product_name,
            p.unit,
            p.quantity AS available_stock
     FROM customer_orders o
     INNER JOIN customers c ON c.id = o.customer_id
     INNER JOIN products p ON p.id = o.product_id
     ORDER BY o.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

require '../includes/header.php';
?>

<style>
.order-heading {
    background: linear-gradient(120deg, #123d2b, #28754d);
    color: white;
    padding: 28px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.manager-order {
    background: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
}

.manager-order h2 {
    color: #174b32;
}

.manager-order label {
    display: block;
    font-weight: bold;
    margin: 14px 0 7px;
}

.manager-order input,
.manager-order select,
.manager-order textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
}

.manager-order button {
    background: #17643b;
    color: white;
    border: 0;
    padding: 12px 22px;
    border-radius: 7px;
    cursor: pointer;
    margin-top: 15px;
}

.order-status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    background: #e2e8f0;
    font-weight: bold;
}

.pending {
    background: #fef3c7;
    color: #92400e;
}

.approved {
    background: #dcfce7;
    color: #166534;
}

.rejected {
    background: #fee2e2;
    color: #991b1b;
}
</style>

<div class="order-heading">
    <h1>📦 Customer Order Management</h1>
    <p>
        Review customer orders, negotiate prices and manage
        order approval.
    </p>
</div>

<?php if ($msg): ?>
    <div class="alert success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<h2>Customer Orders (<?= count($orders) ?>)</h2>

<?php if (!$orders): ?>

    <div class="panel">
        <p>No customer orders have been submitted yet.</p>
    </div>

<?php else: ?>

    <?php foreach ($orders as $order): ?>

        <div class="manager-order">

            <h2>Order #<?= (int)$order['id'] ?></h2>

            <p>
                <strong>Customer:</strong>
                <?= e($order['customer_name']) ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= e($order['customer_email']) ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?= e($order['customer_phone'] ?? 'Not provided') ?>
            </p>

            <p>
                <strong>Address:</strong>
                <?= e($order['customer_address'] ?? 'Not provided') ?>
            </p>

            <hr>

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
                <strong>Available stock:</strong>
                <?= number_format((float)$order['available_stock'], 2) ?>
                <?= e($order['unit']) ?>
            </p>

            <p>
                <strong>Customer proposed price:</strong>
                <?= number_format((float)$order['requested_price'], 2) ?> RWF
            </p>

            <p>
                <strong>Customer notes:</strong><br>
                <?= nl2br(e($order['customer_notes'] ?? 'None')) ?>
            </p>

            <p>
                <strong>Current status:</strong>
                <span class="order-status <?= strtolower($order['status']) ?>">
                    <?= e($order['status']) ?>
                </span>
            </p>

            <?php if (
                $order['status'] === 'Completed' ||
                $order['status'] === 'Cancelled'
            ): ?>

                <p><strong>This order is closed.</strong></p>

            <?php else: ?>

                <form method="post">
                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= (int)$order['id'] ?>"
                    >

                    <label>Agreed Price (RWF per <?= e($order['unit']) ?>)</label>

                    <input
                        type="number"
                        name="agreed_price"
                        min="0.01"
                        step="0.01"
                        value="<?= e($order['agreed_price'] ?? '') ?>"
                        placeholder="Enter negotiated price"
                    >

                    <label>Response to Customer</label>

                    <textarea
                        name="manager_notes"
                        rows="4"
                        maxlength="2000"
                        placeholder="Write your response or price offer..."
                    ><?= e($order['manager_notes'] ?? '') ?></textarea>

                    <label>Order Decision</label>

                    <select name="status" required>
                        <option value="Negotiating"
                            <?= $order['status'] === 'Negotiating' ? 'selected' : '' ?>>
                            Continue Negotiation
                        </option>

                        <option value="Approved"
                            <?= $order['status'] === 'Approved' ? 'selected' : '' ?>>
                            Approve Order
                        </option>

                        <option value="Rejected"
                            <?= $order['status'] === 'Rejected' ? 'selected' : '' ?>>
                            Reject Order
                        </option>
                    </select>

                    <button type="submit">
                        Save Order Decision
                    </button>
                </form>

            <?php endif; ?>

            <?php if ($order['agreed_price'] !== null): ?>
                <p>
                    <strong>Agreed price:</strong>
                    <?= number_format((float)$order['agreed_price'], 2) ?> RWF
                </p>

                <p>
                    <strong>Total amount:</strong>
                    <?= number_format((float)$order['total_amount'], 2) ?> RWF
                </p>
            <?php endif; ?>

            <small>
                Submitted: <?= e($order['created_at']) ?>
            </small>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

<?php require '../includes/footer.php'; ?>
