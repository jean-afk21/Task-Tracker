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

<div class="container section dashboard-page">

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
    <?php endif; ?>

    <div class="dashboard-content">

        <section class="dashboard-header card header-card">
            <div class="header-copy-preview">
                <p class="eyebrow">Happy working</p>
                <h1>Hi, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>.</h1>
                <p class="subheading">Progress is progress — big or small.</p>
            </div>
            <div class="header-actions">
                <div class="date-pill"><?= date('l, F j') ?></div>
                <a href="<?= BASE_URL ?>/views/tasks/add.php" class="btn btn-primary btn-pill">New task</a>
            </div>
        </section>

        <?php if (!empty($motivation)): ?>
        <section class="motivation-banner card">
            <p class="motivation-text"><?= htmlspecialchars($motivation) ?></p>
        </section>
        <?php endif; ?>

        <section class="card stats-panel">
            <div class="section-title-row">
                <div>
                    <h3>Stats</h3>
                    <p class="section-subtitle">Your current task summary</p>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <span><?= (int)($counts['total'] ?? 0) ?></span>
                    <p>Total tasks</p>
                </div>
                <div class="stat-card">
                    <span><?= (int)($counts['finished'] ?? 0) ?></span>
                    <p>Completed</p>
                </div>
                <div class="stat-card">
                    <span><?= max(0, (int)($counts['total'] ?? 0) - (int)($counts['finished'] ?? 0)) ?></span>
                    <p>Pending</p>
                </div>
                <div class="stat-card wide-card">
                    <span><?= ((int)($counts['total'] ?? 0) > 0)
                        ? round(((int)($counts['finished'] ?? 0) / (int)($counts['total'] ?? 1)) * 100)
                        : 0 ?>%</span>
                    <p>Completion rate</p>
                </div>
            </div>
        </section>

        <section class="card tasks-today-card">
            <div class="section-title-row">
                <div>
                    <h2>Today's focus</h2>
                    <p class="section-subtitle">Your priorities right now.</p>
                </div>
                <span class="task-count"><?= $tasks->num_rows ?> items</span>
            </div>

            <?php if ($tasks->num_rows === 0): ?>
            <div class="empty-state card-empty">
                <div class="empty-state-icon">✓</div>
                <h3>All caught up!</h3>
                <p>Everything's done. Start fresh with a new task.</p>
                <a href="<?= BASE_URL ?>/views/tasks/add.php" class="btn btn-primary btn-pill">Create a task</a>
            </div>
            <?php else: ?>
            <div class="task-list">
                <?php while ($task = $tasks->fetch_assoc()): ?>
                <article class="task-row <?= $task['status'] === 'complete' ? 'task-complete' : 'task-pending' ?>">
                    <div class="task-row-main">
                        <div class="task-checkbox">
                            <?php if ($task['status'] === 'complete'): ?>
                                <span class="checkbox checked">✔</span>
                            <?php else: ?>
                                <span class="checkbox"></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3><?= htmlspecialchars($task['title']) ?></h3>
                            <p class="task-meta"><?= htmlspecialchars($task['description'] ?? 'No description provided.') ?></p>
                            <div class="task-label-row">
                                <span class="category-badge"><?= htmlspecialchars($task['category'] ?? 'Personal') ?></span>
                                <?php if (!empty($task['due_date'])): ?>
                                    <span class="due-date">Due <?= htmlspecialchars(date('M j, Y', strtotime($task['due_date']))) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="task-row-actions">
                        <?php $priority = $task['priority'] ?? 'Medium'; ?>
                        <span class="priority-pill priority-<?= strtolower($priority) ?>">
                            <?= htmlspecialchars($priority) ?>
                        </span>
                        <div class="task-action-buttons">
                            <?php if ($task['status'] === 'pending'): ?>
                            <form method="POST" class="action-form">
                                <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                <button type="submit" name="complete_task" class="btn btn-success btn-sm">Done</button>
                            </form>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/views/tasks/edit.php?id=<?= (int)$task['id'] ?>"
                               class="btn btn-secondary btn-sm">Adjust</a>
                            <form method="POST" class="action-form"
                                  onsubmit="return confirm('Delete this task?');">
                                <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                <button type="submit" name="delete_task" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<?php require __DIR__ . '/../partial/footer.php'; ?>