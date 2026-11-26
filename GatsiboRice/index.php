
<?php
require_once __DIR__ . '/config/config.php';

/* ==========================================
   LOGGED-IN USER REDIRECTION
========================================== */

if (user()) {

    $u = user();
    $role = $u['role'];

    if ($role === 'customer') {
        go('customer/dashboard.php');
    }

    if ($role === 'employee') {
        go('employee/dashboard.php');
    }

    if ($role === 'product_manager') {
        go('product_manager/dashboard.php');
    }

    $page_title = ($role === 'admin')
        ? 'Administration Monitoring Dashboard'
        : 'Management Workspace';

    require 'includes/header.php';
    ?>

    <h1>
        <?= $role === 'admin'
            ? 'Administration Monitoring Dashboard'
            : 'Gatsibo Rice Management Workspace' ?>
    </h1>

    <p class="muted">
        Welcome, <?= e($u['name']) ?>.
        You are signed in as <?= e($role) ?>.
    </p>

    <?php if ($role === 'admin'): ?>

        <div class="panel">
            <h2>Administrator Overview</h2>
            <p>
                Welcome to the Gatsibo Rice Administration
                and Monitoring Workspace.
            </p>
            <p>
                You can review company activities, employee
                information, production, stock, customers
                and financial reports.
            </p>
        </div>

        <div class="cards">

            <div class="card">
                <div class="card-icon">📊</div>
                <h3>Company Reports</h3>
                <p>Review business reports and company performance.</p>
                <a class="btn" href="<?= BASE_URL ?>/reports.php">View Reports</a>
            </div>

            <div class="card">
                <div class="card-icon">⚙️</div>
                <h3>User Accounts</h3>
                <p>Create accounts and manage user roles.</p>
                <a class="btn" href="<?= BASE_URL ?>/users.php">Manage Users</a>
            </div>

            <div class="card">
                <div class="card-icon">👥</div>
                <h3>Employee Records</h3>
                <p>Review company employee information.</p>
                <a class="btn" href="<?= BASE_URL ?>/employees.php">View Employees</a>
            </div>

            <div class="card">
                <div class="card-icon">🕒</div>
                <h3>Attendance</h3>
                <p>Monitor employee attendance records.</p>
                <a class="btn" href="<?= BASE_URL ?>/attendance.php">View Attendance</a>
            </div>

            <div class="card">
                <div class="card-icon">📅</div>
                <h3>Leave Requests</h3>
                <p>Review employee leave requests.</p>
                <a class="btn" href="<?= BASE_URL ?>/leave_requests.php">View Requests</a>
            </div>

            <div class="card">
                <div class="card-icon">📈</div>
                <h3>Performance</h3>
                <p>Review employee performance information.</p>
                <a class="btn" href="<?= BASE_URL ?>/performance.php">View Performance</a>
            </div>

            <div class="card">
                <div class="card-icon">🌾</div>
                <h3>Products</h3>
                <p>Review rice products and product information.</p>
                <a class="btn" href="<?= BASE_URL ?>/products.php">View Products</a>
            </div>

            <div class="card">
                <div class="card-icon">📦</div>
                <h3>Stock Management</h3>
                <p>Review available stock and stock movements.</p>
                <a class="btn" href="<?= BASE_URL ?>/stock.php">View Stock</a>
            </div>

            <div class="card">
                <div class="card-icon">⚙️</div>
                <h3>Production</h3>
                <p>Monitor production activities and progress.</p>
                <a class="btn" href="<?= BASE_URL ?>/production.php">View Production</a>
            </div>

            <div class="card">
                <div class="card-icon">🛒</div>
                <h3>Customer Records</h3>
                <p>Review registered customer information.</p>
                <a class="btn" href="<?= BASE_URL ?>/customers.php">View Customers</a>
            </div>

            <div class="card">
                <div class="card-icon">💬</div>
                <h3>Customer Messages</h3>
                <p>Review customer communications.</p>
                <a class="btn" href="<?= BASE_URL ?>/product_manager/messages.php">View Messages</a>
            </div>

            <div class="card">
                <div class="card-icon">🛍️</div>
                <h3>Customer Orders</h3>
                <p>Review customer orders.</p>
                <a class="btn" href="<?= BASE_URL ?>/product_manager/orders.php">View Orders</a>
            </div>

            <div class="card">
                <div class="card-icon">💰</div>
                <h3>Sales</h3>
                <p>Review recorded company sales.</p>
                <a class="btn" href="<?= BASE_URL ?>/sales.php">View Sales</a>
            </div>

            <div class="card">
                <div class="card-icon">💳</div>
                <h3>Expenses</h3>
                <p>Review company expense records.</p>
                <a class="btn" href="<?= BASE_URL ?>/expenses.php">View Expenses</a>
            </div>

            <div class="card">
                <div class="card-icon">📋</div>
                <h3>Payroll Reports</h3>
                <p>Review payroll reports.</p>
                <a class="btn" href="<?= BASE_URL ?>/payroll_reports.php">View Payroll Reports</a>
            </div>

        </div>

    <?php else: ?>

        <div class="panel">
            <h2>Welcome to your management workspace</h2>
            <p>
                Use the navigation menu on the left to access
                the modules available to your account.
            </p>
        </div>

    <?php endif; ?>

    <?php
    require 'includes/footer.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gatsibo Rice | Quality Rice, Better Future</title>

<style>
:root {
    --green: #176b39;
    --dark: #103d28;
    --light: #f4f8f2;
    --gold: #e9b949;
    --text: #24352b;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    color: var(--text);
    background: white;
    line-height: 1.6;
}

a {
    text-decoration: none;
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: auto;
}

/* NAVIGATION */

header {
    background: white;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 12px #00000010;
}

nav {
    min-height: 76px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.logo {
    color: var(--green);
    font-size: 23px;
    font-weight: 800;
}

.logo span {
    color: var(--gold);
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 24px;
}

.nav-links a {
    color: #34443a;
    font-size: 14px;
    font-weight: 600;
}

.nav-links a:hover {
    color: var(--green);
}

.nav-button {
    background: var(--green);
    color: white !important;
    padding: 11px 20px;
    border-radius: 7px;
}

/* HERO */

.hero {
    min-height: 580px;
    display: flex;
    align-items: center;
    color: white;
    background:
      linear-gradient(90deg, #092d1ddd, #103d2870),
      url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=1800')
      center/cover no-repeat;
}

.hero-content {
    max-width: 680px;
    padding: 90px 0;
}

.hero-label {
    display: inline-block;
    background: #ffffff20;
    border: 1px solid #ffffff70;
    padding: 8px 16px;
    border-radius: 30px;
    margin-bottom: 22px;
    font-size: 13px;
}

.hero h1 {
    font-size: clamp(36px, 6vw, 64px);
    line-height: 1.12;
    margin-bottom: 22px;
}

.hero h1 span {
    color: var(--gold);
}

.hero p {
    font-size: 18px;
    max-width: 570px;
    color: #f1f6f1;
    margin-bottom: 30px;
}

.buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
}

.btn {
    display: inline-block;
    padding: 14px 24px;
    border-radius: 7px;
    font-weight: bold;
    transition: .2s;
}

.btn:hover {
    transform: translateY(-3px);
}

.btn-gold {
    background: var(--gold);
    color: #193522;
}

.btn-white {
    background: white;
    color: var(--green);
}

/* GENERAL SECTIONS */

section {
    padding: 85px 0;
}

.section-title {
    text-align: center;
    margin-bottom: 45px;
}

.section-title small {
    color: var(--green);
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.section-title h2 {
    font-size: 34px;
    color: var(--dark);
    margin: 10px 0;
}

.section-title p {
    color: #718077;
    max-width: 650px;
    margin: auto;
}

/* ABOUT */

.about {
    background: var(--light);
}

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 55px;
    align-items: center;
}

.about-image {
    min-height: 370px;
    border-radius: 15px;
    background: url('https://images.unsplash.com/photo-1536304993881-ff6e9eefa2a6?w=1000')
    center/cover;
}

.about-text h2 {
    color: var(--dark);
    font-size: 34px;
    margin-bottom: 18px;
}

.about-text p {
    color: #65736a;
    margin-bottom: 16px;
}

.values {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 25px;
}

.value {
    background: white;
    padding: 17px;
    border-radius: 9px;
    border-left: 4px solid var(--green);
}

.value strong {
    color: var(--green);
    display: block;
}

/* PRODUCTS AND SERVICES */

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 23px;
}

.card {
    padding: 30px 25px;
    border: 1px solid #e7eee8;
    border-radius: 12px;
    background: white;
    transition: .25s;
}

.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px #123d2812;
}

