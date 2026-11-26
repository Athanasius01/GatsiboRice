
<?php
require 'config/config.php';

$u = require_role(['admin']);

$page_title = 'Staff Account Management';
$msg = '';
$error = '';

/*
|--------------------------------------------------------------------------
| STAFF ROLES
|--------------------------------------------------------------------------
*/

// Roles requiring an existing employee record
$staffRoles = [
    'hr',
    'employee',
    'accountant',
    'finance',
    'procurement',
    'store',
    'storekeeper',
    'production',
    'machine_operator',
    'quality_control',
    'sales',
    'marketing',
    'transport',
    'driver'
];

// Roles available for staff accounts
$assignableRoles = [
    'hr' => 'Human Resources',
    'employee' => 'Employee',
    'accountant' => 'Accountant',
    'finance' => 'Finance Officer',
    'procurement' => 'Procurement Officer',
    'store' => 'Storekeeper',
    'storekeeper' => 'Storekeeper (Alternative)',
    'production' => 'Production Supervisor',
    'machine_operator' => 'Machine Operator',
    'quality_control' => 'Quality Control Officer',
    'sales' => 'Sales Officer',
    'marketing' => 'Marketing Officer',
    'transport' => 'Transport Officer',
    'driver' => 'Driver',
    'product_manager' => 'Product Manager'
];

/*
|--------------------------------------------------------------------------
| HANDLE ACCOUNT MANAGEMENT ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_check();

    $action = $_POST['action'] ?? '';

    $userId = (int)($_POST['user_id'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | CREATE STAFF ACCOUNT
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {

        $accountType = $_POST['account_type'] ?? '';
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!isset($assignableRoles[$accountType])) {

            $error = 'Please select a valid staff role.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = 'Please enter a valid email address.';

        } elseif (strlen($password) < 10) {

            $error = 'Password must contain at least 10 characters.';

        } else {

            try {

                $check = $pdo->prepare(
                    'SELECT id FROM users WHERE email = ?'
                );

                $check->execute([$email]);

                if ($check->fetch()) {

                    $error = 'This email address is already registered.';

                } else {

                    $employeeId = null;
                    $name = '';

                    if (in_array($accountType, $staffRoles, true)) {

                        $employeeId = (int)($_POST['employee_id'] ?? 0);

                        $check = $pdo->prepare("
                            SELECT e.id, e.name
                            FROM employees e
                            LEFT JOIN users u ON u.employee_id = e.id
                            WHERE e.id = ?
                              AND e.status = 'active'
                              AND u.id IS NULL
                        ");

                        $check->execute([$employeeId]);

                        $employee = $check->fetch();

                        if (!$employee) {

                            $error = 'Select an active employee who does not already have a login account.';

                        } else {

                            $name = $employee['name'];
                        }

                    } else {

                        // Product Manager account
                        $name = trim($_POST['account_name'] ?? '');

                        if ($name === '') {
                            $error = 'Please enter the staff member name.';
                        }
                    }

                    if (!$error) {

                        $insert = $pdo->prepare("
                            INSERT INTO users
                            (name, email, password_hash, role, status, employee_id)
                            VALUES (?, ?, ?, ?, 'active', ?)
                        ");

                        $insert->execute([
                            $name,
                            $email,
                            password_hash($password, PASSWORD_DEFAULT),
                            $accountType,
                            $employeeId
                        ]);

                        log_action(
                            'Staff account created',
                            $name . ' - ' . $accountType
                        );

                        $msg = 'Staff account created successfully.';
                    }
                }

            } catch (PDOException $e) {

                $error = 'Could not create account. Check the email and employee record.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STAFF DETAILS
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'update_details') {

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        if ($userId < 1 || $name === '') {

            $error = 'Please provide a valid staff name.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = 'Please enter a valid email address.';

        } else {

            $check = $pdo->prepare("
                SELECT id, role
                FROM users
                WHERE id = ?
            ");

            $check->execute([$userId]);
            $target = $check->fetch();

            if (!$target || $target['role'] === 'admin') {

                $error = 'This account cannot be changed.';

            } else {

                $check = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE email = ?
                      AND id <> ?
                ");

                $check->execute([$email, $userId]);

                if ($check->fetch()) {

                    $error = 'That email address is already used by another account.';

                } else {

                    $update = $pdo->prepare("
                        UPDATE users
                        SET name = ?, email = ?
                        WHERE id = ?
                    ");

                    $update->execute([
                        $name,
                        $email,
                        $userId
                    ]);

                    log_action(
                        'Staff account details updated',
                        $name
                    );

                    $msg = 'Staff account details updated successfully.';
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STAFF ROLE
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'update_role') {

        $newRole = $_POST['role'] ?? '';

        if (
            $userId < 1 ||
            !isset($assignableRoles[$newRole])
        ) {

            $error = 'Invalid staff account or role selected.';

        } else {

            $check = $pdo->prepare("
                SELECT id, name, employee_id, role
                FROM users
                WHERE id = ?
            ");

            $check->execute([$userId]);
            $target = $check->fetch();

            if (!$target || $target['role'] === 'admin') {

                $error = 'The Administrator account is protected.';

            } elseif (
                in_array($newRole, $staffRoles, true) &&
                empty($target['employee_id'])
            ) {

                $error = 'This role requires an employee record linked to the account.';

            } else {

                $update = $pdo->prepare("
                    UPDATE users
                    SET role = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $newRole,
                    $userId
                ]);

                log_action(
                    'Staff role changed',
                    $target['name'] . ' changed to ' . $newRole
                );

                $msg = 'Staff role updated successfully.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RESET STAFF PASSWORD
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'reset_password') {

        $newPassword = $_POST['new_password'] ?? '';

        if ($userId < 1) {

            $error = 'Invalid staff account.';

        } elseif (strlen($newPassword) < 10) {

            $error = 'New password must contain at least 10 characters.';

        } else {

            $check = $pdo->prepare("
                SELECT id, name, role
                FROM users
                WHERE id = ?
            ");

            $check->execute([$userId]);
            $target = $check->fetch();

            if (!$target || $target['role'] === 'admin') {

                $error = 'The Administrator account is protected.';

            } else {

                $update = $pdo->prepare("
                    UPDATE users
                    SET password_hash = ?
                    WHERE id = ?
                ");

                $update->execute([
                    password_hash($newPassword, PASSWORD_DEFAULT),
                    $userId
                ]);

                log_action(
                    'Staff password reset',
                    $target['name']
                );

                $msg = 'Staff password reset successfully.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVATE / DEACTIVATE STAFF ACCOUNT
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'toggle_status') {

        $check = $pdo->prepare("
            SELECT id, name, role, status
            FROM users
            WHERE id = ?
        ");

        $check->execute([$userId]);
        $target = $check->fetch();

        if (!$target || $target['role'] === 'admin') {

            $error = 'The Administrator account is protected.';

        } else {

            $newStatus = (
                $target['status'] === 'active'
            ) ? 'inactive' : 'active';

            $update = $pdo->prepare("
                UPDATE users
                SET status = ?
                WHERE id = ?
            ");

            $update->execute([
                $newStatus,
                $userId
            ]);

            log_action(
                'Staff account status changed',
                $target['name'] . ' - ' . $newStatus
            );

            $msg = 'Staff account status changed to ' . $newStatus . '.';
        }
    }

    else {

        $error = 'Invalid account management request.';
    }
}

/*
|--------------------------------------------------------------------------
| EMPLOYEES WITHOUT LOGIN ACCOUNTS
|--------------------------------------------------------------------------
*/

