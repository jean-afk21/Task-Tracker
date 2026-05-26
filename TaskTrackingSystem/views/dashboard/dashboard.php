<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Require login before showing the dashboard.
if (!isset($_SESSION['account_id'])) {
    header("Location: /TaskTrackingSystem/index.php");
    exit();
}

// Load task model and controller so we can read and update tasks.
require_once __DIR__ . '/../../models/task.php';
require_once __DIR__ . '/../../controllers/task.php';
require_once __DIR__ . '/../../public/database.config.php';

$account_id = (int)$_SESSION['account_id'];
$controller = new TaskController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

$message = "";
$errors  = "";

// Process delete requests from the task list.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_task"])) {
    $task_id = (int)($_POST["task_id"] ?? 0);
    if ($task_id) {
        $result  = $controller->deleteTask($task_id, $account_id);
        $message = $result ? "Task deleted." : "";
        $errors  = $result ? "" : "Could not delete task.";
    }
}

// Process task completion requests from the task list.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["complete_task"])) {
    $task_id = (int)($_POST["task_id"] ?? 0);
    if ($task_id) {
        $result  = $controller->markComplete($task_id, $account_id);
        $message = $result ? "Task marked as complete!" : "";
        $errors  = $result ? "" : "Could not update task.";
    }
}

$tasks = $controller->getAllTasks($account_id);
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

    <div class="dashboard-shell">

        <?php // Left sidebar with user info and navigation ?>
        <aside class="dashboard-sidebar card">
            <div class="sidebar-profile">
                <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)) ?></div>
                <div class="sidebar-details">
                    <p class="sidebar-greeting">Hello, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
                    <h2><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h2>
                    <p class="sidebar-email"><?= htmlspecialchars($_SESSION['username'] ?? 'hello') ?>@example.com</p>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="/TaskTrackingSystem/views/dashboard/dashboard.php" class="sidebar-item active">Dashboard</a>
                <a href="#" class="sidebar-item">Tasks</a>
                <a href="#" class="sidebar-item">Analytics</a>
                <a href="#" class="sidebar-item">Calendar</a>
                <a href="#" class="sidebar-item">Settings</a>
            </nav>
        </aside>

        <div class="dashboard-content">

            <?php // Header area with greeting, date, search, and add task button ?>
            <section class="dashboard-header card header-card">
                <div class="header-copy-preview">
                    <p class="eyebrow">Happy working</p>
                    <h1>Hi, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>.</h1>
                    <p class="subheading">A clean space for your priorities, deadlines, and daily focus.</p>
                </div>
                <div class="header-actions">
                    <div class="date-pill"><?= date('l, F j') ?></div>
                    <div class="search-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="search" placeholder="Search tasks, projects, or dates" aria-label="Search tasks">
                    </div>
                    <a href="/TaskTrackingSystem/views/tasks/add.php" class="btn btn-primary btn-pill">+ Add Task</a>
                </div>
            </section>

            <?php // Project summary cards section ?>
            <section class="project-cards">
                <article class="project-card card project-card-blue">
                    <div class="project-card-head">
                        <p class="project-chip">Web Development</p>
                        <span>8 members</span>
                    </div>
                    <h3>Launch landing page</h3>
                    <div class="project-progress">
                        <span>78%</span>
                        <div class="progress-track"><div style="width:78%"></div></div>
                    </div>
                </article>
                <article class="project-card card project-card-soft">
                    <div class="project-card-head">
                        <p class="project-chip">Mobile App</p>
                        <span>5 tasks</span>
                    </div>
                    <h3>Design new onboarding</h3>
                    <div class="project-progress">
                        <span>46%</span>
                        <div class="progress-track"><div style="width:46%"></div></div>
                    </div>
                </article>
                <article class="project-card card project-card-white">
                    <div class="project-card-head">
                        <p class="project-chip">Brand Refresh</p>
                        <span>2 designers</span>
                    </div>
                    <h3>Update UI components</h3>
                    <div class="project-progress">
                        <span>63%</span>
                        <div class="progress-track"><div style="width:63%"></div></div>
                    </div>
                </article>
            </section>

            <?php // Main dashboard grid with tasks and right info panel ?>
            <section class="dashboard-grid">
                <main class="card tasks-today-card">
                    <div class="section-title-row">
                        <div>
                            <h2>Tasks for today</h2>
                            <p class="section-subtitle">Your top work for the next few hours.</p>
                        </div>
                        <span class="task-count"><?= $tasks->num_rows ?> tasks</span>
                    </div>

                    <?php if ($tasks->num_rows === 0): ?>
                        <div class="empty-state card-empty">
                            <div class="empty-state-icon">✓</div>
                            <h3>No tasks yet</h3>
                            <p>Start by adding a task to organize your day.</p>
                            <a href="/TaskTrackingSystem/views/tasks/add.php" class="btn btn-primary btn-pill">Add Your First Task</a>
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
                                                    <button type="submit" name="complete_task" class="btn btn-success btn-sm">Mark done</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="/TaskTrackingSystem/views/tasks/edit.php?id=<?= (int)$task['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        </div>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </main>

                <?php // Right panel showing productivity and upcoming deadlines ?>
                <aside class="schedule-panel">
                    <section class="card stats-panel">
                        <div class="section-title-row">
                            <div>
                                <h3>Productivity</h3>
                                <p class="section-subtitle">Current week summary</p>
                            </div>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <span>28h</span>
                                <p>Hours tracked</p>
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
                                <span>84%</span>
                                <p>Weekly productivity</p>
                            </div>
                        </div>
                    </section>

                    <section class="card schedule-card">
                        <div class="section-title-row">
                            <div>
                                <h3>Upcoming deadlines</h3>
                                <p class="section-subtitle">Keep your schedule on track.</p>
                            </div>
                        </div>
                        <div class="schedule-item">
                            <div class="schedule-time">10:00</div>
                            <div>
                                <h4>Design review</h4>
                                <p>Client website launch</p>
                            </div>
                        </div>
                        <div class="schedule-item">
                            <div class="schedule-time">13:00</div>
                            <div>
                                <h4>Team sync</h4>
                                <p>Weekly planning session</p>
                            </div>
                        </div>
                        <div class="schedule-item">
                            <div class="schedule-time">16:30</div>
                            <div>
                                <h4>Prototype handoff</h4>
                                <p>Mobile onboarding flow</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </section>

        </div>
    </div>

</div>

<?php require __DIR__ . '/../partial/footer.php'; ?>