.card-icon {
    width: 55px;
    height: 55px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #e9f5eb;
    font-size: 27px;
    margin-bottom: 20px;
}

.card h3 {
    color: var(--dark);
    margin-bottom: 12px;
}

.card p {
    color: #718077;
    font-size: 14px;
}

/* PORTALS */

.portals {
    background: var(--light);
}

.portal-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

.portal {
    background: white;
    padding: 35px;
    border-radius: 13px;
    border: 1px solid #e3ece4;
    box-shadow: 0 8px 25px #00000006;
}

.portal h3 {
    color: var(--dark);
    font-size: 23px;
    margin: 12px 0;
}

.portal p {
    color: #718077;
    margin-bottom: 22px;
}

.portal .btn {
    font-size: 14px;
}

.portal-customer {
    border-top: 5px solid var(--green);
}

.portal-staff {
    border-top: 5px solid var(--gold);
}

/* LOCATION */

.location-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 35px;
    align-items: center;
}

.location-box {
    background: var(--light);
    padding: 30px;
    border-radius: 12px;
}

.location-box h3 {
    color: var(--dark);
    margin-bottom: 15px;
}

.location-box p {
    margin: 12px 0;
}

.map-link {
    display: inline-block;
    margin-top: 18px;
    color: var(--green);
    font-weight: bold;
}

