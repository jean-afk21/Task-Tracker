<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../public/database.config.php';

if (!isset($_SESSION['account_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

require_once __DIR__ . '/../../models/task.php';
require_once __DIR__ . '/../../controllers/task.php';

$account_id = (int)$_SESSION['account_id'];
$controller = new TaskController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

$message    = "";
$errors     = "";
$searchTerm = trim($_GET['search'] ?? '');
$motivation = $_SESSION['motivation'] ?? '';
unset($_SESSION['motivation']);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_task"])) {
    $task_id = (int)($_POST["task_id"] ?? 0);
    if ($task_id) {
        $result  = $controller->deleteTask($task_id, $account_id);
        $message = $result ? "Task deleted." : "";
        $errors  = $result ? "" : "Could not delete task.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["complete_task"])) {
    $task_id = (int)($_POST["task_id"] ?? 0);
    if ($task_id) {
        $result  = $controller->markComplete($task_id, $account_id);
        $message = $result ? "Task marked as complete!" : "";
        $errors  = $result ? "" : "Could not update task.";
        if ($result) { $motivation = 'Done. Keep going.'; }
    }
}

$tasks  = $controller->getAllTasks($account_id, $searchTerm);
$counts = $controller->getTaskCounts($account_id);
?>
<?php require __DIR__ . '/../partial/header.php'; ?>

<!-- MAIN -->
<main class="main">

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
    <?php endif; ?>

    <!-- TOP BAR -->
    <div class="topbar">
        <div>
            <div class="welcome-small"><?= date('l, F j') ?></div>
            <h1>Dashboard</h1>
        </div>

        <div class="profile">
            <div class="profile-icon"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)) ?></div>
            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong>
        </div>
    </div>

    <!-- WELCOME -->
    <div class="welcome-card">
        <div>
            <h2>Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>! 👋</h2>
            <p>Stay organized and keep making progress today.</p>
            <a href="<?= BASE_URL ?>/views/tasks/add.php" class="new-task">+ New Task</a>
        </div>

        <div class="welcome-icon">✓</div>
    </div>

    <!-- STATS -->
    <h2 class="section-title">Overview</h2>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Tasks</span>
                <div class="stat-icon">📝</div>
            </div>
            <div class="stat-number"><?= (int)($counts['total'] ?? 0) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Completed</span>
                <div class="stat-icon">✓</div>
            </div>
            <div class="stat-number"><?= (int)($counts['finished'] ?? 0) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Pending</span>
                <div class="stat-icon">⏳</div>
            </div>
            <div class="stat-number"><?= max(0, (int)($counts['total'] ?? 0) - (int)($counts['finished'] ?? 0)) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Completion Rate</span>
                <div class="stat-icon">📈</div>
            </div>
            <div class="stat-number"><?= ((int)($counts['total'] ?? 0) > 0)
                ? round(((int)($counts['finished'] ?? 0) / (int)($counts['total'] ?? 1)) * 100)
                : 0 ?>%</div>
        </div>
    </div>

    <!-- LOWER CONTENT -->
    <div class="content-grid">

        <!-- RECENT TASKS -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Tasks</h3>
                <a href="<?= BASE_URL ?>/views/tasks/index.php" class="view-all">View all</a>
            </div>

            <?php if ($tasks->num_rows === 0): ?>
                <div style="text-align: center; padding: 30px; color: #999;">
                    <p>No tasks yet. <a href="<?= BASE_URL ?>/views/tasks/add.php">Create one</a></p>
                </div>
            <?php else: ?>
                <?php 
                $count = 0;
                while ($task = $tasks->fetch_assoc() && $count < 5): 
                    $count++;
                ?>
                <div class="task">
                    <div class="task-check <?= $task['status'] === 'complete' ? 'checked' : '' ?>"></div>

                    <div class="task-info">
                        <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                        <div class="task-date"><?= !empty($task['due_date']) ? 'Due ' . htmlspecialchars(date('M j', strtotime($task['due_date']))) : 'No due date' ?></div>
                    </div>

                    <span class="priority priority-<?= strtolower($task['priority'] ?? 'medium') ?>"><?= htmlspecialchars($task['priority'] ?? 'Medium') ?></span>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- PROGRESS -->
        <div class="card">
            <div class="card-header">
                <h3>My Progress</h3>
            </div>

            <p style="color:#777; font-size:14px;">
                <?php 
                $total = (int)($counts['total'] ?? 0);
                if ($total > 0) {
                    $finished = (int)($counts['finished'] ?? 0);
                    $percentage = round(($finished / $total) * 100);
                    echo "You're making great progress! $finished of $total tasks done.";
                } else {
                    echo "Get started by creating your first task!";
                }
                ?>
            </p>

            <div class="progress-container">
                <div class="progress-label">
                    <span>Tasks completed</span>
                    <strong><?= ((int)($counts['total'] ?? 0) > 0)
                        ? round(((int)($counts['finished'] ?? 0) / (int)($counts['total'] ?? 1)) * 100)
                        : 0 ?>%</strong>
                </div>

                <div class="progress-bar">
                    <div class="progress" style="width: <?= ((int)($counts['total'] ?? 0) > 0)
                        ? round(((int)($counts['finished'] ?? 0) / (int)($counts['total'] ?? 1)) * 100)
                        : 0 ?>%;"></div>
                </div>
            </div>

            <div style="margin-top:30px; font-size:14px; color:#777;">
                Keep going — every completed task brings you closer to your goals.
            </div>
        </div>

    </div>

</main>

<?php require __DIR__ . '/../partial/footer.php'; ?>