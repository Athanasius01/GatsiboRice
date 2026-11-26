
<?php
require '../config/config.php';

$u = require_role(['employee']);

$employeeId = (int)($u['employee_id'] ?? 0);

if ($employeeId <= 0) {
    exit('Your login account is not linked to an employee record. Please contact the administrator.');
}

$stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ? LIMIT 1');
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

if (!$employee) {
    exit('Employee record not found. Please contact the administrator.');
}

$page_title = 'My Employee Dashboard';
require '../includes/header.php';
?>

<h1>Welcome, <?=e($employee['name'])?></h1>

<p class="muted">
    Your personal employee workspace at Gatsibo Rice Co. Ltd.
</p>

<div class="panel">
    <h2>My Profile</h2>

    <?php if (!empty($employee['photo'])): ?>
        <p>
            <img
                src="../uploads/employees/<?=e($employee['photo'])?>"
                alt="My employee photo"
                style="width:130px;height:130px;object-fit:cover;border-radius:50%;"
            >
        </p>
    <?php endif; ?>

    <h3>Personal Information</h3>

    <p><strong>Employee number:</strong> <?=e($employee['employee_no'])?></p>
    <p><strong>Full name:</strong> <?=e($employee['name'])?></p>
    <p><strong>Gender:</strong> <?=e($employee['gender'] ?? 'Not provided')?></p>
    <p><strong>National ID:</strong> <?=e($employee['national_id'] ?? 'Not provided')?></p>
    <p><strong>Date of birth:</strong> <?=e($employee['date_of_birth'] ?? 'Not provided')?></p>
    <p><strong>Phone:</strong> <?=e($employee['phone'] ?? 'Not provided')?></p>
    <p><strong>Email:</strong> <?=e($employee['email'] ?? 'Not provided')?></p>

    <hr>

    <h3>Employment Information</h3>

    <p><strong>Department:</strong> <?=e($employee['department'] ?? 'Not provided')?></p>
    <p><strong>Job title:</strong> <?=e($employee['job_title'] ?? 'Not provided')?></p>
    <p><strong>Hire date:</strong> <?=e($employee['hire_date'] ?? 'Not provided')?></p>
    <p><strong>Contract type:</strong> <?=e($employee['contract_type'] ?? 'Not provided')?></p>
    <p><strong>Employment status:</strong> <?=e($employee['status'])?></p>

    <hr>

    <h3>Salary Information</h3>

    <p>
        <strong>Basic salary:</strong>
        <?=number_format((float)$employee['basic_salary'], 2)?> RWF
    </p>
</div>

<?php require '../includes/footer.php'; ?>
