<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../public/database.config.php';


if (!isset($_SESSION['account_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}


require_once __DIR__ . '/../controllers/task.php';


$account_id = (int)$_SESSION['account_id'];
$controller = new TaskController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
$tasks_result = $controller->getAllTasks($account_id);


$tasks = [];
while ($task = $tasks_result->fetch_assoc()) {
    $tasks[] = $task;
}


$today = new DateTime('today');
$completed = 0;
$pending = 0;
$overdue = 0;
$recentActivities = [];
$weeklyCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = clone $today;
    $date->modify("-{$i} days");
    $weeklyCounts[$date->format('Y-m-d')] = 0;
}

foreach ($tasks as $task) {
    $status = $task['status'] ?? 'pending';
    $dueDate = !empty($task['due_date']) ? DateTime::createFromFormat('Y-m-d', $task['due_date']) : null;

    if ($status === 'complete') {
        $completed++;
    } else {
        $pending++;
    }

    if ($dueDate && $dueDate < $today && $status !== 'complete') {
        $overdue++;
    }

    foreach ($weeklyCounts as $dateKey => $value) {
        if ($dueDate && $dueDate->format('Y-m-d') === $dateKey) {
            $weeklyCounts[$dateKey]++;
        }
    }

    $recentActivities[] = $task;
}

usort($recentActivities, function ($a, $b) {
    return strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0');
});
$recentActivities = array_slice($recentActivities, 0, 5);

$totalTasks = count($tasks);
$completionRate = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;
$weekStart = clone $today;
$weekStart->modify('-6 days');
$weekLabel = $weekStart->format('M j') . ' – ' . $today->format('M j, Y');

function formatCount($value) {
    return number_format($value);
}

function formatLabel($dateString) {
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    return $date ? $date->format('D') : '';
}

function formatRecentDate($dateString) {
    if (empty($dateString)) {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
    if (!$date) {
        return htmlspecialchars($dateString);
    }
    $today = new DateTime('today');
    $tomorrow = clone $today;
    $tomorrow->modify('+1 day');
    if ($date >= $today && $date < $tomorrow) {
        return 'Today, ' . $date->format('g:i A');
    }
    $yesterday = clone $today;
    $yesterday->modify('-1 day');
    if ($date >= $yesterday && $date < $today) {
        return 'Yesterday, ' . $date->format('g:i A');
    }
    return $date->format('M j, g:i A');
}
?>
<?php require __DIR__ . '/partial/header.php'; ?>


<div class="container section analytics-page">
    <div class="analytics-grid">
        <main class="analytics-main card">
            <div class="analytics-hero">
                <div>
                    <p class="eyebrow">Analytics</p>
                    <h1>Your productivity overview 📊</h1>
                    <p class="section-subtitle">Track your progress and stay consistent.</p>
                </div>
                <div class="analytics-actions analytics-date-range">
                    <span><?= htmlspecialchars($weekLabel) ?></span>
                </div>
            </div>

            <section class="stats-summary">
                <article class="stat-card">
                    <div class="stat-icon stat-icon-success">✓</div>
                    <p class="stat-label">Finished</p>
                    <h2><?= formatCount($completed) ?></h2>
                    <span class="stat-note">tasks completed</span>
                </article>
                <article class="stat-card">
                    <div class="stat-icon stat-icon-info">⟳</div>
                    <p class="stat-label">In Progress</p>
                    <h2><?= formatCount($pending) ?></h2>
                    <span class="stat-note">tasks pending</span>
                </article>
                <article class="stat-card">
                    <div class="stat-icon stat-icon-danger">!</div>
                    <p class="stat-label">Overdue</p>
                    <h2><?= formatCount($overdue) ?></h2>
                    <span class="stat-note">tasks past due</span>
                </article>
                <article class="stat-card">
                    <div class="stat-icon stat-icon-primary">%</div>
                    <p class="stat-label">Completion Rate</p>
                    <h2><?= formatCount($completionRate) ?>%</h2>
                    <span class="stat-note">of tasks completed</span>
                </article>
                <article class="stat-card">
                    <div class="stat-icon stat-icon-secondary">●</div>
                    <p class="stat-label">Total Tasks</p>
                    <h2><?= formatCount($totalTasks) ?></h2>
                    <span class="stat-note">total tasks</span>
                </article>
            </section>

            <section class="chart-panel card card-soft">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Tasks by Due Date (This Week)</p>
                    </div>
                </div>
                <div class="bar-chart" role="img" aria-label="Weekly tasks due this week">
                    <?php $maxCount = max(1, max($weeklyCounts)); ?>
                    <?php foreach ($weeklyCounts as $date => $count): ?>
                        <?php $height = min(100, ($count / $maxCount) * 100); ?>
                        <div class="bar-item">
                            <div class="bar-fill" style="height: <?= $height ?>%;"></div>
                            <small><?= formatLabel($date) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="progress-panel card card-soft">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Task Breakdown</p>
                    </div>
                </div>
                <div class="progress-list">
                    <?php
                        $finishedPct = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;
                        $pendingPct = $totalTasks > 0 ? round(($pending / $totalTasks) * 100) : 0;
                        $overduePct = $totalTasks > 0 ? round(($overdue / $totalTasks) * 100) : 0;
                    ?>
                    <div class="progress-row">
                        <div class="progress-label-row">
                            <p class="progress-label">Finished</p>
                            <p class="progress-meta"><?= formatCount($completed) ?> (<?= $finishedPct ?>%)</p>
                        </div>
                        <div class="progress-track"><div class="progress-fill progress-fill-completed" style="width: <?= $finishedPct ?>%;"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label-row">
                            <p class="progress-label">In Progress</p>
                            <p class="progress-meta"><?= formatCount($pending) ?> (<?= $pendingPct ?>%)</p>
                        </div>
                        <div class="progress-track"><div class="progress-fill progress-fill-pending" style="width: <?= $pendingPct ?>%;"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label-row">
                            <p class="progress-label">Overdue</p>
                            <p class="progress-meta"><?= formatCount($overdue) ?> (<?= $overduePct ?>%)</p>
                        </div>
                        <div class="progress-track"><div class="progress-fill progress-fill-overdue" style="width: <?= $overduePct ?>%;"></div></div>
                    </div>
                    <div class="progress-row progress-row-total">
                        <div class="progress-label-row">
                            <p class="progress-label">Total</p>
                            <p class="progress-meta"><?= formatCount($totalTasks) ?> (100%)</p>
                        </div>
                        <div class="progress-track progress-track-total"><div class="progress-fill progress-fill-total" style="width: 100%;"></div></div>
                    </div>
                </div>
            </section>
        </main>


        <aside class="analytics-panel card">
            <div class="panel-head panel-head-spaced">
                <div>
                    <p class="eyebrow">Recent Activity</p>
                    <h3>Latest updates</h3>
                </div>
                <a href="<?= BASE_URL ?>/views/tasks/index.php" class="view-all">View all</a>
            </div>
            <div class="activity-list">
                <?php if (empty($recentActivities)): ?>
                    <div class="empty-state card-empty">
                        <div class="empty-state-icon">✓</div>
                        <h3>No activity yet</h3>
                        <p>Create your first task to start tracking your productivity.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivities as $task): ?>
                        <?php
                            $statusLabel = $task['status'] === 'complete' ? 'Completed' : (($task['status'] === 'pending' && !empty($task['due_date']) && strtotime($task['due_date']) < time()) ? 'Overdue' : 'In Progress');
                            $statusClass = $statusLabel === 'Completed' ? 'activity-complete' : ($statusLabel === 'Overdue' ? 'activity-danger' : 'activity-pending');
                        ?>
                        <article class="activity-item">
                            <div class="activity-icon <?= $statusClass ?>">
                                <?= $statusLabel === 'Completed' ? '✔' : ($statusLabel === 'Overdue' ? '!' : '◷') ?>
                            </div>
                            <div class="activity-content">
                                <h4><?= htmlspecialchars($task['title']) ?></h4>
                                <p class="activity-meta">
                                    <?= htmlspecialchars($task['category'] ?? 'Personal') ?>
                                    <span class="activity-status <?= strtolower(str_replace(' ', '-', $statusLabel)) ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                </p>
                            </div>
                            <div class="activity-time"><?= htmlspecialchars(formatRecentDate($task['created_at'] ?? '')) ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>


<?php require __DIR__ . '/partial/footer.php'; ?>

