
<?php
require '../config/config.php';

$u = require_role(['employee']);
$employeeId = (int)($u['employee_id'] ?? 0);

if ($employeeId <= 0) {
    exit('Your account is not linked to an employee record.');
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $leaveType = $_POST['leave_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    $allowedTypes = [
        'Annual Leave',
        'Sick Leave',
        'Maternity Leave',
        'Paternity Leave',
        'Emergency Leave',
        'Unpaid Leave',
        'Other'
    ];

    $start = DateTime::createFromFormat('!Y-m-d', $startDate);
    $end = DateTime::createFromFormat('!Y-m-d', $endDate);

    if (!in_array($leaveType, $allowedTypes, true)) {
        $error = 'Please select a valid leave type.';
    } elseif (
        !$start || $start->format('Y-m-d') !== $startDate ||
        !$end || $end->format('Y-m-d') !== $endDate
    ) {
        $error = 'Please enter valid leave dates.';
    } elseif ($end < $start) {
        $error = 'The end date cannot be before the start date.';
    } elseif ($reason === '') {
        $error = 'Please provide a reason for your leave request.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO leave_requests
            (employee_id, leave_type, start_date, end_date, reason)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $employeeId,
            $leaveType,
            $startDate,
            $endDate,
            $reason
        ]);

        $msg = 'Your leave request has been submitted successfully.';
    }
}

$stmt = $pdo->prepare("
    SELECT *
    FROM leave_requests
    WHERE employee_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$employeeId]);
$requests = $stmt->fetchAll();

$page_title = 'My Leave Requests';
require '../includes/header.php';
?>

<h1>My Leave Requests</h1>
<p class="muted">Submit a leave request and track its approval status.</p>

<?php if ($msg): ?>
    <div class="alert"><?=e($msg)?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert"><?=e($error)?></div>
<?php endif; ?>

<div class="panel">
    <h2>Submit Leave Request</h2>

    <form method="post">
        <?php csrf_field(); ?>

        <label>Leave type</label>
        <select name="leave_type" required>
            <option value="">Select leave type</option>
            <option>Annual Leave</option>
            <option>Sick Leave</option>
            <option>Maternity Leave</option>
            <option>Paternity Leave</option>
            <option>Emergency Leave</option>
            <option>Unpaid Leave</option>
            <option>Other</option>
        </select>

        <label>Start date</label>
        <input type="date" name="start_date" required>

        <label>End date</label>
        <input type="date" name="end_date" required>

        <label>Reason</label>
        <textarea name="reason" rows="4" required></textarea>

        <p><button class="btn" type="submit">Submit Request</button></p>
    </form>
</div>

<div class="panel">
    <h2>My Leave History</h2>

    <?php if (!$requests): ?>
        <p>You have not submitted any leave requests yet.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Review Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?=e($request['leave_type'])?></td>
                            <td><?=e($request['start_date'])?></td>
                            <td><?=e($request['end_date'])?></td>
                            <td><?=e($request['status'])?></td>
                            <td><?=e($request['reason'])?></td>
                            <td><?=e($request['review_notes'] ?: '—')?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>
