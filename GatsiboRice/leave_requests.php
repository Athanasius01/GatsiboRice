
<?php
require 'config/config.php';

$u = require_role(['admin', 'hr']);
$page_title = 'Leave Request Management';

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $requestId = (int)($_POST['request_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $reviewNotes = trim($_POST['review_notes'] ?? '');

    if ($requestId <= 0 || !in_array($decision, ['Approved', 'Rejected'], true)) {
        $error = 'Invalid leave request decision.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE leave_requests
            SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ? AND status = 'Pending'
        ");

        $stmt->execute([
            $decision,
            $reviewNotes,
            $u['id'],
            $requestId
        ]);

        if ($stmt->rowCount() > 0) {
            $msg = 'Leave request successfully ' . strtolower($decision) . '.';
        } else {
            $error = 'Request not found or it has already been reviewed.';
        }
    }
}

$stmt = $pdo->query("
    SELECT lr.*, e.employee_no, e.name, e.department, e.job_title
    FROM leave_requests lr
    INNER JOIN employees e ON e.id = lr.employee_id
    ORDER BY
        CASE WHEN lr.status = 'Pending' THEN 0 ELSE 1 END,
        lr.created_at DESC
");
$requests = $stmt->fetchAll();

require 'includes/header.php';
?>

<h1>Leave Request Management</h1>
<p class="muted">Review, approve or reject employee leave requests.</p>

<?php if ($msg): ?>
    <div class="alert"><?=e($msg)?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert"><?=e($error)?></div>
<?php endif; ?>

<div class="panel">
    <h2>Employee Leave Requests</h2>

    <?php if (!$requests): ?>
        <p>No leave requests have been submitted.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Leave Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td>
                            <?=e($r['name'])?>
                            <br>
                            <small><?=e($r['employee_no'])?></small>
                        </td>
                        <td><?=e($r['department'] ?? '')?></td>
                        <td><?=e($r['leave_type'])?></td>
                        <td><?=e($r['start_date'])?></td>
                        <td><?=e($r['end_date'])?></td>
                        <td><?=e($r['reason'])?></td>
                        <td><?=e($r['status'])?></td>
                        <td>
                            <?php if ($r['status'] === 'Pending'): ?>
                                <form method="post">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="request_id"
                                           value="<?= (int)$r['id'] ?>">

                                    <label>Review notes</label>
                                    <textarea name="review_notes"
                                              rows="2"></textarea>

                                    <button class="btn" name="decision"
                                            value="Approved">
                                        Approve
                                    </button>

                                    <button class="btn" name="decision"
                                            value="Rejected">
                                        Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                <?=e($r['review_notes'] ?: 'No notes')?>
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
