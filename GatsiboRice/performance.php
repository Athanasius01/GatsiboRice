
<?php
require 'config/config.php';

$u = require_role(['admin', 'hr']);
$page_title = 'Employee Performance Management';
$msg = '';

$allowedRatings = [
    'Excellent',
    'Very Good',
    'Good',
    'Needs Improvement',
    'Unsatisfactory'
];

/* CREATE, UPDATE AND DELETE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $reviewId = (int)($_POST['review_id'] ?? 0);

        if ($reviewId > 0) {
            $stmt = $pdo->prepare(
                "DELETE FROM performance_reviews WHERE id = ?"
            );
            $stmt->execute([$reviewId]);

            $msg = $stmt->rowCount()
                ? 'Performance review deleted successfully.'
                : 'Performance review not found.';
        } else {
            $msg = 'Invalid performance review.';
        }
    } elseif ($action === 'create' || $action === 'update') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $period = trim($_POST['review_period'] ?? '');
        $reviewDate = $_POST['review_date'] ?? '';
        $rating = $_POST['rating'] ?? '';
        $strengths = trim($_POST['strengths'] ?? '');
        $improvements = trim($_POST['areas_to_improve'] ?? '');
        $comments = trim($_POST['supervisor_comments'] ?? '');

        $employeeStmt = $pdo->prepare(
            "SELECT id FROM employees WHERE id = ? AND status = 'active'"
        );
        $employeeStmt->execute([$employeeId]);

        if (!$employeeStmt->fetch()) {
            $msg = 'Please select a valid active employee.';
        } elseif (
            $period === '' ||
            $reviewDate === '' ||
            !in_array($rating, $allowedRatings, true)
        ) {
            $msg = 'Please complete all required fields.';
        } elseif (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reviewDate) ||
            !checkdate(
                (int)substr($reviewDate, 5, 2),
                (int)substr($reviewDate, 8, 2),
                (int)substr($reviewDate, 0, 4)
            )
        ) {
            $msg = 'Please enter a valid review date.';
        } elseif ($action === 'update' && $reviewId <= 0) {
            $msg = 'Invalid performance review.';
        } else {
            if ($action === 'update') {
                $stmt = $pdo->prepare("
                    UPDATE performance_reviews
                    SET employee_id = ?,
                        review_period = ?,
                        review_date = ?,
                        rating = ?,
                        strengths = ?,
                        areas_to_improve = ?,
                        supervisor_comments = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $employeeId,
                    $period,
                    $reviewDate,
                    $rating,
                    $strengths,
                    $improvements,
                    $comments,
                    $reviewId
                ]);

                $msg = 'Performance review updated successfully.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO performance_reviews
                    (
                        employee_id,
                        review_period,
                        review_date,
                        rating,
                        strengths,
                        areas_to_improve,
                        supervisor_comments,
                        reviewed_by
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $employeeId,
                    $period,
                    $reviewDate,
                    $rating,
                    $strengths,
                    $improvements,
                    $comments,
                    $u['id']
                ]);

                $msg = 'Performance review saved successfully.';
            }
        }
    }
}

/* EMPLOYEES */
$employees = $pdo->query("
    SELECT id, employee_no, name, department
    FROM employees
    WHERE status = 'active'
    ORDER BY name
")->fetchAll();

/* REVIEW HISTORY */
$reviews = $pdo->query("
    SELECT
        p.*,
        e.employee_no,
        e.name AS employee_name,
        e.department,
        e.job_title,
        u.name AS reviewer_name
    FROM performance_reviews p
    INNER JOIN employees e ON e.id = p.employee_id
    LEFT JOIN users u ON u.id = p.reviewed_by
    ORDER BY p.review_date DESC, p.id DESC
")->fetchAll();

/* VIEW DETAILS */
$selectedReview = null;
$viewId = (int)($_GET['view'] ?? 0);

if ($viewId > 0) {
    $detailStmt = $pdo->prepare("
        SELECT
            p.*,
            e.employee_no,
            e.name AS employee_name,
            e.department,
            e.job_title,
            u.name AS reviewer_name
        FROM performance_reviews p
        INNER JOIN employees e ON e.id = p.employee_id
        LEFT JOIN users u ON u.id = p.reviewed_by
        WHERE p.id = ?
    ");
    $detailStmt->execute([$viewId]);
    $selectedReview = $detailStmt->fetch();

    if (!$selectedReview) {
        $msg = 'Performance review not found.';
    }
}

/* EDIT FORM */
$editReview = null;
$editId = (int)($_GET['edit'] ?? 0);

if ($editId > 0) {
    $editStmt = $pdo->prepare("
        SELECT * FROM performance_reviews WHERE id = ?
    ");
    $editStmt->execute([$editId]);
    $editReview = $editStmt->fetch();

    if (!$editReview) {
        $msg = 'Performance review not found.';
    }
}

require 'includes/header.php';
?>

<h1>Employee Performance Management</h1>
<p class="muted">
    Record and review employee performance at Gatsibo Rice Co. Ltd.
</p>

<?php if ($msg): ?>
    <div class="panel">
        <?= e($msg) ?>
    </div>
<?php endif; ?>

<?php if ($selectedReview): ?>

    <div class="panel">
        <h2>Performance Review Details</h2>

        <p><strong>Employee:</strong>
            <?= e($selectedReview['employee_name']) ?></p>

        <p><strong>Employee Number:</strong>
            <?= e($selectedReview['employee_no']) ?></p>

        <p><strong>Department:</strong>
            <?= e($selectedReview['department'] ?? 'N/A') ?></p>

        <p><strong>Job Title:</strong>
            <?= e($selectedReview['job_title'] ?? 'N/A') ?></p>

        <p><strong>Review Period:</strong>
            <?= e($selectedReview['review_period']) ?></p>

        <p><strong>Review Date:</strong>
            <?= e($selectedReview['review_date']) ?></p>

        <p><strong>Performance Rating:</strong>
            <?= e($selectedReview['rating']) ?></p>

        <hr>

        <h3>Employee Strengths</h3>
        <p><?= nl2br(e($selectedReview['strengths'] ?? 'Not provided')) ?></p>

        <h3>Areas for Improvement</h3>
        <p><?= nl2br(e($selectedReview['areas_to_improve'] ?? 'Not provided')) ?></p>

        <h3>Supervisor Comments</h3>
        <p><?= nl2br(e($selectedReview['supervisor_comments'] ?? 'Not provided')) ?></p>

        <p><strong>Reviewed By:</strong>
            <?= e($selectedReview['reviewer_name'] ?? 'N/A') ?></p>

        <a href="performance.php" class="btn">Back to Performance Reviews</a>
    </div>

<?php elseif ($editReview): ?>

    <div class="panel">
        <h2>Edit Performance Review</h2>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="review_id"
                   value="<?= (int)$editReview['id'] ?>">

            <label>Employee *</label>
            <select name="employee_id" required>
                <option value="">Select employee</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int)$employee['id'] ?>"
                        <?= (int)$employee['id'] === (int)$editReview['employee_id'] ? 'selected' : '' ?>>
                        <?= e($employee['employee_no']) ?> -
                        <?= e($employee['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Review Period *</label>
            <input type="text" name="review_period" required
                   value="<?= e($editReview['review_period']) ?>">

            <label>Review Date *</label>
            <input type="date" name="review_date" required
                   value="<?= e($editReview['review_date']) ?>">

            <label>Performance Rating *</label>
            <select name="rating" required>
                <?php foreach ($allowedRatings as $rating): ?>
                    <option value="<?= e($rating) ?>"
                        <?= $editReview['rating'] === $rating ? 'selected' : '' ?>>
                        <?= e($rating) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Employee Strengths</label>
            <textarea name="strengths" rows="3"><?= e($editReview['strengths'] ?? '') ?></textarea>

            <label>Areas for Improvement</label>
            <textarea name="areas_to_improve" rows="3"><?= e($editReview['areas_to_improve'] ?? '') ?></textarea>

            <label>Supervisor Comments</label>
            <textarea name="supervisor_comments" rows="3"><?= e($editReview['supervisor_comments'] ?? '') ?></textarea>

            <button type="submit" class="btn">Update Review</button>
            <a href="performance.php" class="btn">Cancel</a>
        </form>
    </div>

<?php else: ?>

    <div class="panel">
        <h2>Create Performance Review</h2>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">

            <label>Employee *</label>
            <select name="employee_id" required>
                <option value="">Select employee</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int)$employee['id'] ?>">
                        <?= e($employee['employee_no']) ?> -
                        <?= e($employee['name']) ?>
                        (<?= e($employee['department'] ?? '') ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Review Period *</label>
            <input type="text" name="review_period"
                   placeholder="e.g. November 2026" required>

            <label>Review Date *</label>
            <input type="date" name="review_date"
                   value="<?= date('Y-m-d') ?>" required>

            <label>Performance Rating *</label>
            <select name="rating" required>
                <option value="">Select rating</option>
                <?php foreach ($allowedRatings as $rating): ?>
                    <option value="<?= e($rating) ?>"><?= e($rating) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Employee Strengths</label>
            <textarea name="strengths" rows="3"></textarea>

            <label>Areas for Improvement</label>
            <textarea name="areas_to_improve" rows="3"></textarea>

            <label>Supervisor Comments</label>
            <textarea name="supervisor_comments" rows="3"></textarea>

            <button type="submit" class="btn">
                Save Performance Review
            </button>
        </form>
    </div>

<?php endif; ?>

<div class="panel">
    <h2>Performance Review History</h2>

    <?php if (!$reviews): ?>
        <p>No performance reviews have been recorded yet.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Review Period</th>
                        <th>Review Date</th>
                        <th>Rating</th>
                        <th>Reviewed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td>
                                <?= e($review['employee_no']) ?> -
                                <?= e($review['employee_name']) ?>
                            </td>
                            <td><?= e($review['department'] ?? '') ?></td>
                            <td><?= e($review['review_period']) ?></td>
                            <td><?= e($review['review_date']) ?></td>
                            <td><?= e($review['rating']) ?></td>
                            <td><?= e($review['reviewer_name'] ?? 'N/A') ?></td>
                            <td>
                                <a class="btn"
                                   href="performance.php?view=<?= (int)$review['id'] ?>">
                                    View Details
                                </a>

                                <a class="btn"
                                   href="performance.php?edit=<?= (int)$review['id'] ?>">
                                    Edit
                                </a>

                                <form method="post"
                                      style="display:inline;"
                                      onsubmit="return confirm('Are you sure you want to delete this performance review? This cannot be undone.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="review_id"
                                           value="<?= (int)$review['id'] ?>">
                                    <button type="submit" class="btn">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