$employees = $pdo->query("
    SELECT
        e.id,
        e.employee_no,
        e.name,
        e.department
    FROM employees e
    LEFT JOIN users u ON u.employee_id = e.id
    WHERE u.id IS NULL
      AND e.status = 'active'
    ORDER BY e.name
")->fetchAll();

/*
|--------------------------------------------------------------------------
| STAFF ACCOUNTS
|--------------------------------------------------------------------------
| Customer accounts and the protected Admin account are not included.
|--------------------------------------------------------------------------
*/

$rows = $pdo->query("
    SELECT
        u.id,
        u.name,
        u.email,
        u.role,
        u.status,
        u.created_at,
        u.employee_id,
        e.employee_no
    FROM users u
    LEFT JOIN employees e ON e.id = u.employee_id
    WHERE u.role <> 'admin'
      AND u.role <> 'customer'
    ORDER BY u.id DESC
")->fetchAll();

require 'includes/header.php';
?>

<h1>Staff Account Management</h1>

<p class="muted">
    Administrator workspace for creating and managing staff login accounts.
</p>

<?php if ($msg): ?>
    <div class="alert success">
        <?= e($msg) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert">
        <?= e($error) ?>
    </div>
<?php endif; ?>


<!-- CREATE STAFF ACCOUNT -->

<div class="panel">

    <h2>Create Staff Account</h2>

    <form method="post">

        <?php csrf_field(); ?>

        <input type="hidden" name="action" value="create">

        <div class="formgrid">

            <div>
                <label>Staff Role</label>

                <select
                    name="account_type"
                    id="account_type"
                    onchange="toggleAccountFields()"
                    required
                >

                    <option value="">-- Select staff role --</option>

                    <?php foreach ($assignableRoles as $value => $label): ?>

                        <option value="<?= e($value) ?>">
                            <?= e($label) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div id="employee_field" style="display:none;">

                <label>Select Employee</label>

                <select name="employee_id" id="employee_id">

                    <option value="">-- Select employee --</option>

                    <?php foreach ($employees as $employee): ?>

                        <option value="<?= (int)$employee['id'] ?>">

                            <?= e(
                                $employee['employee_no'] . ' - ' .
                                $employee['name'] . ' - ' .
                                $employee['department']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div id="account_name_field" style="display:none;">

                <label>Staff Member Name</label>

                <input
                    type="text"
                    name="account_name"
                    placeholder="Enter staff member name"
                >

            </div>

            <div>

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    placeholder="staff@example.com"
                    required
                >

            </div>

            <div>

                <label>Initial Password</label>

                <input
                    type="password"
                    name="password"
                    minlength="10"
                    required
                >

            </div>

        </div>

        <p>
            <button type="submit" class="btn">
                Create Staff Account
            </button>
        </p>

    </form>

</div>


<!-- STAFF ACCOUNT DIRECTORY -->

<div class="panel">

    <h2>Staff Accounts (<?= count($rows) ?>)</h2>

    <p class="muted">
        Manage staff details, roles, passwords and account status.
        The Administrator account is protected.
    </p>

    <div class="tablewrap">

        <table>

            <thead>

                <tr>
                    <th>Name</th>
                    <th>Employee No.</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Update Details</th>
                    <th>Update Role</th>
                    <th>Password Reset</th>
                    <th>Account Status</th>
                </tr>

            </thead>

            <tbody>

                <?php if (!$rows): ?>

                    <tr>
                        <td colspan="10">
                            No staff accounts found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <td><?= e($r['name']) ?></td>

                            <td>
                                <?= e($r['employee_no'] ?? '-') ?>
                            </td>

                            <td><?= e($r['email']) ?></td>

                            <td>
                                <?= e(
                                    $assignableRoles[$r['role']]
                                    ?? $r['role']
                                ) ?>
                            </td>

                            <td><?= e($r['status']) ?></td>

                            <td><?= e($r['created_at']) ?></td>

                            <!-- UPDATE DETAILS -->

                            <td>

                                <details>

                                    <summary>Edit details</summary>

                                    <form method="post">

                                        <?php csrf_field(); ?>

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update_details"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int)$r['id'] ?>"
                                        >

                                        <label>Name</label>

                                        <input
                                            type="text"
                                            name="name"
                                            value="<?= e($r['name']) ?>"
                                            required
                                        >

                                        <label>Email</label>

                                        <input
                                            type="email"
                                            name="email"
                                            value="<?= e($r['email']) ?>"
                                            required
                                        >

                                        <button type="submit">
                                            Save Details
                                        </button>

                                    </form>

                                </details>

                            </td>

                            <!-- UPDATE ROLE -->

                            <td>

                                <form method="post">

                                    <?php csrf_field(); ?>

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update_role"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int)$r['id'] ?>"
                                    >

                                    <select name="role" required>

                                        <?php foreach ($assignableRoles as $value => $label): ?>

                                            <option
                                                value="<?= e($value) ?>"
                                                <?= $r['role'] === $value ? 'selected' : '' ?>
                                            >

                                                <?= e($label) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <button type="submit">
                                        Update Role
                                    </button>

                                </form>

                            </td>

                            <!-- RESET PASSWORD -->

                            <td>

                                <details>

                                    <summary>Reset password</summary>

                                    <form method="post">

                                        <?php csrf_field(); ?>

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reset_password"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int)$r['id'] ?>"
                                        >

                                        <label>New Password</label>

                                        <input
                                            type="password"
                                            name="new_password"
                                            minlength="10"
                                            placeholder="Minimum 10 characters"
                                            required
                                        >

                                        <button type="submit">
                                            Reset Password
                                        </button>

                                    </form>

                                </details>

                            </td>

                            <!-- ACTIVATE / DEACTIVATE -->

                            <td>

                                <form method="post">

                                    <?php csrf_field(); ?>

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="toggle_status"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int)$r['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        onclick="return confirm('Change this staff account status?');"
                                    >

                                        <?= $r['status'] === 'active'
                                            ? 'Deactivate'
                                            : 'Activate' ?>

                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>


<script>

const staffRoles = <?= json_encode(array_values($staffRoles)) ?>;

function toggleAccountFields() {

    const type = document.getElementById('account_type').value;

    const isEmployeeLinked = staffRoles.includes(type);

    const employeeField =
        document.getElementById('employee_field');

    const employeeSelect =
        document.getElementById('employee_id');

    const accountNameField =
        document.getElementById('account_name_field');

    const accountNameInput =
        document.querySelector('[name="account_name"]');

    employeeField.style.display =
        isEmployeeLinked ? 'block' : 'none';

    accountNameField.style.display =
        isEmployeeLinked ? 'none' : 'block';

    employeeSelect.required = isEmployeeLinked;

    accountNameInput.required = !isEmployeeLinked;
}

toggleAccountFields();

</script>

<?php require 'includes/footer.php'; ?>
