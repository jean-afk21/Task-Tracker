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

require_once __DIR__ . '/../../controllers/task.php';

$account_id = (int)$_SESSION['account_id'];
$controller = new TaskController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
$searchTerm = trim($_GET['search'] ?? '');
$tasks = $controller->getAllTasks($account_id, $searchTerm);
?>
<?php require __DIR__ . '/../partial/header.php'; ?>

<!-- TASKS: LIST START -->
<!-- View: Tasks list and management -->
<main class="main">

<div class="container section tasks-page">
    <section class="card">
        <div class="section-title-row">
            <div>
                <p class="eyebrow">Tasks</p>
                <h1>Your task list</h1>
                <p class="section-subtitle">Manage all of your tasks from one place.</p>
            </div>
                    <a href="<?= BASE_URL ?>/views/tasks/add.php" class="btn btn-primary btn-pill">New task</a>
            </form>
        </div>

        <?php if ($tasks->num_rows === 0): ?>
            <div class="empty-state card-empty">
                <div class="empty-state-icon">✓</div>
                <?php if (!empty($searchTerm)): ?>
                    <h3>Nothing matches. Add it?</h3>
                    <p>Try a different keyword or add a new task.</p>
                <?php else: ?>
                    <h3>No tasks yet</h3>
                    <p>Create your first task to get started.</p>
                <?php endif; ?>
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
                            <span class="priority-pill priority-<?= strtolower($task['priority'] ?? 'Medium') ?>">
                                <?= htmlspecialchars($task['priority'] ?? 'Medium') ?>
                            </span>
                            <a href="<?= BASE_URL ?>/views/tasks/edit.php?id=<?= (int)$task['id'] ?>" class="btn btn-secondary btn-sm">Adjust</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

</main>

<?php require __DIR__ . '/../partial/footer.php'; ?>