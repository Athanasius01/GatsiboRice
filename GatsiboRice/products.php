
<?php
require 'config/config.php';

$u = require_role(['store', 'product_manager', 'admin']);

$page_title = 'Products & Stock';
$msg = '';
$error = '';

$isAdmin = ($u['role'] === 'admin');

/* ONLY STORE AND PRODUCT MANAGER CAN ADD PRODUCTS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($isAdmin) {
        http_response_code(403);
        exit('Admin has view-only access to products.');
    }

    csrf_check();

    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $quantity = $_POST['quantity'] ?? '';
    $reorder = $_POST['reorder_level'] ?? '';
    $cost = $_POST['cost_price'] ?? '';
    $selling = $_POST['selling_price'] ?? '';

    if ($sku === '' || $name === '' || $category === '' || $unit === '') {
        $error = 'Please complete all product details.';

    } elseif (
        !is_numeric($quantity) ||
        !is_numeric($reorder) ||
        !is_numeric($cost) ||
        !is_numeric($selling) ||
        $quantity < 0 ||
        $reorder < 0 ||
        $cost < 0 ||
        $selling < 0
    ) {
        $error = 'Enter valid non-negative numbers for stock and prices.';

    } else {

        try {
            $stmt = $pdo->prepare("
                INSERT INTO products
                (sku, name, category, unit, quantity,
                 reorder_level, cost_price, selling_price)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $sku,
                $name,
                $category,
                $unit,
                $quantity,
                $reorder,
                $cost,
                $selling
            ]);

            log_action('Product created', $name);
            $msg = 'Product saved successfully.';

        } catch (PDOException $e) {
            $error = 'Could not save product. Check whether the SKU already exists.';
        }
    }
}

/* DISPLAY PRODUCTS */
$rows = $pdo->query("
    SELECT *
    FROM products
    ORDER BY name
")->fetchAll();

require 'includes/header.php';
?>

<h1>Products & Inventory</h1>

<p class="muted">
    <?= $isAdmin
        ? 'View available products and company stock information.'
        : 'Manage rice products, stock quantities, reorder levels and prices.' ?>
</p>

<?php if ($msg): ?>
    <div class="alert success">
        <?= e($msg) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error">
        <?= e($error) ?>
    </div>
<?php endif; ?>


<?php if (!$isAdmin): ?>

<!-- ADD PRODUCT FORM -->
<div class="panel">

    <h2>Add Product / Stock Item</h2>

    <form method="post">

        <?= csrf_field() ?>

        <div class="formgrid">

            <div>
                <label>SKU / Code</label>
                <input name="sku" required>
            </div>

            <div>
                <label>Product Name</label>
                <input name="name" required>
            </div>

            <div>
                <label>Category</label>
                <input name="category" required>
            </div>

            <div>
                <label>Unit (kg, bag, pcs)</label>
                <input name="unit" required>
            </div>

            <div>
                <label>Opening Quantity</label>
                <input type="number" name="quantity"
                       step="0.01" min="0" required>
            </div>

            <div>
                <label>Reorder Level</label>
                <input type="number" name="reorder_level"
                       step="0.01" min="0" required>
            </div>

            <div>
                <label>Cost Price (RWF)</label>
                <input type="number" name="cost_price"
                       step="0.01" min="0" required>
            </div>

            <div>
                <label>Selling Price (RWF)</label>
                <input type="number" name="selling_price"
                       step="0.01" min="0" required>
            </div>

        </div>

        <p>
            <button type="submit" class="btn">
                Add Product
            </button>
        </p>

    </form>

</div>

<?php endif; ?>


<!-- AVAILABLE PRODUCTS -->
<div class="panel">

    <h2>Available Products & Stock</h2>

    <div class="tablewrap">

        <table>

            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Reorder Level</th>

                    <?php if (!$isAdmin): ?>
                        <th>Cost</th>
                    <?php endif; ?>

                    <th>Selling Price</th>
                    <th>Stock Status</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$rows): ?>

                    <tr>
                        <td colspan="<?= $isAdmin ? 8 : 9 ?>">
                            No products registered yet.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <td><?= e($r['sku']) ?></td>

                            <td><?= e($r['name']) ?></td>

                            <td><?= e($r['category']) ?></td>

                            <td><?= e($r['unit']) ?></td>

                            <td><?= e($r['quantity']) ?></td>

                            <td><?= e($r['reorder_level']) ?></td>

                            <?php if (!$isAdmin): ?>
                                <td><?= money($r['cost_price']) ?></td>
                            <?php endif; ?>

                            <td><?= money($r['selling_price']) ?></td>

                            <td>
                                <?php if ((float)$r['quantity'] <= 0): ?>
                                    <span class="badge">Out of stock</span>

                                <?php elseif ((float)$r['quantity'] <= (float)$r['reorder_level']): ?>
                                    <span class="badge">Low stock</span>

                                <?php else: ?>
                                    <span class="badge">Available</span>
                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require 'includes/footer.php'; ?>
