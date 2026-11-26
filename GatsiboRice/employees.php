
<?php
require 'config/config.php';
require_role(['hr']);

$page_title = 'Employees';
$msg = '';
$error = '';

$departments = [
    'Administration',
    'Human Resources',
    'Finance & Accounting',
    'Procurement',
    'Stores & Inventory',
    'Production',
    'Quality Control',
    'Sales & Marketing',
    'Transport & Logistics'
];

$job_titles = [
    'Managing Director',
    'Operations Manager',
    'Accountant',
    'Human Resources Officer',
    'Procurement Officer',
    'Storekeeper',
    'Production Supervisor',
    'Machine Operator',
    'Quality Control Officer',
    'Sales Officer',
    'Marketing Officer',
    'Driver',
    'General Worker'
];

$contracts = ['Permanent', 'Fixed-term', 'Temporary', 'Casual'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $employee_no = trim($_POST['employee_no'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $national_id = trim($_POST['national_id'] ?? '');
    $dob = $_POST['date_of_birth'] ?? '';
    $salary = $_POST['basic_salary'] ?? '0';
    $contract = $_POST['contract_type'] ?? '';
    $department = $_POST['department'] ?? '';
    $job = $_POST['job_title'] ?? '';
    $hire_date = $_POST['hire_date'] ?? '';
    $status = $_POST['status'] ?? 'active';

    if (
        $employee_no === '' ||
        $name === '' ||
        !in_array($department, $departments, true) ||
        !in_array($job, $job_titles, true) ||
        !in_array($contract, $contracts, true) ||
        !in_array($gender, ['Male', 'Female', 'Other'], true) ||
        !in_array($status, ['active', 'inactive'], true)
    ) {
        $error = 'Please complete all required fields and select valid options.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!is_numeric($salary) || (float)$salary < 0) {
        $error = 'Please enter a valid salary.';
    } else {
        try {
            $photo = null;

            if (
                isset($_FILES['photo']) &&
                $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Photo upload failed.');
                }

                if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                    throw new RuntimeException('Photo must not exceed 2 MB.');
                }

                $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

                if (!$imageInfo) {
                    throw new RuntimeException('Please select a valid image.');
                }

                $mime = $imageInfo['mime'];
                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($extensions[$mime])) {
                    throw new RuntimeException('Only JPG, PNG and WEBP photos are allowed.');
                }

                $folder = __DIR__ . '/uploads/employees/';

                if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
                    throw new RuntimeException('Could not create photo folder.');
                }

                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];

                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $folder . $filename)) {
                    throw new RuntimeException('Could not save employee photo.');
                }

                $photo = 'uploads/employees/' . $filename;
            }

            $s = $pdo->prepare(
                'INSERT INTO employees
                (employee_no, name, phone, email, gender, national_id,
                 date_of_birth, basic_salary, contract_type, photo,
                 department, job_title, hire_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $s->execute([
                $employee_no,
                $name,
                $phone ?: null,
                $email ?: null,
                $gender,
                $national_id ?: null,
                $dob ?: null,
                $salary,
                $contract,
                $photo,
                $department,
                $job,
                $hire_date ?: null,
                $status
            ]);

            log_action('Employee added', $name);
            $msg = 'Employee saved successfully.';

        } catch (Throwable $ex) {
            if (isset($photo) && $photo !== null && isset($folder)) {
                if (is_file($folder . basename($photo))) {
                    unlink($folder . basename($photo));
                }
            }

            $error = $ex instanceof PDOException
                ? 'Could not save employee. Check that the employee number is not already registered.'
                : $ex->getMessage();
        }
    }
}

$rows = $pdo->query(
    'SELECT * FROM employees ORDER BY id DESC'
)->fetchAll();

require 'includes/header.php';
?>

<h1>Employee Management</h1>
<p class="muted">Register and manage Gatsibo Rice Co. Ltd employees.</p>

<?php if ($msg): ?>
    <div class="alert success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="panel">
    <h2>Register New Employee</h2>

    <form method="post" enctype="multipart/form-data">
        <?php csrf_field(); ?>

        <div class="formgrid">

            <div>
                <label>Employee Number *</label>
                <input name="employee_no" required>
            </div>

            <div>
                <label>Full Name *</label>
                <input name="name" required>
            </div>

            <div>
                <label>Phone Number</label>
                <input name="phone" placeholder="07XXXXXXXX">
            </div>

            <div>
                <label>Email Address</label>
                <input type="email" name="email">
            </div>

            <div>
                <label>Gender *</label>
                <select name="gender" required>
                    <option value="">Select gender</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Other</option>
                </select>
            </div>

            <div>
                <label>National ID</label>
                <input name="national_id">
            </div>

            <div>
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth">
            </div>

            <div>
                <label>Department *</label>
                <select name="department" required>
                    <option value="">Select department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= e($d) ?>"><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Job Title *</label>
                <select name="job_title" required>
                    <option value="">Select job title</option>
                    <?php foreach ($job_titles as $j): ?>
                        <option value="<?= e($j) ?>"><?= e($j) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Basic Salary (RWF) *</label>
                <input type="number" name="basic_salary"
                       min="0" step="0.01" value="0" required>
            </div>

            <div>
                <label>Contract Type *</label>
                <select name="contract_type" required>
                    <option value="">Select contract</option>
                    <?php foreach ($contracts as $c): ?>
                        <option value="<?= e($c) ?>"><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Hire Date</label>
                <input type="date" name="hire_date">
            </div>

            <div>
                <label>Employee Photo (Optional)</label>
                <input type="file" name="photo"
                       accept=".jpg,.jpeg,.png,.webp">
                <small>JPG, PNG or WEBP. Maximum 2 MB.</small>
            </div>

            <div>
                <label>Status</label>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

        </div>

        <p><button type="submit">Save Employee</button></p>
    </form>
</div>

<div class="panel">
    <h2>Employee List</h2>

    <div class="tablewrap">
        <table>
            <tr>
                <th>Photo</th>
                <th>No.</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Position</th>
                <th>Salary (RWF)</th>
                <th>Contract</th>
                <th>Status</th>
            </tr>

            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <?php if (!empty($r['photo'])): ?>
                            <img src="<?= e($r['photo']) ?>"
                                 alt="Employee photo"
                                 width="45" height="45"
                                 style="object-fit:cover;border-radius:50%">
                        <?php else: ?>
                            No photo
                        <?php endif; ?>
                    </td>

                    <td><?= e($r['employee_no']) ?></td>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e($r['phone']) ?></td>
                    <td><?= e($r['department']) ?></td>
                    <td><?= e($r['job_title']) ?></td>
                    <td><?= number_format((float)$r['basic_salary'], 0) ?></td>
                    <td><?= e($r['contract_type']) ?></td>
                    <td><?= e($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
