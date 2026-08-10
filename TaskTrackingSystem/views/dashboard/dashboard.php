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

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
        <?php endif; ?>

        <!-- Header Section -->
        <section class="dashboard-header-section">
            <div class="header-left">
                <p class="header-greeting">Hi, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>.</p>
                <p class="header-subtitle">Progress is progress — big or small.</p>
            </div>
            <div class="header-right">
                <div class="date-display"><?= date('l, F j') ?></div>
                <a href="<?= BASE_URL ?>/views/tasks/add.php" class="btn btn-primary btn-new-task">+ New task</a>
            </div>
        </section>

        <!-- Stats Grid -->
        <section class="stats-section">
            <div class="stat-box">
                <div class="stat-icon stat-icon-tasks">📋</div>
                <div class="stat-content">
                    <div class="stat-value"><?= (int)($counts['total'] ?? 0) ?></div>
                    <div class="stat-label">Total tasks</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon stat-icon-completed">✅</div>
                <div class="stat-content">
                    <div class="stat-value"><?= (int)($counts['finished'] ?? 0) ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon stat-icon-pending">⏱️</div>
                <div class="stat-content">
                    <div class="stat-value"><?= max(0, (int)($counts['total'] ?? 0) - (int)($counts['finished'] ?? 0)) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-box stat-box-rate">
                <div class="stat-content">
                    <div class="stat-value-rate"><?= ((int)($counts['total'] ?? 0) > 0)
                        ? round(((int)($counts['finished'] ?? 0) / (int)($counts['total'] ?? 1)) * 100)
                        : 0 ?>%</div>
                    <div class="stat-label">Completion rate</div>
                </div>
            </div>
        </section>

        <!-- Task Overview Chart -->
        <section class="task-overview-section">
            <div class="section-header">
                <h3>Task Overview</h3>
                <select class="time-filter">
                    <option>This Week</option>
                    <option>This Month</option>
                    <option>All Time</option>
                </select>
            </div>
            <div class="chart-container">
                <canvas id="taskChart"></canvas>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div class="dashboard-grid">
            <!-- Recent Tasks -->
            <section class="recent-tasks-section">
                <div class="section-header">
                    <h3>Recent Tasks</h3>
                    <a href="<?= BASE_URL ?>/views/tasks/index.php" class="view-all-link">View all</a>
                </div>

                <?php if ($tasks->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h4>No tasks yet</h4>
                    <p>You haven't added any tasks.</p>
                    <a href="<?= BASE_URL ?>/views/tasks/add.php" class="btn btn-primary">Create your first task</a>
                </div>
                <?php else: ?>
                <div class="recent-tasks-list">
                    <?php 
                    $count = 0;
                    while ($task = $tasks->fetch_assoc() && $count < 5): 
                        $count++;
                    ?>
                    <div class="task-item <?= $task['status'] === 'complete' ? 'task-item-complete' : '' ?>">
                        <div class="task-item-check">
                            <?php if ($task['status'] === 'complete'): ?>
                                <span class="task-checkbox checked">✔</span>
                            <?php else: ?>
                                <span class="task-checkbox"></span>
                            <?php endif; ?>
                        </div>
                        <div class="task-item-content">
                            <h4><?= htmlspecialchars($task['title']) ?></h4>
                            <p><?= htmlspecialchars(substr($task['description'] ?? '', 0, 50)) ?></p>
                        </div>
                        <div class="task-item-priority">
                            <span class="priority-badge priority-<?= strtolower($task['priority'] ?? 'medium') ?>">
                                <?= htmlspecialchars($task['priority'] ?? 'Medium') ?>
                            </span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Upcoming Tasks -->
            <section class="upcoming-tasks-section">
                <div class="section-header">
                    <h3>Upcoming Tasks</h3>
                </div>

                <?php
                $upcomingTasks = $controller->getUpcomingTasks($account_id, 5);
                if ($upcomingTasks && $upcomingTasks->num_rows > 0):
                ?>
                <div class="upcoming-tasks-list">
                    <?php while ($task = $upcomingTasks->fetch_assoc()): ?>
                    <div class="upcoming-task-item">
                        <div class="task-date"><?= htmlspecialchars(date('M j', strtotime($task['due_date']))) ?></div>
                        <div class="task-details">
                            <h5><?= htmlspecialchars($task['title']) ?></h5>
                            <p><?= htmlspecialchars($task['category'] ?? 'Personal') ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state-small">
                    <p>No upcoming tasks</p>
                    <p class="text-muted">You're all caught up! Nice work.</p>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Task Overview Chart
    const ctx = document.getElementById('taskChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [
                    {
                        label: 'Completed',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointBackgroundColor: '#10b981'
                    },
                    {
                        label: 'Pending',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointBackgroundColor: '#f59e0b'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 4
                    }
                }
            }
        });
    }
</script>

<?php require __DIR__ . '/../partial/footer.php'; ?>