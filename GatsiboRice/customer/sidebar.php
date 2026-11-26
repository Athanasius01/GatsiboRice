
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
* {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    min-height: 100%;
}

.customer-layout {
    display: flex;
    min-height: 100vh;
    align-items: stretch;
    background: #f4f7f6;
}

.customer-sidebar {
    width: 250px;
    flex-shrink: 0;
    min-height: 100vh;
    background: #123d2b;
    color: white;
    padding: 25px 15px;
}

.customer-sidebar h2 {
    font-size: 20px;
    margin: 0 0 30px;
    padding: 0 10px;
    color: white;
}

.customer-sidebar a {
    display: block;
    color: #e7f2eb;
    text-decoration: none;
    padding: 14px 12px;
    border-radius: 7px;
    margin-bottom: 8px;
    transition: background 0.2s;
}

.customer-sidebar a:hover,
.customer-sidebar a.active {
    background: #28754d;
    color: white;
}

.customer-content {
    flex: 1;
    min-width: 0;
    padding: 30px;
}

@media (max-width: 700px) {
    .customer-layout {
        flex-direction: column;
    }

    .customer-sidebar {
        width: 100%;
        min-height: auto;
    }

    .customer-content {
        padding: 18px;
    }
}
</style>

<div class="customer-layout">

    <aside class="customer-sidebar">

        <h2>🌾 Customer Portal</h2>

        <a href="dashboard.php"
           class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            ▦ Dashboard
        </a>

        <a href="products.php"
           class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
            🛒 Our Products
        </a>

        <a href="messages.php"
           class="<?= $currentPage === 'messages.php' ? 'active' : '' ?>">
            💬 Price Negotiation
        </a>

        <a href="orders.php"
           class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
            📦 My Orders
        </a>

        <a href="account.php"
           class="<?= $currentPage === 'account.php' ? 'active' : '' ?>">
            👤 My Account
        </a>

        <a href="../logout.php">
            ↪ Sign Out
        </a>

    </aside>

    <main class="customer-content">
