
<?php
require 'config/config.php';

$u = require_role(['hr', 'accountant']);
$page_title = 'Payroll Management';

$msg = '';
$error = '';

$months = [
    1 => 'January', 2 => 'February', 3 => 'March',
    4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September',
    10 => 'October', 11 => 'November', 12 => 'December'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $month = (int)($_POST['payroll_month'] ?? 0);
        $year = (int)($_POST['payroll_year'] ?? 0);

        $basic = $_POST['basic_salary'] ?? '';
        $allowances = $_POST['allowances'] ?? '';
        $deductions = $_POST['deductions'] ?? '';
        $notes = trim($_POST['notes'] ?? '');

        if ($employeeId <= 0) {
            $error = 'Please select an employee.';
        } elseif ($month < 1 || $month > 12) {
            $error = 'Please select a valid payroll month.';
        } elseif ($year < 2020 || $year > 2100) {
            $error = 'Please enter a valid payroll year.';
        } elseif (
            !is_numeric($basic) ||
            !is_numeric($allowances) ||
            !is_numeric($deductions) ||
            !is_finite((float)$basic) ||
            !is_finite((float)$allowances) ||
            !is_finite((float)$deductions) ||
            (float)$basic < 0 ||
            (float)$allowances < 0 ||
            (float)$deductions < 0 ||
            (float)$deductions > (float)$basic + (float)$allowances
        ) {
            $error = 'Please enter valid salary amounts. Deductions cannot exceed total earnings.';
        } else {
            $check = $pdo->prepare("
                SELECT id FROM employees
                WHERE id = ? AND status = 'active'
            ");
            $check->execute([$employeeId]);

            if (!$check->fetch()) {
                $error = 'Selected employee was not found or is inactive.';
            } else {
                $basic = round((float)$basic, 2);
                $allowances = round((float)$allowances, 2);
                $deductions = round((float)$deductions, 2);
                $net = round($basic + $allowances - $deductions, 2);

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO payroll_records
                        (
                            employee_id, payroll_month, payroll_year,
                            basic_salary, allowances, deductions,
                            net_salary, notes, prepared_by
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $employeeId, $month, $year,
                        $basic, $allowances, $deductions,
                        $net, $notes, $u['id']
                    ]);

                    $msg = 'Payroll record created successfully as Draft.';
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $error = 'A payroll record already exists for this employee and month.';
                    } else {
                        throw $e;
                    }
                }
            }
        }
    }

    if ($action === 'approve' || $action === 'paid') {
        $recordId = (int)($_POST['record_id'] ?? 0);

        if ($recordId <= 0) {
            $error = 'Invalid payroll record.';
        } else {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("
                    UPDATE payroll_records
                    SET status = 'Approved'
                    WHERE id = ? AND status = 'Draft'
                ");
                $stmt->execute([$recordId]);
                $msg = $stmt->rowCount()
                    ? 'Payslip approved successfully.'
                    : '';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE payroll_records
                    SET status = 'Paid', payment_date = CURDATE()
                    WHERE id = ? AND status = 'Approved'
                ");
                $stmt->execute([$recordId]);
                $msg = $stmt->rowCount()
                    ? 'Salary payment recorded successfully.'
                    : '';
            }

            if (!$msg) {
                $error = 'The payroll record could not be updated. Check its current status.';
            }
        }
    }
}

$employees = $pdo->query("
    SELECT id, employee_no, name, department
    FROM employees
    WHERE status = 'active'
    ORDER BY name
")->fetchAll();

$records = $pdo->query("
    SELECT p.*, e.employee_no, e.name, e.department
    FROM payroll_records p
    INNER JOIN employees e ON e.id = p.employee_id
    ORDER BY p.payroll_year DESC, p.payroll_month DESC, e.name
")->fetchAll();

require 'includes/header.php';
?>

<h1>Payroll Management</h1>
<p class="muted">Prepare, approve and manage employee salary records.</p>

<?php if ($msg): ?>
    <div class="alert"><?=e($msg)?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert"><?=e($error)?></div>
<?php endif; ?>

<div class="panel">
    <h2>Create Payroll Record</h2>

    <?php if (!$employees): ?>
        <p>No active employees are available.</p>
    <?php else: ?>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="create">

            <label>Employee</label>
            <select name="employee_id" required>
                <option value="">Select employee</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?=(int)$employee['id']?>">
                        <?=e($employee['employee_no'])?> -
                        <?=e($employee['name'])?>
                        (<?=e($employee['department'] ?? '')?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Payroll month</label>
            <select name="payroll_month" required>
                <option value="">Select month</option>
                <?php foreach ($months as $number => $monthName): ?>
                    <option value="<?=$number?>"><?=e($monthName)?></option>
                <?php endforeach; ?>
            </select>

            <label>Payroll year</label>
            <input type="number" name="payroll_year"
                   min="2020" max="2100"
                   value="<?=date('Y')?>" required>

            <label>Basic salary (RWF)</label>
            <input type="number" name="basic_salary"
                   min="0" step="0.01" required>

            <label>Allowances (RWF)</label>
            <input type="number" name="allowances"
                   min="0" step="0.01" value="0" required>

            <label>Deductions (RWF)</label>
            <input type="number" name="deductions"
                   min="0" step="0.01" value="0" required>

            <label>Notes</label>
            <textarea name="notes" rows="3"></textarea>

            <p>
                <button class="btn" type="submit">
                    Save Draft Payroll
                </button>
            </p>
        </form>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>Payroll Records</h2>

    <?php if (!$records): ?>
        <p>No payroll records have been created yet.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Month</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td>
                            <?=e($record['name'])?>
                            <br>
                            <small><?=e($record['employee_no'])?></small>
                        </td>
                        <td>
                            <?=e($months[(int)$record['payroll_month']] ?? '')?>
                            <?=e($record['payroll_year'])?>
                        </td>
                        <td><?=number_format((float)$record['basic_salary'], 2)?> RWF</td>
                        <td><?=number_format((float)$record['allowances'], 2)?> RWF</td>
                        <td><?=number_format((float)$record['deductions'], 2)?> RWF</td>
                        <td>
                            <strong>
                                <?=number_format((float)$record['net_salary'], 2)?> RWF
                            </strong>
                        </td>
                        <td><?=e($record['status'])?></td>
                        <td>
                            <?php if ($record['status'] === 'Draft'): ?>
                                <form method="post">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="record_id"
                                           value="<?=(int)$record['id']?>">
                                    <button class="btn" type="submit">
                                        Approve
                                    </button>
                                </form>
                            <?php elseif ($record['status'] === 'Approved'): ?>
                                <form method="post">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="paid">
                                    <input type="hidden" name="record_id"
                                           value="<?=(int)$record['id']?>">
                                    <button class="btn" type="submit">
                                        Mark as Paid
                                    </button>
                                </form>
                            <?php else: ?>
                                Paid on <?=e($record['payment_date'] ?? '')?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
