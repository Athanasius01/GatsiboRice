
<?php
require '../config/config.php';

$u = require_role(['customer']);

$products = $pdo->query(
    "SELECT id, sku, name, category, unit, quantity, selling_price
     FROM products
     WHERE quantity > 0
     ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Our Rice Products';
require 'sidebar.php';
?>

<style>
.catalogue-header {
    background: linear-gradient(120deg, #123d2b, #28754d);
    color: white;
    padding: 35px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.catalogue-header h1 {
    margin-top: 0;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 22px;
}

.product-card {
    background: white;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 3px 12px rgba(0,0,0,.07);
    border: 1px solid #e5e7eb;
}

.product-icon {
    background: #e7f3e9;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    font-size: 65px;
    margin-bottom: 18px;
}

.product-card h2 {
    color: #174b32;
    font-size: 21px;
}

.product-price {
    color: #187344;
    font-size: 23px;
    font-weight: bold;
    margin: 12px 0;
}

.product-stock {
    color: #64748b;
    font-size: 14px;
}

.product-button {
    display: block;
    background: #17643b;
    color: white;
    text-align: center;
    padding: 12px;
    text-decoration: none;
    border-radius: 7px;
    margin-top: 18px;
}

.product-button:hover {
    background: #104b2c;
}

.empty-products {
    background: white;
    padding: 30px;
    border-radius: 10px;
}
</style>

<div class="catalogue-header">
    <h1>🌾 Our Rice Products</h1>

    <p>
        Discover our available rice products at Gatsibo Rice.
        Browse our catalogue, check prices and contact our Product
        Manager if you would like to negotiate.
    </p>
</div>

<h2>Available Products</h2>

<?php if (!$products): ?>

    <div class="empty-products">
        <h3>No products currently available</h3>
        <p>Please check again later.</p>
    </div>

<?php else: ?>

    <div class="product-grid">

        <?php foreach ($products as $product): ?>

            <div class="product-card">

                <div class="product-icon">🌾</div>

                <h2><?= e($product['name']) ?></h2>

                <p>
                    <strong>SKU:</strong>
                    <?= e($product['sku']) ?>
                </p>

                <p>
                    <strong>Category:</strong>
                    <?= e($product['category'] ?? 'Rice') ?>
                </p>

                <div class="product-price">
                    <?= number_format((float)$product['selling_price'], 2) ?>
                    RWF / <?= e($product['unit'] ?: 'unit') ?>
                </div>

                <p class="product-stock">
                    <strong>Available stock:</strong>
                    <?= number_format((float)$product['quantity'], 2) ?>
                    <?= e($product['unit'] ?: 'unit') ?>
                </p>

                <a class="product-button"
                   href="messages.php?product_id=<?= (int)$product['id'] ?>">
                    💬 Discuss Price
                </a>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
