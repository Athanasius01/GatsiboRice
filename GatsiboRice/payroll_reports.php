
<?php
require 'config/config.php';

$u = require_role(['admin', 'hr', 'accountant']);

$page_title = 'Monthly Payroll Reports';

$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

if ($month < 1 || $month > 12) {
    $month = (int)date('n');
}

if ($year < 2020 || $year > 2100) {
    $year = (int)date('Y');
}

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March',
    4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September',
    10 => 'October', 11 => 'November', 12 => 'December'
];

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_records,
        SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) AS draft_count,
        SUM(CASE WHEN status = 'Paid' THEN basic_salary ELSE 0 END) AS basic_total,
        SUM(CASE WHEN status = 'Paid' THEN allowances ELSE 0 END) AS allowance_total,
        SUM(CASE WHEN status = 'Paid' THEN deductions ELSE 0 END) AS deduction_total,
        SUM(CASE WHEN status = 'Paid' THEN net_salary ELSE 0 END) AS net_total
    FROM payroll_records
    WHERE payroll_month = ? AND payroll_year = ?
");
$stmt->execute([$month, $year]);
$summary = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT
        p.*,
        e.employee_no,
        e.name,
        e.department,
        e.job_title
    FROM payroll_records p
    INNER JOIN employees e ON e.id = p.employee_id
    WHERE p.payroll_month = ? AND p.payroll_year = ?
    ORDER BY e.name ASC
");
$stmt->execute([$month, $year]);
$records = $stmt->fetchAll();

require 'includes/header.php';
?>

<style>
.report-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.report-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 18px;
}
.report-card h3 {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}
.report-card strong {
    font-size: 21px;
    color: #26734d;
}
.report-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: end;
    margin-bottom: 20px;
}
@media print {
    .no-print, nav, aside, header button {
        display: none !important;
    }
    body {
        background: white !important;
    }
    .report-card {
        break-inside: avoid;
    }
}
</style>

<h1>Monthly Payroll Reports</h1>
<p class="muted">
    Review employee payroll records and monthly salary payments.
</p>

<div class="panel no-print">
    <form method="get" class="report-actions">

        <div>
            <label>Payroll Month</label>
            <select name="month">
                <?php foreach ($monthNames as $number => $name): ?>
                    <option value="<?= $number ?>"
                        <?= $month === $number ? 'selected' : '' ?>>
                        <?= e($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Payroll Year</label>
            <input type="number" name="year"
                value="<?= e($year) ?>"
                min="2020" max="2100">
        </div>

        <button type="submit" class="btn">View Report</button>

        <button type="button" class="btn"
            onclick="window.print()">
            Print Report
        </button>

    </form>
</div>

<h2>
    <?= e($monthNames[$month]) ?> <?= e($year) ?> Summary
</h2>

<div class="report-summary">

    <div class="report-card">
        <h3>Total Payroll Records</h3>
        <strong><?= (int)$summary['total_records'] ?></strong>
    </div>

    <div class="report-card">
        <h3>Employees Paid</h3>
        <strong><?= (int)$summary['paid_count'] ?></strong>
    </div>

    <div class="report-card">
        <h3>Approved Records</h3>
        <strong><?= (int)$summary['approved_count'] ?></strong>
    </div>

    <div class="report-card">
        <h3>Draft Records</h3>
        <strong><?= (int)$summary['draft_count'] ?></strong>
    </div>

    <div class="report-card">
        <h3>Total Basic Salaries Paid</h3>
        <strong><?= number_format((float)$summary['basic_total'], 2) ?> RWF</strong>
    </div>

    <div class="report-card">
        <h3>Total Allowances Paid</h3>
        <strong><?= number_format((float)$summary['allowance_total'], 2) ?> RWF</strong>
    </div>

    <div class="report-card">
        <h3>Total Deductions</h3>
        <strong><?= number_format((float)$summary['deduction_total'], 2) ?> RWF</strong>
    </div>

    <div class="report-card">
        <h3>Total Net Salaries Paid</h3>
        <strong><?= number_format((float)$summary['net_total'], 2) ?> RWF</strong>
    </div>

</div>

<div class="panel">
    <h2>Employee Payroll Details</h2>

    <?php if (!$records): ?>
        <p>No payroll records found for this month.</p>
    <?php else: ?>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?= e($r['employee_no']) ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['department'] ?? '') ?></td>
                        <td><?= number_format((float)$r['basic_salary'], 2) ?></td>
                        <td><?= number_format((float)$r['allowances'], 2) ?></td>
                        <td><?= number_format((float)$r['deductions'], 2) ?></td>
                        <td><strong><?= number_format((float)$r['net_salary'], 2) ?></strong></td>
                        <td><?= e($r['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