.map-placeholder {
    min-height: 260px;
    border-radius: 12px;
    background: linear-gradient(135deg, #dcebd9, #eff4e7);
    display: grid;
    place-items: center;
    text-align: center;
    padding: 25px;
    color: var(--dark);
}

/* CONTACT */

.contact {
    background: var(--dark);
    color: white;
    text-align: center;
}

.contact h2 {
    font-size: 34px;
    margin-bottom: 15px;
}

.contact p {
    color: #d5e5d8;
    margin-bottom: 25px;
}

.contact .btn {
    background: var(--gold);
    color: var(--dark);
}

/* FOOTER */

footer {
    background: #092c1c;
    color: #c6d8ca;
    padding: 30px 0;
    text-align: center;
    font-size: 13px;
}

footer strong {
    color: white;
}

/* MOBILE */

@media(max-width: 850px) {
    .nav-links {
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .about-grid,
    .location-grid {
        grid-template-columns: 1fr;
    }

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 600px) {
    nav {
        padding: 15px 0;
        flex-direction: column;
    }

    .nav-links {
        gap: 13px;
    }

    .nav-links a {
        font-size: 12px;
    }

    .hero {
        min-height: 500px;
    }

    .hero-content {
        padding: 65px 0;
    }

    .hero p {
        font-size: 16px;
    }

    .cards,
    .portal-grid,
    .values {
        grid-template-columns: 1fr;
    }

    section {
        padding: 60px 0;
    }

    .section-title h2 {
        font-size: 28px;
    }
}
</style>
</head>

<body>

<header>
<nav class="container">

    <a href="#home" class="logo">
        🌾 GATSIBO <span>RICE</span>
    </a>

    <div class="nav-links">
        <a href="#home">Home</a>
        <a href="#about">About Us</a>
        <a href="#products">Products</a>
        <a href="#services">Services</a>
        <a href="#location">Location</a>
        <a href="#contact">Contact</a>
        <a href="#portals" class="nav-button">Sign In</a>
    </div>

</nav>
</header>

<!-- HOME -->

<section class="hero" id="home">
<div class="container">
    <div class="hero-content">

        <span class="hero-label">
            WELCOME TO GATSIBO RICE MANAGEMENT
        </span>

        <h1>
            Growing Quality Rice.
            <span>Building a Better Future.</span>
        </h1>

        <p>
            Discover quality rice products, agricultural
            opportunities and reliable customer services.
            Together, we support sustainable rice production
            and development in Rwanda.
        </p>

        <div class="buttons">
            <a href="#products" class="btn btn-gold">
                Explore Our Products
            </a>

            <a href="#about" class="btn btn-white">
                Discover Our Company
            </a>
        </div>

    </div>
</div>
</section>

<!-- ABOUT US -->

<section class="about" id="about">
<div class="container">
    <div class="about-grid">

        <div class="about-image"></div>

        <div class="about-text">
            <small>WHO WE ARE</small>

            <h2>About Gatsibo Rice</h2>

            <p>
                Gatsibo Rice is an agricultural business
                management initiative focused on rice production,
                processing, product management and customer
                services.
            </p>

            <p>
                Our goal is to support quality rice products,
                effective business operations and stronger
                connections between customers, employees and
                agricultural activities.
            </p>

            <div class="values">
                <div class="value">
                    <strong>Our Mission</strong>
                    <small>Support quality rice production and reliable services.</small>
                </div>

                <div class="value">
                    <strong>Our Vision</strong>
                    <small>Contribute to a stronger and more sustainable rice sector.</small>
                </div>

                <div class="value">
                    <strong>Our Values</strong>
                    <small>Quality, integrity and teamwork.</small>
                </div>

                <div class="value">
                    <strong>Our Commitment</strong>
                    <small>Customer satisfaction and continuous improvement.</small>
                </div>
            </div>
        </div>

    </div>
</div>
</section>

<!-- PRODUCTS -->

<section id="products">
<div class="container">

    <div class="section-title">
        <small>WHAT WE OFFER</small>
        <h2>Our Rice Products</h2>
        <p>
            Explore our rice product categories and discover
            the quality we aim to provide.
        </p>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-icon">🌾</div>
            <h3>Quality Rice</h3>
            <p>
                Rice products selected and managed with
                attention to quality and customer needs.
            </p>
        </div>

        <div class="card">
            <div class="card-icon">🍚</div>
            <h3>Processed Rice</h3>
            <p>
                Rice processing and preparation for
                convenient customer access.
            </p>
        </div>

        <div class="card">
            <div class="card-icon">📦</div>
            <h3>Rice Packaging</h3>
            <p>
                Product packaging and management designed
                to support distribution and sales.
            </p>
        </div>
    </div>

</div>
</section>

<!-- SERVICES -->

<section class="about" id="services">
<div class="container">

    <div class="section-title">
        <small>OUR ACTIVITIES</small>
        <h2>Our Services</h2>
        <p>
            We focus on organized operations and efficient
            management across our business activities.
        </p>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-icon">🚜</div>
            <h3>Agricultural Activities</h3>
            <p>
                Supporting rice production and agricultural
                business activities.
            </p>
        </div>

        <div class="card">
            <div class="card-icon">🤝</div>
            <h3>Customer Services</h3>
            <p>
                Customer account management, orders,
                inquiries and communication.
            </p>
        </div>

        <div class="card">
            <div class="card-icon">📊</div>
            <h3>Business Management</h3>
            <p>
                Coordinating employees, attendance,
                products, sales and business operations.
            </p>
        </div>
    </div>

</div>
</section>

<!-- PORTAL ACCESS -->

<section class="portals" id="portals">
<div class="container">

    <div class="section-title">
        <small>ACCESS YOUR ACCOUNT</small>
        <h2>Welcome to Our Portals</h2>
        <p>
            Choose the appropriate portal to access
            Gatsibo Rice services.
        </p>
    </div>

    <div class="portal-grid">

        <div class="portal portal-customer">
            <div class="card-icon">👨‍👩‍👧</div>

            <h3>Customer Portal</h3>

            <p>
                Are you a customer? Create an account to
                explore products, place orders and communicate
                with our team. Existing customers can sign in.
            </p>

            <div class="buttons">
                <a href="register.php" class="btn btn-gold">
                    Create Account
                </a>

                <a href="login.php" class="btn btn-white">
                    Customer Login
                </a>
            </div>
        </div>

        <div class="portal portal-staff">
            <div class="card-icon">🔐</div>

            <h3>Employee & Administration Portal</h3>

            <p>
                Secure access for administrators, HR officers,
                employees and product managers. Your account
                role determines the workspace you can access.
            </p>

            <a href="staff_login.php" class="btn btn-gold">
                Staff & Admin Login
            </a>
        </div>

    </div>
</div>
</section>

<!-- LOCATION -->

<section id="location">
<div class="container">

    <div class="section-title">
        <small>FIND US</small>
        <h2>Our Location</h2>
        <p>
            Gatsibo District, Eastern Province, Rwanda.
        </p>
    </div>

    <div class="location-grid">

        <div class="location-box">
            <h3>📍 Visit Gatsibo Rice</h3>

            <p><strong>District:</strong> Gatsibo</p>
            <p><strong>Province:</strong> Eastern Province</p>
            <p><strong>Country:</strong> Rwanda</p>

            <p>
                We welcome customers and business partners
                interested in our rice products and services.
            </p>

            <a class="map-link"
               href="https://www.google.com/maps/search/?api=1&query=Gatsibo+District+Rwanda"
               target="_blank" rel="noopener">
                View Location on Google Maps →
            </a>
        </div>

        <div class="map-placeholder">
            <div>
                <div style="font-size:50px">📍</div>
                <h3>Gatsibo District</h3>
                <p>Eastern Province, Rwanda</p>
            </div>
        </div>

    </div>
</div>
</section>

<!-- CONTACT -->

<section class="contact" id="contact">
<div class="container">

    <h2>Let's Grow Together</h2>

    <p>
        Interested in our products or services?
        We welcome your questions and inquiries.
    </p>

    <a href="login.php" class="btn">
        Contact Our Team
    </a>

</div>
</section>

<!-- FOOTER -->

<footer>
<div class="container">

    <p><strong>🌾 GATSIBO RICE</strong></p>

    <p>
        Growing Quality Rice. Building a Better Future.
    </p>

    <p style="margin-top:12px">
        © <?= date('Y') ?> Gatsibo Rice.
        All Rights Reserved.
    </p>

</div>
</footer>

</body>
</html>
