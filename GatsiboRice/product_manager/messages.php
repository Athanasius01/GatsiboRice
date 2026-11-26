
<?php
require '../config/config.php';

$u = require_role(['product_manager']);

$msg = '';
$error = '';

$customer_id = (int)($_GET['customer_id'] ?? 0);
$product_id = (int)($_GET['product_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($customer_id < 1 || $message === '') {
        $error = 'Please enter a reply.';
    } else {
        $check = $pdo->prepare(
            'SELECT id FROM customers WHERE id = ?'
        );
        $check->execute([$customer_id]);

        if (!$check->fetch()) {
            $error = 'Customer not found.';
        } else {
            if ($product_id > 0) {
                $check = $pdo->prepare(
                    'SELECT id FROM products WHERE id = ?'
                );
                $check->execute([$product_id]);

                if (!$check->fetch()) {
                    $error = 'Product not found.';
                }
            }

            if (!$error) {
                $stored_product_id = $product_id > 0
                    ? $product_id
                    : null;

                $s = $pdo->prepare(
                    "INSERT INTO customer_messages
                    (customer_id, product_id, sender_role, message, status)
                    VALUES (?, ?, 'product_manager', ?, 'Replied')"
                );

                $s->execute([
                    $customer_id,
                    $stored_product_id,
                    $message
                ]);

                if ($product_id > 0) {
                    $update = $pdo->prepare(
                        "UPDATE customer_messages
                         SET status = 'Replied'
                         WHERE customer_id = ?
                         AND product_id = ?
                         AND sender_role = 'customer'"
                    );
                    $update->execute([$customer_id, $product_id]);
                } else {
                    $update = $pdo->prepare(
                        "UPDATE customer_messages
                         SET status = 'Replied'
                         WHERE customer_id = ?
                         AND product_id IS NULL
                         AND sender_role = 'customer'"
                    );
                    $update->execute([$customer_id]);
                }

                log_action(
                    'Customer message reply',
                    'Customer ID: ' . $customer_id
                );

                header(
                    'Location: messages.php?customer_id=' .
                    $customer_id . '&product_id=' . $product_id
                );
                exit;
            }
        }
    }
}

if ($customer_id > 0) {
    if ($product_id > 0) {
        $update = $pdo->prepare(
            "UPDATE customer_messages
             SET status = 'Read'
             WHERE customer_id = ?
             AND product_id = ?
             AND sender_role = 'customer'
             AND status = 'New'"
        );
        $update->execute([$customer_id, $product_id]);
    } else {
        $update = $pdo->prepare(
            "UPDATE customer_messages
             SET status = 'Read'
             WHERE customer_id = ?
             AND product_id IS NULL
             AND sender_role = 'customer'
             AND status = 'New'"
        );
        $update->execute([$customer_id]);
    }

    $customerQuery = $pdo->prepare(
        'SELECT name, email FROM customers WHERE id = ?'
    );
    $customerQuery->execute([$customer_id]);
    $customer = $customerQuery->fetch();

    if ($customer) {
        if ($product_id > 0) {
            $threadQuery = $pdo->prepare(
                'SELECT m.*, p.name AS product_name
                 FROM customer_messages m
                 LEFT JOIN products p ON p.id = m.product_id
                 WHERE m.customer_id = ?
                 AND m.product_id = ?
                 ORDER BY m.created_at ASC, m.id ASC'
            );
            $threadQuery->execute([$customer_id, $product_id]);
        } else {
            $threadQuery = $pdo->prepare(
                'SELECT m.*, p.name AS product_name
                 FROM customer_messages m
                 LEFT JOIN products p ON p.id = m.product_id
                 WHERE m.customer_id = ?
                 AND m.product_id IS NULL
                 ORDER BY m.created_at ASC, m.id ASC'
            );
            $threadQuery->execute([$customer_id]);
        }

        $messages = $threadQuery->fetchAll();
    } else {
        $error = 'Customer not found.';
        $customer_id = 0;
    }
}

if ($customer_id === 0) {
    $inbox = $pdo->query(
        "SELECT m.*, c.name AS customer_name,
                c.email AS customer_email,
                p.name AS product_name
         FROM customer_messages m
         JOIN customers c ON c.id = m.customer_id
         LEFT JOIN products p ON p.id = m.product_id
         WHERE m.sender_role = 'customer'
         ORDER BY m.created_at DESC"
    )->fetchAll();
}

require '../includes/header.php';
?>

<h1>Customer Messages & Negotiations</h1>
<p class="muted">
    Respond to customer enquiries and discuss product prices.
</p>

<?php if ($error): ?>
    <div class="alert"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($customer_id > 0 && !empty($customer)): ?>

    <div class="panel">
        <h2>Conversation with <?= e($customer['name']) ?></h2>
        <p class="muted"><?= e($customer['email']) ?></p>

        <p>
            <a href="messages.php">← Back to Messages</a>
        </p>

        <?php foreach ($messages as $m): ?>
            <div class="panel">
                <strong>
                    <?= $m['sender_role'] === 'customer'
                        ? e($customer['name'])
                        : 'Product Manager' ?>
                </strong>

                <?php if ($m['product_name']): ?>
                    <p class="muted">
                        Product: <?= e($m['product_name']) ?>
                    </p>
                <?php endif; ?>

                <p><?= nl2br(e($m['message'])) ?></p>

                <small class="muted">
                    <?= e($m['created_at']) ?>
                </small>
            </div>
        <?php endforeach; ?>

        <h3>Send Reply</h3>

        <form method="post">
            <?php csrf_field(); ?>

            <input type="hidden" name="customer_id"
                   value="<?= $customer_id ?>">

            <input type="hidden" name="product_id"
                   value="<?= $product_id ?>">

            <label>Your message</label>
            <textarea name="message" rows="5"
                      required placeholder="Write your reply or price offer..."></textarea>

            <button type="submit">Send Reply</button>
        </form>
    </div>

<?php else: ?>

    <div class="panel">
        <h2>Customer Inbox</h2>

        <?php if (!$inbox): ?>
            <p class="muted">No customer messages yet.</p>
        <?php else: ?>

            <div class="tablewrap">
                <table>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($inbox as $m): ?>
                        <tr>
                            <td>
                                <?= e($m['customer_name']) ?><br>
                                <small><?= e($m['customer_email']) ?></small>
                            </td>

                            <td>
                                <?= e($m['product_name'] ?? 'General enquiry') ?>
                            </td>

                            <td>
                                <?= e(mb_strimwidth(
                                    $m['message'], 0, 70, '...'
                                )) ?>
                            </td>

                            <td><?= e($m['created_at']) ?></td>

                            <td><?= e($m['status']) ?></td>

                            <td>
                                <a class="btn"
                                   href="messages.php?customer_id=<?= (int)$m['customer_id'] ?>&product_id=<?= (int)$m['product_id'] ?>">
                                    Open Conversation
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require '../includes/footer.php'; ?>
