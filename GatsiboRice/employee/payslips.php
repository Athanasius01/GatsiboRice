
<?php
require '../config/config.php';

$u = require_role(['employee']);
$employeeId = (int)($u['employee_id'] ?? 0);

if ($employeeId <= 0) {
    exit('Your account is not linked to an employee record.');
}

$stmt = $pdo->prepare("
    SELECT p.*, e.employee_no, e.name, e.department, e.job_title
    FROM payroll_records p
    INNER JOIN employees e ON e.id = p.employee_id
    WHERE p.employee_id = ?
      AND p.status IN ('Approved', 'Paid')
    ORDER BY p.payroll_year DESC, p.payroll_month DESC
");
$stmt->execute([$employeeId]);
$payslips = $stmt->fetchAll();

$page_title = 'My Payslips';
require '../includes/header.php';

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March',
    4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September',
    10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<style>
.payslip-card {
    background: white;
    color: #222;
    border: 1px solid #ddd;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 8px;
}
.payslip-header {
    text-align: center;
    border-bottom: 3px solid #26734d;
    padding-bottom: 15px;
    margin-bottom: 20px;
}
.payslip-header h2 {
    color: #26734d;
    margin-bottom: 5px;
}
.payslip-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}
.payslip-card table {
    width: 100%;
    border-collapse: collapse;
}
.payslip-card th,
.payslip-card td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
}
.payslip-total {
    background: #eaf5ee;
    font-size: 18px;
    font-weight: bold;
}
.payslip-signatures {
    display: flex;
    justify-content: space-between;
    margin-top: 55px;
}
.signature-line {
    border-top: 1px solid #555;
    width: 40%;
    text-align: center;
    padding-top: 8px;
}
@media print {
    body * {
        visibility: hidden;
    }
    .payslip-card.printing,
    .payslip-card.printing * {
        visibility: visible;
    }
    .payslip-card.printing {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none;
        margin: 0;
        padding: 15px;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<h1 class="no-print">My Payslips</h1>
<p class="muted no-print">
    View and print your approved and paid salary records.
</p>

<?php if (!$payslips): ?>
    <div class="panel">
        <p>No approved payslips are available yet.</p>
    </div>
<?php else: ?>

<?php foreach ($payslips as $p): ?>
    <div class="payslip-card">

        <div class="payslip-header">
            <h2>GATSIBO RICE CO. LTD</h2>
            <p>Employee Salary Statement</p>
            <strong>
                <?=e($monthNames[(int)$p['payroll_month']] ?? 'Unknown')?>
                <?=e($p['payroll_year'])?>
            </strong>
        </div>

        <h3>Employee Information</h3>

        <div class="payslip-info">
            <div>
                <strong>Employee Name:</strong>
                <?=e($p['name'])?>
            </div>

            <div>
                <strong>Employee Number:</strong>
                <?=e($p['employee_no'])?>
            </div>

            <div>
                <strong>Department:</strong>
                <?=e($p['department'] ?? 'N/A')?>
            </div>

            <div>
                <strong>Job Title:</strong>
                <?=e($p['job_title'] ?? 'N/A')?>
            </div>

            <div>
                <strong>Payroll Period:</strong>
                <?=e($monthNames[(int)$p['payroll_month']] ?? 'Unknown')?>
                <?=e($p['payroll_year'])?>
            </div>

            <div>
                <strong>Payment Date:</strong>
                <?=e($p['payment_date'] ?: 'Not recorded')?>
            </div>
        </div>

        <h3>Salary Breakdown</h3>

        <table>
            <tr>
                <th>Description</th>
                <th>Amount (RWF)</th>
            </tr>

            <tr>
                <td>Basic Salary</td>
                <td><?=number_format((float)$p['basic_salary'], 2)?></td>
            </tr>

            <tr>
                <td>Allowances</td>
                <td><?=number_format((float)$p['allowances'], 2)?></td>
            </tr>

            <tr>
                <td>Deductions</td>
                <td><?=number_format((float)$p['deductions'], 2)?></td>
            </tr>

            <tr class="payslip-total">
                <td>NET SALARY</td>
                <td><?=number_format((float)$p['net_salary'], 2)?> RWF</td>
            </tr>
        </table>

        <p>
            <strong>Payment Status:</strong>
            <?=e($p['status'])?>
        </p>

        <div class="payslip-signatures">
            <div class="signature-line">
                Employee Signature
            </div>

            <div class="signature-line">
                Authorized Signature
            </div>
        </div>

        <p style="text-align:center;margin-top:25px;font-size:12px;">
            Generated by Gatsibo Rice Co. Ltd Management System
        </p>

        <div class="no-print" style="margin-top:20px;">
            <button type="button" class="btn"
                onclick="printPayslip(this)">
                Print This Payslip
            </button>
        </div>

    </div>
<?php endforeach; ?>

<?php endif; ?>

<script>
function printPayslip(button) {
    const card = button.closest('.payslip-card');
    card.classList.add('printing');

    window.print();

    card.classList.remove('printing');
}
</script>

<?php require '../includes/footer.php'; ?>
