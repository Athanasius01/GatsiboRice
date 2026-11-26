
<?php

session_start();

date_default_timezone_set('Africa/Kigali');

define('APP_NAME', 'Gatsibo Rice Co. Ltd');
define('BASE_URL', '/GatsiboRice');

$DB_HOST = '127.0.0.1';
$DB_NAME = 'gatsibo';
$DB_USER = 'root';
$DB_PASS = '';

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    http_response_code(500);

    exit(
        'Database connection failed. Start MySQL in XAMPP and check your database settings.'
    );

}

/*
|--------------------------------------------------------------------------
| ESCAPE OUTPUT
|--------------------------------------------------------------------------
*/

function e($v): string {

    return htmlspecialchars(
        (string)$v,
        ENT_QUOTES,
        'UTF-8'
    );

}

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

function go($p) {

    header('Location: ' . BASE_URL . '/' . $p);

    exit;

}

/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
|--------------------------------------------------------------------------
*/

function csrf_field() {

    if (empty($_SESSION['csrf'])) {

        $_SESSION['csrf'] = bin2hex(random_bytes(32));

    }

    echo '<input type="hidden" name="csrf" value="' .
        e($_SESSION['csrf']) . '">';

}

function csrf_check() {

    if (!hash_equals(
        $_SESSION['csrf'] ?? '',
        $_POST['csrf'] ?? ''
    )) {

        http_response_code(419);

        exit('Invalid request token. Refresh and try again.');

    }

}

/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

function user() {

    return $_SESSION['user'] ?? null;

}

/*
|--------------------------------------------------------------------------
| LOGIN REQUIRED
|--------------------------------------------------------------------------
*/

function require_login() {

    if (!user()) {

        go('login.php');

    }

    return user();

}

/*
|--------------------------------------------------------------------------
| PAGE ACCESS
|--------------------------------------------------------------------------
| ADMIN:
| Can view all company modules.
|
| OTHER STAFF:
| Can only view pages allowed for their assigned roles.
|--------------------------------------------------------------------------
*/

function require_role($roles) {

    $u = require_login();

    // Administrator can view all operational modules.
    if ($u['role'] === 'admin') {

        return $u;

    }

    if (!in_array($u['role'], $roles, true)) {

        http_response_code(403);

        exit('You do not have permission to access this page.');

    }

    return $u;

}

/*
|--------------------------------------------------------------------------
| OPERATIONAL ACTION ACCESS
|--------------------------------------------------------------------------
| ADMIN:
| Cannot perform operational actions.
|
| STAFF:
| Can perform actions only in their authorized modules.
|--------------------------------------------------------------------------
*/

function require_staff_action($roles) {

    $u = require_login();

    if ($u['role'] === 'admin') {

        http_response_code(403);

        exit('Admin has view-only access to operational modules.');

    }

    if (!in_array($u['role'], $roles, true)) {

        http_response_code(403);

        exit('You do not have permission to perform this action.');

    }

    return $u;

}

/*
|--------------------------------------------------------------------------
| CURRENCY
|--------------------------------------------------------------------------
*/

function money($n) {

    return number_format((float)$n, 0) . ' RWF';

}

/*
|--------------------------------------------------------------------------
| AUDIT LOG
|--------------------------------------------------------------------------
*/

function log_action($action, $details = '') {

    global $pdo;

    if (user()) {

        $s = $pdo->prepare(
            'INSERT INTO audit_logs(user_id, action, details)
             VALUES (?, ?, ?)'
        );

        $s->execute([
            user()['id'],
            $action,
            $details
        ]);

    }

}
