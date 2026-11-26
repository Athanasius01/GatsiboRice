
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

$products = $pdo->query(
    "SELECT id, name, unit, selling_price
     FROM products
     WHERE quantity > 0
     ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$selectedProduct = (int)($_GET['product_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $productId = (int)($_POST['product_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
        $error = 'Please enter your message.';
    } elseif (strlen($message) > 3000) {
        $error = 'Your message is too long. Please use 3000 characters or fewer.';
    } elseif ($productId > 0) {
        $check = $pdo->prepare(
            'SELECT id FROM products WHERE id = ? AND quantity > 0'
        );
        $check->execute([$productId]);

        if (!$check->fetch()) {
            $error = 'Please select an available product.';
        }
    }

    if (!$error) {
        $insert = $pdo->prepare(
            "INSERT INTO customer_messages
             (customer_id, product_id, sender_role, message, status)
             VALUES (?, ?, 'customer', ?, 'New')"
        );

        $insert->execute([
            $customerId,
            $productId > 0 ? $productId : null,
            $message
        ]);

        header('Location: messages.php?product_id=' . $productId . '&sent=1');
        exit;
    }

    $selectedProduct = $productId;
}

if (isset($_GET['sent'])) {
    $msg = 'Your message has been sent to the Product Manager.';
}

$history = $pdo->prepare(
    "SELECT m.*, p.name AS product_name
     FROM customer_messages m
     LEFT JOIN products p ON p.id = m.product_id
     WHERE m.customer_id = ?
     ORDER BY m.created_at ASC, m.id ASC"
);
$history->execute([$customerId]);
$messages = $history->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Price Negotiation';
require 'sidebar.php';
?>

<style>
.negotiation-header {
    background: linear-gradient(120deg, #123d2b, #28754d);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.chat-container {
    background: white;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
}

.chat-message {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 14px;
    max-width: 85%;
}

.customer-message {
    background: #e6f4eb;
    margin-left: auto;
}

.manager-message {
    background: #edf1f7;
    margin-right: auto;
}

.chat-message strong {
    display: block;
    margin-bottom: 7px;
    color: #174b32;
}

.chat-message small {
    display: block;
    color: #64748b;
    margin-top: 8px;
}

.negotiation-form {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
}

.negotiation-form label {
    display: block;
    font-weight: bold;
    margin: 15px 0 7px;
}

.negotiation-form select,
.negotiation-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
}

.negotiation-form button {
    background: #17643b;
    color: white;
    padding: 12px 22px;
    border: 0;
    border-radius: 7px;
    cursor: pointer;
    margin-top: 15px;
}

.notice {
    background: #e6f4eb;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 15px;
}
</style>

<div class="negotiation-header">
    <h1>💬 Price Negotiation</h1>
    <p>
        Communicate directly with our Product Manager.
        Ask questions, discuss product prices and receive responses.
    </p>
</div>

<?php if ($msg): ?>
    <div class="notice"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#fee2e2;padding:12px;border-radius:7px">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="chat-container">
    <h2>Conversation History</h2>

    <?php if (!$messages): ?>

        <p>No messages yet. Start a conversation below.</p>

    <?php else: ?>

        <?php foreach ($messages as $item): ?>

            <div class="chat-message <?= $item['sender_role'] === 'customer' ? 'customer-message' : 'manager-message' ?>">

                <strong>
                    <?= $item['sender_role'] === 'customer'
                        ? 'You'
                        : 'Product Manager' ?>
                </strong>

                <?php if ($item['product_name']): ?>
                    <p>
                        <strong>Product:</strong>
                        <?= e($item['product_name']) ?>
                    </p>
                <?php endif; ?>

                <div><?= nl2br(e($item['message'])) ?></div>

                <small>
                    <?= e($item['created_at']) ?>
                </small>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>
</div>

<div class="negotiation-form">
    <h2>Send a Message</h2>

    <form method="post">
        <?= csrf_field() ?>

        <label>Select Product</label>

        <select name="product_id">
            <option value="0">General Enquiry</option>

            <?php foreach ($products as $product): ?>
                <option
                    value="<?= (int)$product['id'] ?>"
                    <?= $selectedProduct === (int)$product['id'] ? 'selected' : '' ?>
                >
                    <?= e($product['name']) ?>
                    - <?= number_format((float)$product['selling_price'], 2) ?>
                    RWF / <?= e($product['unit']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Your Message</label>

        <textarea
            name="message"
            rows="5"
            maxlength="3000"
            required
            placeholder="Write your question or proposed price here..."
        ></textarea>

        <button type="submit">Send Message</button>
    </form>
</div>

</main>
</div>
</div>
</body>
</html>
