
<?php
require 'config/config.php';

$u = require_role(['admin', 'sales', 'product_manager']);

$page_title = 'Customer Management';
$msg = '';

/*
|--------------------------------------------------------------------------
| CUSTOMER MANAGEMENT - VIEW ONLY
|--------------------------------------------------------------------------
| Admin, Sales and Product Manager can view and search customers.
| Customers create their own accounts through register.php.
| No staff role can manually create, update or delete customers here.
*/

// Block all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(403);
    exit('You have view-only access to customer records. Customers must register their own accounts.');
}

/* SEARCH CUSTOMERS */
$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $stmt = $pdo->prepare("
        SELECT id, name, phone, email, address, created_at
        FROM customers
        WHERE name LIKE ?
           OR phone LIKE ?
           OR email LIKE ?
        ORDER BY id DESC
    ");

    $term = '%' . $search . '%';
    $stmt->execute([$term, $term, $term]);

    $rows = $stmt->fetchAll();

} else {

    $rows = $pdo->query("
        SELECT id, name, phone, email, address, created_at
        FROM customers
        ORDER BY id DESC
    ")->fetchAll();
}

require 'includes/header.php';
?>

<h1>Customer Management</h1>

<p class="muted">
    View and search customers registered with Gatsibo Rice Co. Ltd.
    Customers create their own accounts through the public registration page.
</p>

<div class="panel">

    <h2>Customer Directory</h2>

    <form method="get">

        <label>Search by name, phone or email</label>

        <div class="formgrid">

            <div>
                <input
                    type="text"
                    name="search"
                    placeholder="Enter customer details..."
                    value="<?= e($search) ?>"
                >
            </div>

            <div>
                <button type="submit" class="btn">
                    Search
                </button>

                <a href="customers.php" class="btn">
                    Clear Search
                </a>
            </div>

        </div>

    </form>

    <p>
        <strong>Total customers displayed:</strong>
        <?= count($rows) ?>
    </p>

    <div style="overflow-x:auto;">

        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Registration Date</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$rows): ?>

                    <tr>
                        <td colspan="6">
                            No customers found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <td><?= (int)$r['id'] ?></td>

                            <td><?= e($r['name']) ?></td>

                            <td><?= e($r['phone'] ?? '') ?></td>

                            <td><?= e($r['email'] ?? '') ?></td>

                            <td><?= e($r['address'] ?? '') ?></td>

                            <td>
                                <?= e($r['created_at'] ?? '') ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require 'includes/footer.php'; ?>
