<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();


if (!isset($_SESSION['account_id'])) {
    header("Location: /TaskTrackingSystem/index.php");
    exit();
}


require_once __DIR__ . '/../controllers/task.php';
require_once __DIR__ . '/../public/database.config.php';


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
    $createdDate = !empty($task['created_at']) ? DateTime::createFromFormat('Y-m-d H:i:s', $task['created_at']) : null;


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


$focusHours = max(0, $completed * 2);


function formatCount($value) {
    return number_format($value);
}


function formatLabel($dateString) {
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    return $date ? $date->format('D') : '';
}
?>
<?php require __DIR__ . '/partial/header.php'; ?>


<div class="container section analytics-page">
    <div class="analytics-grid">
        <main class="analytics-main card">
            <div class="analytics-hero">
                <div>
                    <p class="eyebrow">Analytics</p>
                    <h1>Your productivity snapshot</h1>
                    <p class="section-subtitle">Quick look at your progress today.</p>
                </div>
                <div class="analytics-actions">
                    <button class="btn btn-secondary btn-icon">⟳</button>
                    <button class="btn btn-primary btn-pill">Export</button>
                </div>
            </div>


            <section class="stats-summary">
                <article class="stat-card">
                    <p class="stat-label">Finished</p>
                    <h2><?= formatCount($completed) ?></h2>
                    <span class="stat-note">Tasks wrapped up</span>
                </article>
                <article class="stat-card">
                    <p class="stat-label">In Progress</p>
                    <h2><?= formatCount($pending) ?></h2>
                    <span class="stat-note">Still working on</span>
                </article>
                <article class="stat-card">
                    <p class="stat-label">Overdue</p>
                    <h2><?= formatCount($overdue) ?></h2>
                    <span class="stat-note">Need attention now</span>
                </article>
                <article class="stat-card">
                    <p class="stat-label">Effort Hours</p>
                    <h2><?= formatCount($focusHours) ?></h2>
                    <span class="stat-note">This week's work</span>
                </article>
            </section>


            <section class="chart-panel card card-soft">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">This week</p>
                        <h3>Your daily load</h3>
                    </div>
                    <span class="chart-badge">Live</span>
                </div>
                <div class="bar-chart" role="img" aria-label="Weekly productivity chart">
                    <?php foreach ($weeklyCounts as $date => $count): ?>
                        <?php $height = min(100, ($count / max(1, max($weeklyCounts))) * 100); ?>
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
                        <p class="eyebrow">Status</p>
                        <h3>How you're tracking</h3>
                    </div>
                </div>
                <div class="progress-list">
                    <div class="progress-row">
                        <div>
                            <p class="progress-label">Completed</p>
                            <p class="progress-meta"><?= formatCount($completed) ?> tasks</p>
                        </div>
                        <div class="progress-track"><div class="progress-fill progress-fill-completed" style="width: <?= min(100, $completed + 10) ?>%;"></div></div>
                    </div>
                    <div class="progress-row">
                        <div>
                            <p class="progress-label">Pending</p>
                            <p class="progress-meta"><?= formatCount($pending) ?> tasks</p>
                        </div>
                        <div class="progress-track"><div class="progress-fill progress-fill-pending" style="width: <?= min(100, $pending + 10) ?>%;"></div></div>
                    </div>
                    <div class="progress-row">
                        <div>
                            <p class="progress-label">Overdue</p>
                            <p class="progress-meta"><?= formatCount($overdue) ?> tasks</p>
                        </div>
                        <div class="progress-track"><div class="progress-fill progress-fill-overdue" style="width: <?= min(100, $overdue * 2) ?>%;"></div></div>
                    </div>
                </div>
            </section>
        </main>


        <aside class="analytics-panel card">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Latest</p>
                    <h3>Recent work</h3>
                </div>
            </div>
            <div class="activity-list">
                <?php if (empty($recentActivities)): ?>
                    <div class="empty-state card-empty">
                        <div class="empty-state-icon">✓</div>
                        <h3>No recent activity</h3>
                        <p>Complete a task to populate this list.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivities as $task): ?>
                        <article class="activity-item">
                            <div class="activity-icon <?= $task['status'] === 'complete' ? 'activity-complete' : 'activity-pending' ?>">
                                <?= $task['status'] === 'complete' ? '✔' : '•' ?>
                            </div>
                            <div>
                                <h4><?= htmlspecialchars($task['title']) ?></h4>
                                <p><?= htmlspecialchars($task['category'] ?? 'Personal') ?> · <?= htmlspecialchars(date('M j', strtotime($task['due_date'] ?? 'now'))) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>


<?php require __DIR__ . '/partial/footer.php'; ?>

