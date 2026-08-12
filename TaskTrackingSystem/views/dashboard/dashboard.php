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

$totalCount    = (int)($counts['total'] ?? 0);
$finishedCount = (int)($counts['finished'] ?? 0);
$pendingCount  = max(0, $totalCount - $finishedCount);
$firstName     = strtok(trim($_SESSION['username'] ?? 'there'), ' ');

$hour = (int)date('G');
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

if ($motivation !== '') {
    $welcomeLine = $motivation;
} elseif ($totalCount === 0) {
    $welcomeLine = $greeting . ', ' . $firstName . '. Every big climb starts with one small task — add your first one.';
} elseif ($pendingCount === 0) {
    $welcomeLine = $greeting . ', ' . $firstName . '. Everything is done — enjoy the view from the top.';
} elseif ($finishedCount === 0) {
    $welcomeLine = $greeting . ', ' . $firstName . '. ' . $pendingCount . ' ' . ($pendingCount === 1 ? 'task is' : 'tasks are')
        . ' waiting — start with the smallest one and momentum does the rest.';
} elseif ($pendingCount === 1) {
    $welcomeLine = 'Just one task left, ' . $firstName . '. Finish strong.';
} else {
    $welcomeLine = "You've cleared " . $finishedCount . ' of ' . $totalCount . ' — '
        . $pendingCount . ' to go. Small steps, steady climb.';
}
?>
<?php require __DIR__ . '/../partial/header.php'; ?>

<!-- DASHBOARD: START -->
<!-- View: Dashboard overview with recent tasks and progress -->
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
            <p><?= htmlspecialchars($welcomeLine) ?></p>
            <a href="<?= BASE_URL ?>/views/tasks/add.php" class="new-task">+ New Task</a>
        </div>

        <div class="welcome-icon" aria-hidden="true">
            <svg class="ant-hill" viewBox="0 0 200 120" role="img" aria-label="Ants climbing a hill">
                <path class="hill-back" d="M0 120 C 45 92, 78 46, 118 26 C 152 9, 178 12, 200 4 L200 120 Z"/>
                <path class="hill-front" d="M0 120 C 52 108, 92 74, 132 54 C 164 38, 184 34, 200 30 L200 120 Z"/>
                <g class="ant-trail">
                    <g transform="translate(46 104) rotate(-24)">
                        <ellipse cx="-7" cy="0" rx="3.4" ry="2.8"/>
                        <ellipse cx="0" cy="0" rx="2.6" ry="2.2"/>
                        <circle cx="5.6" cy="-0.4" r="2.6"/>
                        <path class="ant-legs" d="M-6 -2 l-3 -4 M-6 2 l-3 4 M0 -2.2 l0 -5 M0 2.2 l0 5 M5 -2.4 l3 -4 M5 2 l3 4 M7 -2.4 l3.4 -4.6 M7.6 -1.2 l4.2 -2.6"/>
                    </g>
                    <g transform="translate(92 84) rotate(-30)">
                        <ellipse cx="-7" cy="0" rx="3.4" ry="2.8"/>
                        <ellipse cx="0" cy="0" rx="2.6" ry="2.2"/>
                        <circle cx="5.6" cy="-0.4" r="2.6"/>
                        <path class="ant-legs" d="M-6 -2 l-3 -4 M-6 2 l-3 4 M0 -2.2 l0 -5 M0 2.2 l0 5 M5 -2.4 l3 -4 M5 2 l3 4 M7 -2.4 l3.4 -4.6 M7.6 -1.2 l4.2 -2.6"/>
                    </g>
                    <g transform="translate(136 62) rotate(-28)">
                        <ellipse cx="-7" cy="0" rx="3.4" ry="2.8"/>
                        <ellipse cx="0" cy="0" rx="2.6" ry="2.2"/>
                        <circle cx="5.6" cy="-0.4" r="2.6"/>
                        <path class="ant-legs" d="M-6 -2 l-3 -4 M-6 2 l-3 4 M0 -2.2 l0 -5 M0 2.2 l0 5 M5 -2.4 l3 -4 M5 2 l3 4 M7 -2.4 l3.4 -4.6 M7.6 -1.2 l4.2 -2.6"/>
                    </g>
                    <g class="ant-lead" transform="translate(176 36) rotate(-16)">
                        <ellipse cx="-7" cy="0" rx="3.4" ry="2.8"/>
                        <ellipse cx="0" cy="0" rx="2.6" ry="2.2"/>
                        <circle cx="5.6" cy="-0.4" r="2.6"/>
                        <path class="ant-legs" d="M-6 -2 l-3 -4 M-6 2 l-3 4 M0 -2.2 l0 -5 M0 2.2 l0 5 M5 -2.4 l3 -4 M5 2 l3 4 M7 -2.4 l3.4 -4.6 M7.6 -1.2 l4.2 -2.6"/>
                    </g>
                </g>
            </svg>
        </div>
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
                while ($count < 5 && ($task = $tasks->fetch_assoc())):
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