
<?php
require 'config/config.php';

$u = require_role(['sales']);

$page_title = 'Sales';
$msg = '';

$isSales = ($u['role'] === 'sales');
$isAdmin = ($u['role'] === 'admin');

/*
|--------------------------------------------------------------------------
| RECORD SALE - SALES STAFF ONLY
|--------------------------------------------------------------------------
| Admin can view sales history but cannot record sales.
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$isSales) {
        http_response_code(403);
        exit('Access denied. Admin has view-only access to sales.');
    }

    csrf_check();

    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = (float)($_POST['quantity'] ?? 0);
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $paymentStatus = $_POST['payment_status'] ?? '';

    $allowedPayments = ['paid', 'unpaid', 'partial'];

    if ($pid <= 0) {
        $msg = 'Please select a valid product.';

    } elseif ($qty <= 0) {
        $msg = 'Please enter a valid quantity.';

    } elseif (!in_array($paymentStatus, $allowedPayments, true)) {
        $msg = 'Invalid payment status.';

    } else {

        try {

            $pdo->beginTransaction();

            $s = $pdo->prepare("
                SELECT *
                FROM products
                WHERE id = ?
                FOR UPDATE
            ");

            $s->execute([$pid]);
            $p = $s->fetch();

            if (!$p || (float)$p['quantity'] < $qty) {
                throw new Exception(
                    'Product not found or insufficient stock.'
                );
            }

            if ($customerId > 0) {

                $checkCustomer = $pdo->prepare("
                    SELECT id
                    FROM customers
                    WHERE id = ?
                ");

                $checkCustomer->execute([$customerId]);

                if (!$checkCustomer->fetch()) {
                    throw new Exception('Selected customer does not exist.');
                }
            }

            $total = $qty * (float)$p['selling_price'];

            $s = $pdo->prepare("
                INSERT INTO sales
                (
                    customer_id,
                    product_id,
                    quantity,
                    unit_price,
                    total,
                    payment_status,
                    created_by
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $s->execute([
                $customerId > 0 ? $customerId : null,
                $pid,
                $qty,
                $p['selling_price'],
                $total,
                $paymentStatus,
                $u['id']
            ]);

            // Update product stock
            $s = $pdo->prepare("
                UPDATE products
                SET quantity = quantity - ?
                WHERE id = ?
            ");

            $s->execute([$qty, $pid]);

            // Record stock movement
            $s = $pdo->prepare("
                INSERT INTO stock_movements
                (
                    product_id,
                    movement_type,
                    quantity,
                    reference,
                    notes,
                    created_by
                )
                VALUES (?, 'out', ?, 'SALE', ?, ?)
            ");

            $s->execute([
                $pid,
                $qty,
                'Stock issued through sale',
                $u['id']
            ]);

            $pdo->commit();

            log_action('Sale recorded', money($total));

            $msg = 'Sale recorded successfully. Total: ' . money($total);

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $msg = $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD PRODUCTS AND CUSTOMERS
|--------------------------------------------------------------------------
*/

$products = [];
$customers = [];

if ($isSales) {

    $products = $pdo->query("
        SELECT id, name, unit, quantity, selling_price
        FROM products
        ORDER BY name
    ")->fetchAll();

    $customers = $pdo->query("
        SELECT id, name
        FROM customers
        ORDER BY name
    ")->fetchAll();
}

/*
|--------------------------------------------------------------------------
| SALES HISTORY - ADMIN AND SALES STAFF
|--------------------------------------------------------------------------
*/

$rows = $pdo->query("
    SELECT
        s.*,
        p.name AS product,
        c.name AS customer
    FROM sales s
    JOIN products p ON p.id = s.product_id
    LEFT JOIN customers c ON c.id = s.customer_id
    ORDER BY s.id DESC
    LIMIT 100
")->fetchAll();

require 'includes/header.php';
?>

<h1>Sales & Orders</h1>

<p class="muted">
    <?php if ($isAdmin): ?>
        View-only sales monitoring. You can review sales transactions and payment statuses.
    <?php else: ?>
        Record company sales and review sales history.
    <?php endif; ?>
</p>

<?php if ($msg): ?>
    <div class="panel">
        <?= e($msg) ?>
    </div>
<?php endif; ?>


<?php if ($isSales): ?>

<!-- SALES STAFF ONLY: RECORD SALE -->

<div class="panel">

    <h2>Record Sale</h2>

    <?php if (!$products): ?>

        <p>No products are currently available.</p>

    <?php else: ?>

        <form method="post">

            <?php csrf_field(); ?>

            <div class="formgrid">

                <div>
                    <label>Product</label>

                    <select name="product_id" required>

                        <option value="">Select product</option>

                        <?php foreach ($products as $p): ?>

                            <option value="<?= (int)$p['id'] ?>">

                                <?= e(
                                    $p['name'] .
                                    ' · available ' .
                                    $p['quantity'] . ' ' .
                                    $p['unit'] . ' · ' .
                                    money($p['selling_price'])
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label>Customer (optional)</label>

                    <select name="customer_id">

                        <option value="">Walk-in customer</option>

                        <?php foreach ($customers as $c): ?>

                            <option value="<?= (int)$c['id'] ?>">
                                <?= e($c['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label>Quantity</label>

                    <input
                        type="number"
                        name="quantity"
                        min="0.01"
                        step="0.01"
                        required
                    >
                </div>

                <div>
                    <label>Payment Status</label>

                    <select name="payment_status" required>

                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="partial">Partial</option>

                    </select>
                </div>

            </div>

            <p>
                <button type="submit" class="btn">
                    Save Sale
                </button>
            </p>

        </form>

    <?php endif; ?>

</div>

<?php endif; ?>


<!-- SALES HISTORY: ADMIN AND SALES STAFF -->

<div class="panel">

    <h2>Sales History</h2>

    <?php if ($isAdmin): ?>
        <p class="muted">
            This section is view-only for Admin. Sales transactions cannot be created, edited or deleted here.
        </p>
    <?php endif; ?>

    <div class="tablewrap">

        <table>

            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Payment</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$rows): ?>

                    <tr>
                        <td colspan="6">No sales records found.</td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <td><?= e($r['created_at']) ?></td>

                            <td><?= e($r['product']) ?></td>

                            <td>
                                <?= e($r['customer'] ?? 'Walk-in') ?>
                            </td>

                            <td><?= e($r['quantity']) ?></td>

                            <td><?= money($r['total']) ?></td>

                            <td><?= e($r['payment_status']) ?></td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require 'includes/footer.php'; ?>
