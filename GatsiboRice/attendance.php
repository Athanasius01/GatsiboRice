
<?php
require 'config/config.php';

$u = require_role(['hr']);
$isAdmin = ($u['role'] === 'admin');

$page_title = 'Attendance Management';
$msg = '';
$error = '';

/* Only HR can record or update attendance */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($isAdmin) {
        http_response_code(403);
        exit('Admin has view-only access to attendance.');
    }

    require_staff_action(['hr']);
    csrf_check();

    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $attendanceDate = $_POST['attendance_date'] ?? '';
    $status = $_POST['status'] ?? '';
    $checkIn = $_POST['check_in'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    $allowedStatuses = ['present', 'absent', 'late', 'leave'];

    if (
        $employeeId <= 0 ||
        !in_array($status, $allowedStatuses, true) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendanceDate)
    ) {
        $error = 'Please enter valid attendance information.';
    } else {
        $checkEmployee = $pdo->prepare(
            "SELECT id FROM employees WHERE id = ? AND status = 'active'"
        );
        $checkEmployee->execute([$employeeId]);

        if (!$checkEmployee->fetch()) {
            $error = 'Selected employee was not found or is inactive.';
        } else {
            $s = $pdo->prepare("
                INSERT INTO attendance
                (employee_id, attendance_date, status, check_in, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    check_in = VALUES(check_in),
                    notes = VALUES(notes),
                    recorded_by = VALUES(recorded_by)
            ");

            $s->execute([
                $employeeId,
                $attendanceDate,
                $status,
                $checkIn ?: null,
                $notes,
                $u['id']
            ]);

            log_action('Attendance recorded', 'Employee ID ' . $employeeId);
            $msg = 'Attendance saved successfully.';
        }
    }
}

/* Date filter */
$selectedDate = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

/* Attendance statistics */
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'present') AS present_count,
        SUM(status = 'absent') AS absent_count,
        SUM(status = 'late') AS late_count,
        SUM(status = 'leave') AS leave_count
    FROM attendance
    WHERE attendance_date = ?
");
$statsStmt->execute([$selectedDate]);
$stats = $statsStmt->fetch();

$present = (int)($stats['present_count'] ?? 0);
$absent = (int)($stats['absent_count'] ?? 0);
$late = (int)($stats['late_count'] ?? 0);
$leave = (int)($stats['leave_count'] ?? 0);
$total = $present + $absent + $late + $leave;

/* Active employees */
$emps = $pdo->query("
    SELECT id, employee_no, name
    FROM employees
    WHERE status = 'active'
    ORDER BY name
")->fetchAll();

/* Attendance history */
$rowsStmt = $pdo->prepare("
    SELECT a.*, e.name, e.employee_no
    FROM attendance a
    JOIN employees e ON e.id = a.employee_id
    WHERE a.attendance_date = ?
    ORDER BY e.name ASC
");
$rowsStmt->execute([$selectedDate]);
$rows = $rowsStmt->fetchAll();

require 'includes/header.php';
?>

<h1>Employee Attendance Management</h1>

<?php if ($msg): ?>
    <div class="alert success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="panel">
    <h2>Attendance overview</h2>

    <form method="get">
        <label>Choose attendance date</label>
        <input
            type="date"
            name="date"
            value="<?= e($selectedDate) ?>"
            required
        >
        <button type="submit">View attendance</button>
    </form>

    <div class="formgrid">
        <div>
            <h3>Total records</h3>
            <strong><?= $total ?></strong>
        </div>
        <div>
            <h3>Present</h3>
            <strong><?= $present ?></strong>
        </div>
        <div>
            <h3>Absent</h3>
            <strong><?= $absent ?></strong>
        </div>
        <div>
            <h3>Late</h3>
            <strong><?= $late ?></strong>
        </div>
        <div>
            <h3>On leave</h3>
            <strong><?= $leave ?></strong>
        </div>
    </div>
</div>

<div class="panel">
    <h2>Attendance pie chart</h2>

    <?php if ($total > 0): ?>
        <?php
        $presentPercent = ($present / $total) * 100;
        $absentPercent = ($absent / $total) * 100;
        $latePercent = ($late / $total) * 100;
        $leavePercent = ($leave / $total) * 100;
        ?>

        <div style="
            width:220px;
            height:220px;
            border-radius:50%;
            margin:20px auto;
            background:conic-gradient(
                #16a34a 0% <?= $presentPercent ?>%,
                #dc2626 <?= $presentPercent ?>% <?= $presentPercent + $absentPercent ?>%,
                #f59e0b <?= $presentPercent + $absentPercent ?>% <?= $presentPercent + $absentPercent + $latePercent ?>%,
                #2563eb <?= $presentPercent + $absentPercent + $latePercent ?>% 100%
            );
        "></div>
    <?php else: ?>
        <p>No attendance records found for this date.</p>
    <?php endif; ?>

    <p>
        <span style="color:#16a34a">●</span> Present:
        <?= $present ?>
    </p>
    <p>
        <span style="color:#dc2626">●</span> Absent:
        <?= $absent ?>
    </p>
    <p>
        <span style="color:#f59e0b">●</span> Late:
        <?= $late ?>
    </p>
    <p>
        <span style="color:#2563eb">●</span> Leave:
        <?= $leave ?>
    </p>
</div>

<?php if (!$isAdmin): ?>
<div class="panel">
    <h2>Record employee attendance</h2>

    <form method="post">
        <?php csrf_field(); ?>

        <div class="formgrid">
            <div>
                <label>Employee</label>
                <select name="employee_id" required>
                    <?php foreach ($emps as $emp): ?>
                        <option value="<?= (int)$emp['id'] ?>">
                            <?= e($emp['employee_no'] . ' - ' . $emp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Date</label>
                <input
                    type="date"
                    name="attendance_date"
                    value="<?= e($selectedDate) ?>"
                    required
                >
            </div>

            <div>
                <label>Status</label>
                <select name="status" required>
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                    <option value="leave">Leave</option>
                </select>
            </div>

            <div>
                <label>Check-in</label>
                <input type="time" name="check_in">
            </div>

            <div>
                <label>Notes</label>
                <input type="text" name="notes">
            </div>
        </div>

        <p><button type="submit">Save attendance</button></p>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <h2>Attendance history - <?= e($selectedDate) ?></h2>

    <div class="tablewrap">
        <table>
            <tr>
                <th>Date</th>
                <th>Employee No.</th>
                <th>Employee</th>
                <th>Status</th>
                <th>Check-in</th>
                <th>Notes</th>
            </tr>

            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['attendance_date']) ?></td>
                    <td><?= e($r['employee_no']) ?></td>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e(ucfirst($r['status'])) ?></td>
                    <td><?= e($r['check_in'] ?? '') ?></td>
                    <td><?= e($r['notes'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$rows): ?>
                <tr>
                    <td colspan="6">No attendance records for this date.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
