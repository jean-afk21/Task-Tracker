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

// Week window shown by the bar chart (0 = current week, -1 = previous week, 1 = next week)
$weekOffset = (int)($_GET['week'] ?? 0);
$weekOffset = max(-4, min(4, $weekOffset));

$weekStart = clone $today;
$weekStart->modify('-' . (int)$today->format('w') . ' days');
if ($weekOffset !== 0) {
    $weekStart->modify(($weekOffset > 0 ? '+' : '-') . abs($weekOffset) . ' weeks');
}
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

$weeklyCounts = [];
for ($i = 0; $i < 7; $i++) {
    $date = clone $weekStart;
    $date->modify("+{$i} days");
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

    if ($dueDate) {
        $dueKey = $dueDate->format('Y-m-d');
        if (array_key_exists($dueKey, $weeklyCounts)) {
            $weeklyCounts[$dueKey]++;
        }
    }

    $recentActivities[] = $task;
}

usort($recentActivities, function ($a, $b) {
    return strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0');
});
$recentActivities = array_slice($recentActivities, 0, 5);

// Overdue tasks are shown separately, so "In Progress" excludes them.
$inProgress = max(0, $pending - $overdue);

$totalTasks = count($tasks);
$completionRate = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;
$weekLabel = $weekStart->format('M j') . ' – ' . $weekEnd->format('M j, Y');

$completionNote = 'Create your first task';
if ($totalTasks > 0) {
    if ($completionRate >= 80) {
        $completionNote = 'Outstanding work!';
    } elseif ($completionRate >= 50) {
        $completionNote = 'Great progress!';
    } elseif ($completionRate > 0) {
        $completionNote = 'Keep it going!';
    } else {
        $completionNote = 'Time to get started';
    }
}

function formatCount($value) {
    return number_format($value);
}

function formatLabel($dateString) {
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    return $date ? $date->format('D') : '';
}

function formatDayLabel($dateString) {
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    return $date ? $date->format('M j') : '';
}

