<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// If user is not logged in, send them to login.
if (!isset($_SESSION['account_id'])) {
    header("Location: /TaskTrackingSystem/index.php");
    exit();
}

// Load the task model and controller.
require_once __DIR__ . '/../../models/task.php';
require_once __DIR__ . '/../../controllers/task.php';
require_once __DIR__ . '/../../public/database.config.php';

$account_id = (int)$_SESSION['account_id'];
$controller = new TaskController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

$task_id = (int)($_GET['id'] ?? 0);

if (!$task_id) {
    header("Location: /TaskTrackingSystem/views/dashboard/dashboard.php");
    exit();
}

// Fetch task — also verifies it belongs to this user
$task = $controller->getTaskById($task_id, $account_id);

if (!$task) {
    header("Location: /TaskTrackingSystem/views/dashboard/dashboard.php");
    exit();
}

$errors = "";

// Handle the edit task form submission.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_task"])) {
    $title       = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $due_date    = trim($_POST["due_date"] ?? "") ?: null;
    $priority    = trim($_POST["priority"] ?? "Medium");
    $category    = trim($_POST["category"] ?? "Personal");

    if (empty($title)) {
        $errors = "Task title is required.";
    } else {
        $result = $controller->editTask($task_id, $account_id, $title, $description, $due_date, $priority, $category);
        if ($result) {
            // Redirect to dashboard when the save succeeds.
            header("Location: /TaskTrackingSystem/views/dashboard/dashboard.php");
            exit();
        } else {
            $errors = "Could not update task. Please try again.";
        }
    }
}
?>
<?php require __DIR__ . '/../partial/header.php'; ?>

<div class="container section">

    <div class="flex-between mb-2">
        <h2>Edit Task</h2>
        <a href="/TaskTrackingSystem/views/dashboard/dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
    <?php endif; ?>

    <div class="card p-2" style="max-width:620px;">
        <form method="POST">
            <div class="form-group">
                <label for="title">Task Title <span style="color:#dc2626;">*</span></label>
                <input type="text" id="title" name="title"
                    value="<?= htmlspecialchars($_POST['title'] ?? $task['title']) ?>"
                    required>
            </div>
            <div class="form-group">
                <label for="description">Description <span style="color:#94a3b8;">(optional)</span></label>
                <textarea id="description" name="description" rows="4"
                ><?= htmlspecialchars($_POST['description'] ?? $task['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date <span style="color:#94a3b8;">(optional)</span></label>
                <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($_POST['due_date'] ?? $task['due_date']) ?>">
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <?php $selectedPriority = $_POST['priority'] ?? $task['priority'] ?? 'Medium'; ?>
                <select id="priority" name="priority">
                    <option value="Low" <?= $selectedPriority === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $selectedPriority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= $selectedPriority === 'High' ? 'selected' : '' ?>>High</option>
                </select>
            </div>
            <div class="form-group">
                <label for="category">Category / Tag</label>
                <?php $selectedCategory = $_POST['category'] ?? $task['category'] ?? 'Personal'; ?>
                <select id="category" name="category">
                    <option value="Personal" <?= $selectedCategory === 'Personal' ? 'selected' : '' ?>>Personal</option>
                    <option value="Work" <?= $selectedCategory === 'Work' ? 'selected' : '' ?>>Work</option>
                    <option value="School" <?= $selectedCategory === 'School' ? 'selected' : '' ?>>School</option>
                    <option value="Project" <?= $selectedCategory === 'Project' ? 'selected' : '' ?>>Project</option>
                    <option value="Other" <?= $selectedCategory === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div style="background:#f8fafc; border-radius:6px; padding:0.6rem 0.8rem; margin-bottom:1rem; font-size:0.85rem; color:#64748b;">
                Status: <strong><?= htmlspecialchars(ucfirst($task['status'])) ?></strong>
                &nbsp;&mdash;&nbsp;
                Created: <strong><?= htmlspecialchars(date('M d, Y', strtotime($task['created_at']))) ?></strong>
            </div>
            <div class="flex" style="gap:0.75rem;">
                <button type="submit" name="edit_task" class="btn btn-primary">Save Changes</button>
                <a href="/TaskTrackingSystem/views/dashboard/dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    if (!form) return;

    const focusableFields = Array.from(form.querySelectorAll('input:not([type=hidden]), select'))
        .filter(el => !el.disabled);
    const submitButton = form.querySelector('[type="submit"]');

    focusableFields.forEach((field, index) => {
        field.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();

            const nextField = focusableFields[index + 1];
            if (nextField) {
                nextField.focus();
                if (typeof nextField.select === 'function') {
                    nextField.select();
                }
            } else if (submitButton) {
                submitButton.click();
            } else {
                form.submit();
            }
        });
    });
});
</script>

<?php require __DIR__ . '/../partial/footer.php'; ?>