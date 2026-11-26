
<?php
require '../config/config.php';

$u = require_role(['customer']);

$stmt = $pdo->prepare(
    'SELECT name FROM customers WHERE user_id = ? LIMIT 1'
);
$stmt->execute([$u['id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    exit('Customer profile not found.');
}

$page_title = 'Welcome to Gatsibo Rice';
require 'sidebar.php';
?>

<style>
.welcome-hero {
    background: linear-gradient(120deg, #123d2b, #28754d);
    color: white;
    padding: 55px 40px;
    border-radius: 14px;
    margin-bottom: 28px;
}
.welcome-hero h1 {
    font-size: 34px;
    margin: 0 0 15px;
}
.welcome-hero p {
    font-size: 16px;
    line-height: 1.8;
    max-width: 700px;
}
.welcome-section {
    background: white;
    padding: 28px;
    border-radius: 12px;
    margin-bottom: 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.welcome-section h2 {
    color: #174b32;
}
.welcome-footer {
    text-align: center;
    padding: 25px;
    color: #64748b;
}
</style>

<section class="welcome-hero">
    <p>GATSIBO RICE COMPANY</p>

    <h1>Welcome, <?= e($customer['name']) ?>!</h1>

    <p>
        We are delighted to welcome you to Gatsibo Rice.
        Thank you for choosing us as your rice supply partner.
        Our customer portal makes it easy to explore our products,
        discuss prices, submit orders and follow your order progress.
    </p>
</section>

<section class="welcome-section">
    <h2>🌾 About Gatsibo Rice</h2>

    <p>
        Gatsibo Rice is committed to providing quality rice products
        and dependable customer service. We value our customers and
        aim to build lasting relationships through quality, trust
        and transparent communication.
    </p>
</section>

<section class="welcome-section">
    <h2>🤝 Our Commitment to You</h2>

    <p>
        We aim to make your shopping experience simple and convenient.
        You can browse our available products, communicate directly
        with our Product Manager, negotiate prices and monitor your
        orders through this portal.
    </p>
</section>

<section class="welcome-section">
    <h2>🛒 Start Exploring</h2>

    <p>
        Use the navigation menu on the left to access our products,
        price negotiations, orders and your customer account.
    </p>
</section>

<div class="welcome-footer">
    <strong>GATSIBO RICE COMPANY</strong>
    <p>Quality Rice • Reliable Service • Customer Satisfaction</p>
    <small>Thank you for being part of our customer community.</small>
</div>

</main>
</div>
</div>
</body>
</html>
