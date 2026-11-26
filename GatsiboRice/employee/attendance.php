
<?php
require '../config/config.php';

$u = require_role(['hr']);
$isAdmin = ($u['role'] === 'admin');

$page_title = 'Attendance Management';

$msg = '';
$error = '';

// Only HR can record attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_staff_action(['hr']);
    csrf_check();

    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $date = $_POST['attendance_date'] ?? '';
    $status = $_POST['status'] ?? '';
    $checkIn = $_POST['check_in'] ?: null;
    $notes = trim($_POST['notes'] ?? '');

    $allowedStatuses = ['present', 'absent', 'late', 'leave'];

    if ($employeeId <= 0 || $date === '' || !in_array($status, $allowedStatuses, true)) {
        $error = 'Please complete all required attendance fields.';
    } else {

        $check = $pdo->prepare("
            SELECT id FROM employees
            WHERE id = ? AND status = 'active'
        ");
        $check->execute([$employeeId]);

        if (!$check->fetch()) {
            $error = 'Please select a valid active employee.';
        } else {

            $existing = $pdo->prepare("
                SELECT id FROM attendance
                WHERE employee_id = ? AND attendance_date = ?
            ");
            $existing->execute([$employeeId, $date]);

            if ($existing->fetch()) {
                $error = 'Attendance has already been recorded for this employee on that date.';
            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO attendance
                    (employee_id, attendance_date, status, check_in, notes, recorded_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $employeeId,
                    $date,
                    $status,
                    $checkIn,
                    $notes,
                    $u['id']
                ]);

                log_action(
                    'Attendance recorded',
                    'Employee ID: ' . $employeeId . ', Date: ' . $date . ', Status: ' . $status
                );

                $msg = 'Attendance recorded successfully.';
            }
        }
    }
}

// Attendance statistics for selected date
$selectedDate = $_GET['date'] ?? date('Y-m-d');

$dateCheck = DateTime::createFromFormat('Y-m-d', $selectedDate);

if (!$dateCheck || $dateCheck->format('Y-m-d') !== $selectedDate) {
    $selectedDate = date('Y-m-d');
}

$stats = [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'leave' => 0
];

$statStmt = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM attendance
    WHERE attendance_date = ?
    GROUP BY status
");
$statStmt->execute([$selectedDate]);

foreach ($statStmt->fetchAll() as $row) {
    $key = strtolower($row['status']);

    if (isset($stats[$key])) {
        $stats[$key] = (int)$row['total'];
    }
}

$totalAttendance = array_sum($stats);

$present = $stats['present'];
$absent = $stats['absent'];
$late = $stats['late'];
$leave = $stats['leave'];

if ($totalAttendance > 0) {
    $p1 = ($present / $totalAttendance) * 100;
    $p2 = ($absent / $totalAttendance) * 100;
    $p3 = ($late / $totalAttendance) * 100;

    $pieStyle = "conic-gradient(
        #16a34a 0% {$p1}%,
        #dc2626 {$p1}% " . ($p1 + $p2) . "%,
        #f59e0b " . ($p1 + $p2) . "% " . ($p1 + $p2 + $p3) . "%,
        #64748b " . ($p1 + $p2 + $p3) . "% 100%
    )";
} else {
    $pieStyle = "conic-gradient(#d1d5db 0% 100%)";
}