function formatRecentDate($dateString) {
    if (empty($dateString)) {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
    if (!$date) {
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return $dateString;
        }
        $date = (new DateTime())->setTimestamp($timestamp);
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

// Round the chart axis up to a friendly maximum so gridlines stay readable.
$peakCount = $weeklyCounts ? max($weeklyCounts) : 0;
$axisStep = $peakCount > 20 ? 10 : ($peakCount > 8 ? 5 : 2);
$axisMax = max($axisStep, (int)(ceil($peakCount / $axisStep) * $axisStep));
$axisTicks = [];
for ($tick = $axisMax; $tick >= 0; $tick -= $axisStep) {
    $axisTicks[] = $tick;
}

$finishedPct = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;
$pendingPct  = $totalTasks > 0 ? round(($inProgress / $totalTasks) * 100) : 0;
$overduePct  = $totalTasks > 0 ? round(($overdue / $totalTasks) * 100) : 0;
?>
<?php require __DIR__ . '/partial/header.php'; ?>

<!-- ANALYTICS: START -->
<!-- View: Task Tracking Analytics dashboard (presentation only) -->
<main class="main">
<div class="analytics-page">

    <header class="analytics-topbar">
        <div class="analytics-heading">
            <p class="eyebrow">Analytics</p>
            <h1>Your productivity overview 📊</h1>
            <p class="section-subtitle">Track your progress and stay consistent.</p>
        </div>

        <div class="analytics-topbar-side">
            <div class="profile">
                <div class="profile-icon"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)) ?></div>
                <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong>
            </div>

            <form method="get" class="date-range" action="<?= BASE_URL ?>/views/analytics.php">
                <span class="date-range-icon" aria-hidden="true">🗓️</span>
                <label class="sr-only" for="week">Week shown in the chart</label>
                <select id="week" name="week" onchange="this.form.submit()">
                    <?php for ($offset = 2; $offset >= -4; $offset--): ?>
                        <?php
                            $optionStart = clone $today;
                            $optionStart->modify('-' . (int)$today->format('w') . ' days');
                            if ($offset !== 0) {
                                $optionStart->modify(($offset > 0 ? '+' : '-') . abs($offset) . ' weeks');
                            }
                            $optionEnd = clone $optionStart;
                            $optionEnd->modify('+6 days');
                        ?>
                        <option value="<?= $offset ?>" <?= $offset === $weekOffset ? 'selected' : '' ?>>
                            <?= $optionStart->format('M j') ?> – <?= $optionEnd->format('M j, Y') ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <noscript><button type="submit" class="date-range-go">Go</button></noscript>
            </form>
        </div>
    </header>

    <section class="stats-summary">
        <article class="analytics-stat">
            <div class="analytics-stat-top">
                <span class="stat-icon stat-icon-success" aria-hidden="true">✓</span>
                <p class="stat-label">Finished</p>
            </div>
            <h2><?= formatCount($completed) ?></h2>
            <span class="stat-note stat-note-success">tasks completed</span>
        </article>

        <article class="analytics-stat">
            <div class="analytics-stat-top">
                <span class="stat-icon stat-icon-info" aria-hidden="true">◷</span>
                <p class="stat-label">In Progress</p>
            </div>
            <h2><?= formatCount($inProgress) ?></h2>
            <span class="stat-note stat-note-info">tasks pending</span>
        </article>

        <article class="analytics-stat">
            <div class="analytics-stat-top">
                <span class="stat-icon stat-icon-danger" aria-hidden="true">↓</span>
                <p class="stat-label">Overdue</p>
            </div>
            <h2><?= formatCount($overdue) ?></h2>
            <span class="stat-note stat-note-danger">tasks past due</span>
        </article>

        <article class="analytics-stat analytics-stat-rate">
            <p class="stat-label">Completion Rate</p>
            <div class="rate-ring" style="--rate: <?= (int)$completionRate ?>;" role="img"
                 aria-label="Completion rate <?= (int)$completionRate ?> percent">
                <span><?= formatCount($completionRate) ?>%</span>
            </div>
            <span class="stat-note stat-note-info"><?= htmlspecialchars($completionNote) ?></span>
        </article>

        <article class="analytics-stat">
            <div class="analytics-stat-top">
                <span class="stat-icon stat-icon-secondary" aria-hidden="true">▤</span>
                <p class="stat-label">Total Tasks</p>
            </div>
            <h2><?= formatCount($totalTasks) ?></h2>
            <span class="stat-note">total tasks</span>
        </article>
    </section>

    <div class="analytics-grid">
        <section class="chart-panel">
            <div class="panel-head">
                <h3>Tasks by Due Date <?= $weekOffset === 0 ? '(This Week)' : '(' . htmlspecialchars($weekLabel) . ')' ?></h3>
            </div>

            <div class="bar-chart" role="img"
                 aria-label="Tasks due per day for <?= htmlspecialchars($weekLabel) ?>">
                <div class="bar-axis">
                    <?php foreach ($axisTicks as $tick): ?>
                        <span><?= $tick ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="bar-plot">
                    <div class="bar-gridlines" aria-hidden="true">
                        <?php foreach ($axisTicks as $tick): ?><span></span><?php endforeach; ?>
                    </div>

                    <?php foreach ($weeklyCounts as $date => $count): ?>
                        <?php $height = $axisMax > 0 ? min(100, ($count / $axisMax) * 100) : 0; ?>
                        <div class="bar-item">
                            <div class="bar-column">
                                <span class="bar-value"><?= $count > 0 ? (int)$count : '' ?></span>
                                <div class="bar-fill<?= $count === 0 ? ' bar-fill-empty' : '' ?>"
                                     style="height: <?= $height ?>%;"></div>
                            </div>
                            <small>
                                <span><?= formatLabel($date) ?></span>
                                <span class="bar-date"><?= formatDayLabel($date) ?></span>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="progress-panel">
            <div class="panel-head">
                <h3>Task Breakdown</h3>
            </div>

            <div class="progress-list">
                <div class="progress-row">
                    <div class="progress-label-row">
                        <p class="progress-label">Finished</p>
                        <p class="progress-meta"><strong><?= formatCount($completed) ?></strong> (<?= $finishedPct ?>%)</p>
                    </div>
                    <div class="progress-track"><div class="progress-fill progress-fill-completed" style="width: <?= $finishedPct ?>%;"></div></div>
                </div>

                <div class="progress-row">
                    <div class="progress-label-row">
                        <p class="progress-label">In Progress</p>
                        <p class="progress-meta"><strong><?= formatCount($inProgress) ?></strong> (<?= $pendingPct ?>%)</p>
                    </div>
                    <div class="progress-track"><div class="progress-fill progress-fill-pending" style="width: <?= $pendingPct ?>%;"></div></div>
                </div>

                <div class="progress-row">
                    <div class="progress-label-row">
                        <p class="progress-label">Overdue</p>
                        <p class="progress-meta"><strong><?= formatCount($overdue) ?></strong> (<?= $overduePct ?>%)</p>
                    </div>
                    <div class="progress-track"><div class="progress-fill progress-fill-overdue" style="width: <?= $overduePct ?>%;"></div></div>
                </div>

                <div class="progress-row progress-row-total">
                    <div class="progress-label-row">
                        <p class="progress-label">Total</p>
                        <p class="progress-meta"><strong><?= formatCount($totalTasks) ?></strong> (<?= $totalTasks > 0 ? 100 : 0 ?>%)</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section class="analytics-panel">
        <div class="panel-head">
            <h3>Recent Activity</h3>
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
                        $isOverdue = $task['status'] !== 'complete'
                            && !empty($task['due_date'])
                            && strtotime($task['due_date']) < strtotime('today');
                        $statusLabel = $task['status'] === 'complete' ? 'Completed' : ($isOverdue ? 'Overdue' : 'In Progress');
                        $statusKey = strtolower(str_replace(' ', '-', $statusLabel));
                    ?>
                    <article class="activity-item">
                        <div class="activity-icon activity-<?= $statusKey ?>" aria-hidden="true">
                            <?= $statusLabel === 'Completed' ? '✓' : ($statusLabel === 'Overdue' ? '↓' : '◷') ?>
                        </div>

                        <h4 class="activity-title"><?= htmlspecialchars($task['title']) ?></h4>

                        <span class="activity-tag"><?= htmlspecialchars($task['category'] ?? 'Personal') ?></span>

                        <span class="activity-status activity-status-<?= $statusKey ?>"><?= $statusLabel ?></span>

                        <span class="activity-time"><?= htmlspecialchars(formatRecentDate($task['created_at'] ?? '')) ?></span>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

</div>
</main>


<?php require __DIR__ . '/partial/footer.php'; ?>
