
<?php

$u = user();
$role = $u['role'] ?? '';

$links = [];

/*
|--------------------------------------------------------------------------
| ADMIN: VIEW ALL COMPANY MODULES
|--------------------------------------------------------------------------
*/

if ($role === 'admin') {

    $links = [
        ['index.php', '▦', 'Dashboard'],
        ['users.php', '⚙', 'Staff Account Management'],
        ['employees.php', '♙', 'Employees'],
        ['attendance.php', '◷', 'Attendance'],
        ['leave_requests.php', '📅', 'Leave Requests'],
        ['payroll.php', '💰', 'Payroll Management'],
        ['payroll_reports.php', '📊', 'Payroll Reports'],
        ['performance.php', '📈', 'Performance Management'],
        ['products.php', '▤', 'Products'],
        ['stock.php', '⇄', 'Stock Movements'],
        ['production.php', '⚙', 'Production'],
        ['customers.php', '♡', 'Customers'],
        ['sales.php', '↗', 'Sales & Orders'],
        ['expenses.php', '−', 'Expenses'],
        ['reports.php', '▥', 'Company Reports'],
    ];

/*
|--------------------------------------------------------------------------
| EMPLOYEE
|--------------------------------------------------------------------------
*/

} elseif ($role === 'employee') {

    $links = [
        ['employee/dashboard.php', '▦', 'My Dashboard'],
        ['employee/attendance.php', '◷', 'My Attendance'],
        ['employee/leave.php', '📅', 'Leave Requests'],
        ['employee/payslips.php', '▤', 'My Payslips'],
    ];

/*
|--------------------------------------------------------------------------
| PRODUCT MANAGER
|--------------------------------------------------------------------------
*/

} elseif ($role === 'product_manager') {

    $links = [
        ['product_manager/dashboard.php', '▦', 'Dashboard'],
        ['product_manager/messages.php', '💬', 'Customer Messages'],
        ['product_manager/orders.php', '📦', 'Customer Orders'],
        ['products.php', '▤', 'Products & Stock'],
        ['customers.php', '♡', 'Customers'],
    ];

/*
|--------------------------------------------------------------------------
| OTHER STAFF
|--------------------------------------------------------------------------
*/

} else {

    $links = [
        ['index.php', '▦', 'Dashboard'],
        ['employees.php', '♙', 'Employees'],
        ['attendance.php', '◷', 'Attendance'],
        ['leave_requests.php', '📅', 'Leave Requests'],
        ['products.php', '▤', 'Products & Stock'],
        ['stock.php', '⇄', 'Stock Movements'],
        ['production.php', '⚙', 'Production'],
        ['customers.php', '♡', 'Customers'],
        ['sales.php', '↗', 'Sales & Orders'],
        ['expenses.php', '−', 'Expenses'],
        ['payroll.php', '💰', 'Payroll Management'],
        ['payroll_reports.php', '📊', 'Payroll Reports'],
        ['performance.php', '📈', 'Performance Management'],
        ['reports.php', '▥', 'Reports'],
    ];

}

/*
|--------------------------------------------------------------------------
| BRAND LINK
|--------------------------------------------------------------------------
*/

$brandLink = 'index.php';

if ($role === 'admin') {
    $brandLink = 'users.php';
} elseif ($role === 'employee') {
    $brandLink = 'employee/dashboard.php';
} elseif ($role === 'product_manager') {
    $brandLink = 'product_manager/dashboard.php';
}

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= e($page_title ?? APP_NAME) ?> | Gatsibo Rice
    </title>

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/style.css">

</head>

<body>

<div class="top">

    <a class="brand"
       href="<?= BASE_URL ?>/<?= e($brandLink) ?>">

        🌾 GATSIBO RICE

    </a>

    <span>

        <?php if ($role === 'admin'): ?>

            Administrator Workspace

        <?php elseif ($role === 'product_manager'): ?>

            Product Management Workspace

        <?php else: ?>

            Integrated Management

        <?php endif; ?>

    </span>

    <span class="right">

        <?= e($u['name'] ?? '') ?>
        ·
        <?= e($role) ?>

    </span>

    <a href="<?= BASE_URL ?>/logout.php">
        Sign out
    </a>

</div>

<div class="layout">

    <aside>

        <div class="navtitle">

            <?= $role === 'admin'
                ? 'ADMINISTRATION WORKSPACE'
                : ($role === 'product_manager'
                    ? 'PRODUCT WORKSPACE'
                    : 'WORKSPACE') ?>

        </div>

        <?php foreach ($links as [$url, $ico, $label]): ?>

            <a href="<?= BASE_URL ?>/<?= e($url) ?>">

                <span><?= $ico ?></span>

                <?= e($label) ?>

            </a>

        <?php endforeach; ?>

    </aside>

    <main>