// Get attendance history
$historyStmt = $pdo->prepare("
    SELECT
        a.id,
        a.attendance_date,
        a.status,
        a.check_in,
        a.notes,
        e.employee_no,
        e.name AS employee_name
    FROM attendance a
    INNER JOIN employees e ON e.id = a.employee_id
    WHERE a.attendance_date = ?
    ORDER BY e.name ASC
");
$historyStmt->execute([$selectedDate]);
$records = $historyStmt->fetchAll();

// Employee list for HR form
$employees = [];

if (!$isAdmin) {
    $employees = $pdo->query("
        SELECT id, employee_no, name
        FROM employees
        WHERE status = 'active'
        ORDER BY name
    ")->fetchAll();
}

require 'includes/header.php';
?>

<h1>Attendance Overview</h1>

<p class="muted">
    <?= $isAdmin
        ? 'Read-only attendance monitoring and employee attendance statistics.'
        : 'Record and monitor employee attendance.' ?>
</p>

<?php if ($msg): ?>
    <div class="alert"><?=e($msg)?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert"><?=e($error)?></div>
<?php endif; ?>

<section class="panel">

    <h2>Attendance Statistics</h2>

    <form method="GET" style="margin-bottom:20px;">
        <label for="date">Select attendance date</label>

        <input
            type="date"
            id="date"
            name="date"
            value="<?=e($selectedDate)?>"
            required
        >

        <button type="submit" class="btn">View Statistics</button>
    </form>

    <div class="cards">

        <div class="card">
            <div class="muted">Present</div>
            <div class="metric"><?=$present?></div>
        </div>

        <div class="card">
            <div class="muted">Absent</div>
            <div class="metric"><?=$absent?></div>
        </div>

        <div class="card">
            <div class="muted">Late</div>
            <div class="metric"><?=$late?></div>
        </div>

        <div class="card">
            <div class="muted">On Leave</div>
            <div class="metric"><?=$leave?></div>
        </div>

    </div>

    <h2>Attendance Pie Chart</h2>

    <div style="display:flex;align-items:center;justify-content:center;gap:35px;flex-wrap:wrap;padding:20px;">

        <div style="
            width:210px;
            height:210px;
            border-radius:50%;
            background:<?=$pieStyle?>;
            display:flex;
            align-items:center;
            justify-content:center;
        ">
            <div style="
                width:115px;
                height:115px;
                border-radius:50%;
                background:var(--card-bg,#fff);
                display:flex;
                flex-direction:column;
                align-items:center;
                justify-content:center;
            ">
                <strong style="font-size:28px;"><?=$totalAttendance?></strong>
                <small class="muted">Records</small>
            </div>
        </div>

        <div>
            <p><span style="color:#16a34a;">●</span> Present: <?=$present?></p>
            <p><span style="color:#dc2626;">●</span> Absent: <?=$absent?></p>
            <p><span style="color:#f59e0b;">●</span> Late: <?=$late?></p>
            <p><span style="color:#64748b;">●</span> On Leave: <?=$leave?></p>
        </div>

    </div>

    <?php if ($totalAttendance === 0): ?>
        <p class="muted">No attendance records exist for this date.</p>
    <?php endif; ?>

</section>

<?php if (!$isAdmin): ?>

<section class="panel">

    <h2>Record Employee Attendance</h2>

    <form method="POST">

        <?php csrf_field(); ?>

        <label>Employee</label>

        <select name="employee_id" required>
            <option value="">Select employee</option>

            <?php foreach ($employees as $employee): ?>
                <option value="<?=$employee['id']?>">
                    <?=e($employee['employee_no'])?> -
                    <?=e($employee['name'])?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Date</label>
        <input
            type="date"
            name="attendance_date"
            value="<?=date('Y-m-d')?>"
            required
        >

        <label>Status</label>
        <select name="status" required>
            <option value="">Select status</option>
            <option value="present">Present</option>
            <option value="absent">Absent</option>
            <option value="late">Late</option>
            <option value="leave">Leave</option>
        </select>

        <label>Check-in Time</label>
        <input type="time" name="check_in">

        <label>Notes</label>
        <textarea name="notes" rows="3" placeholder="Optional attendance notes"></textarea>

        <button type="submit" class="btn">Save Attendance</button>

    </form>

</section>

<?php endif; ?>

<section class="panel">

    <h2>Attendance History</h2>

    <p class="muted">
        Records for <?=e($selectedDate)?>
    </p>

    <?php if (!$records): ?>

        <p>No attendance records found for this date.</p>

    <?php else: ?>

        <div style="overflow-x:auto;">

            <table>
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Employee Name</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Check-in</th>
                        <th>Notes</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($records as $record): ?>

                        <tr>
                            <td><?=e($record['employee_no'])?></td>
                            <td><?=e($record['employee_name'])?></td>
                            <td><?=e($record['attendance_date'])?></td>
                            <td><?=e(ucfirst($record['status']))?></td>
                            <td><?=e($record['check_in'] ?: 'Not recorded')?></td>
                            <td><?=e($record['notes'] ?: '—')?></td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</section>

<?php require 'includes/footer.php'; ?>
