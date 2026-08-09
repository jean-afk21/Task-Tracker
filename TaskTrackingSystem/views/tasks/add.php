<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// If no user is logged in, send them back to the login page.
if (!isset($_SESSION['account_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

// Load task model and controller functions.
require_once __DIR__ . '/../../models/task.php';
require_once __DIR__ . '/../../controllers/task.php';
require_once __DIR__ . '/../../public/database.config.php';

$account_id = (int)$_SESSION['account_id'];
$controller = new TaskController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

$errors = "";

// Process form submission when the user clicks Add Task.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_task"])) {
    $title       = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $due_date    = trim($_POST["due_date"] ?? "") ?: null;
    $priority    = trim($_POST["priority"] ?? "Medium");
    $category    = trim($_POST["category"] ?? "Personal");

    if (empty($title)) {
        $errors = "Task title is required.";
    } else {
        $result = $controller->addTask($account_id, $title, $description, $due_date, $priority, $category);
        if ($result) {
            $_SESSION['motivation'] = 'Another task, another step forward.';
            header("Location: " . BASE_URL . "/views/dashboard/dashboard.php");
            exit();
        } else {
            $errors = "Could not add task. Please try again.";
        }
    }
}
?>
<?php require __DIR__ . '/../partial/header.php'; ?>

<div class="container section">

    <div class="flex-between mb-2">
        <h2>Add New Task</h2>
        <a href="<?= BASE_URL ?>/views/dashboard/dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
    <?php endif; ?>

    <div class="card p-2" style="max-width:620px;">
        <form method="POST">
            <div class="form-group">
                <label for="title">Task Title <span style="color:#dc2626;">*</span></label>
                <input type="text" id="title" name="title"
                    placeholder="e.g. Finish assignment, Buy groceries"
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                    required>
            </div>
            <div class="form-group">
                <label for="description">Description <span style="color:#94a3b8;">(optional)</span></label>
                <textarea id="description" name="description" rows="4"
                    placeholder="Add more details about this task..."
                ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date <span style="color:#94a3b8;">(optional)</span></label>
                <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <?php $selectedPriority = $_POST['priority'] ?? 'Medium'; ?>
                    <option value="Low" <?= $selectedPriority === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $selectedPriority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= $selectedPriority === 'High' ? 'selected' : '' ?>>High</option>
                </select>
            </div>
            <div class="form-group">
                <label for="category">Category / Tag</label>
                <?php $selectedCategory = $_POST['category'] ?? 'Personal'; ?>
                <select id="category" name="category">
                    <option value="Personal" <?= $selectedCategory === 'Personal' ? 'selected' : '' ?>>Personal</option>
                    <option value="Work" <?= $selectedCategory === 'Work' ? 'selected' : '' ?>>Work</option>
                    <option value="School" <?= $selectedCategory === 'School' ? 'selected' : '' ?>>School</option>
                    <option value="Project" <?= $selectedCategory === 'Project' ? 'selected' : '' ?>>Project</option>
                    <option value="Other" <?= $selectedCategory === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="flex" style="gap:0.75rem;">
                <button type="submit" name="add_task" class="btn btn-primary">Add Task</button>
                <a href="<?= BASE_URL ?>/views/dashboard/dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

</div>

<?php require __DIR__ . '/../partial/footer.php'; ?>