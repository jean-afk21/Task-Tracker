<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../public/database.config.php';

if (!isset($_SESSION['account_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

require_once __DIR__ . '/../controllers/task.php';

$account_id = (int) $_SESSION['account_id'];
$controller = new TaskController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
$tasks_result = $controller->getAllTasks($account_id);

$tasks_data = [];
while ($task = $tasks_result->fetch_assoc()) {
    if (empty($task['due_date'])) {
        continue;
    }
    $tasks_data[] = [
        'id' => (int) $task['id'],
        'title' => $task['title'],
        'description' => $task['description'],
        'due_date' => $task['due_date'],
        'priority' => $task['priority'] ?? 'Medium',
        'category' => $task['category'] ?? 'Personal',
        'status' => $task['status'] ?? 'pending',
    ];
}
?>
<?php require __DIR__ . '/partial/header.php'; ?>

<div class="container section calendar-page">
    <div class="calendar-shell">
        <main class="calendar-main card">
            <div class="calendar-header">
                <div class="calendar-title-panel">
                    <p class="eyebrow">Calendar</p>
                    <h1>Stay aligned with every deadline.</h1>
                    <p class="section-subtitle">A clean monthly view of your due tasks.</p>
                </div>
                <div class="calendar-header-actions">
                    <button type="button" class="calendar-nav-btn calendar-nav-btn-secondary" id="todayBtn">Go to Today</button>
                </div>
            </div>

            <div class="calendar-period-row">
                <div class="calendar-nav-row">
                    <button type="button" class="calendar-nav-btn" id="prevBtn" aria-label="Previous month">←</button>
                    <p class="calendar-period" id="calendarPeriodLabel"></p>
                    <button type="button" class="calendar-nav-btn" id="nextBtn" aria-label="Next month">→</button>
                </div>
            </div>

            <div class="calendar-board">
                <div class="calendar-weekdays" aria-hidden="true">
                    <span>Sun</span>
                    <span>Mon</span>
                    <span>Tue</span>
                    <span>Wed</span>
                    <span>Thu</span>
                    <span>Fri</span>
                    <span>Sat</span>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>

            <div class="calendar-legend">
                <div class="legend-item"><span class="legend-dot" style="background:#ef4444"></span>High Priority</div>
                <div class="legend-item"><span class="legend-dot" style="background:#f59e0b"></span>Medium Priority</div>
                <div class="legend-item"><span class="legend-dot" style="background:#34d399"></span>Low Priority</div>
            </div>
        </main>

        <aside class="calendar-panel card" id="detailPanel">
            <div class="detail-header">
                <p class="eyebrow">Selected date</p>
                <h2 id="selectedDateLabel"></h2>
                <p class="panel-subtitle" id="selectedTaskCount"></p>
            </div>
            <div class="detail-body" id="detailTaskList"></div>
        </aside>
    </div>
</div>

<script>
const rawTasks = <?= json_encode($tasks_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
const taskMap = rawTasks.reduce((map, task) => {
    if (!task.due_date) return map;
    map[task.due_date] = map[task.due_date] || [];
    map[task.due_date].push(task);
    return map;
}, {});

const state = {
    current: new Date(),
    selected: new Date(),
};

const calendarPeriodLabel = document.getElementById('calendarPeriodLabel');
const calendarGrid = document.getElementById('calendarGrid');
const selectedDateLabel = document.getElementById('selectedDateLabel');
const selectedTaskCount = document.getElementById('selectedTaskCount');
const detailTaskList = document.getElementById('detailTaskList');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const todayBtn = document.getElementById('todayBtn');

const priorityOrder = {
    High: 1,
    Medium: 2,
    Low: 3,
};

function toIsoDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function isSameDate(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

function getMonthGrid(date) {
    const firstOfMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    const startDay = firstOfMonth.getDay();
    const startDate = new Date(firstOfMonth);
    startDate.setDate(firstOfMonth.getDate() - startDay);
    const grid = [];
    for (let i = 0; i < 42; i += 1) {
        const cell = new Date(startDate);
        cell.setDate(startDate.getDate() + i);
        grid.push(cell);
    }
    return grid;
}

function buildIndicators(tasks) {
    const counts = { High: 0, Medium: 0, Low: 0 };
    tasks.forEach(task => {
        if (counts[task.priority] !== undefined) {
            counts[task.priority] += 1;
        }
    });
    return Object.entries(counts)
        .filter(([, count]) => count > 0)
        .map(([priority, count]) => {
            const className = priority.toLowerCase();
            return `<span class="priority-indicator priority-${className}">${count}</span>`;
        })
        .join('');
}

function updateHeader() {
    calendarPeriodLabel.textContent = state.current.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function sortTasks(tasks) {
    return [...tasks].sort((a, b) => {
        const priorityDiff = priorityOrder[a.priority] - priorityOrder[b.priority];
        if (priorityDiff !== 0) return priorityDiff;
        const aTime = a.due_date ? new Date(a.due_date).getTime() : 0;
        const bTime = b.due_date ? new Date(b.due_date).getTime() : 0;
        return aTime - bTime;
    });
}

function renderCalendar() {
    const grid = getMonthGrid(state.current);
    calendarGrid.innerHTML = '';

    grid.forEach(date => {
        const iso = toIsoDate(date);
        const tasksForDate = taskMap[iso] || [];
        const isCurrentMonth = date.getMonth() === state.current.getMonth();
        const isToday = isSameDate(date, new Date());
        const isSelected = isSameDate(date, state.selected);

        const cell = document.createElement('button');
        cell.type = 'button';
        cell.className = `calendar-day${isCurrentMonth ? '' : ' calendar-day--muted'}${isToday ? ' calendar-day--today' : ''}${isSelected ? ' calendar-day--selected' : ''}`;
        cell.innerHTML = `
            <span class="date-number">${date.getDate()}</span>
            <div class="calendar-day-indicators">${buildIndicators(tasksForDate)}</div>
        `;

        cell.addEventListener('click', () => {
            state.selected = new Date(date);
            renderDetailPanel();
            renderCalendar();
        });

        calendarGrid.appendChild(cell);
    });
}

function renderDetailPanel() {
    const selected = state.selected;
    const iso = toIsoDate(selected);
    const tasksForDate = taskMap[iso] || [];
    const sortedTasks = sortTasks(tasksForDate);

    selectedDateLabel.textContent = selected.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    selectedTaskCount.textContent = `${sortedTasks.length} task${sortedTasks.length === 1 ? '' : 's'}`;
    detailTaskList.innerHTML = '';

    if (sortedTasks.length === 0) {
        detailTaskList.innerHTML = `
            <div class="empty-state card-empty">
                <div class="empty-state-icon">☁</div>
                <h3>No tasks for this day.</h3>
                <p>Enjoy your free time or add a new task.</p>
                <a href="<?= BASE_URL ?>/views/tasks/add.php" class="btn btn-primary">+ New Task</a>
            </div>
        `;
        return;
    }

    sortedTasks.forEach(task => {
        const completeClass = task.status === 'complete' ? 'is-complete' : '';
        const dueLabel = task.due_date ? new Date(task.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'No due date';
        detailTaskList.innerHTML += `
            <article class="detail-task-card ${completeClass}">
                <div class="detail-task-top">
                    <h3>${escapeHtml(task.title)}</h3>
                    <span class="priority-pill priority-${task.priority.toLowerCase()}">${escapeHtml(task.priority)}</span>
                </div>
                <p>${escapeHtml(task.description || 'No description provided.')}</p>
                <div class="detail-task-meta">
                    <span class="task-category">${escapeHtml(task.category || 'Personal')}</span>
                    <span class="task-status ${task.status === 'complete' ? 'completed' : ''}">${escapeHtml(task.status === 'complete' ? 'Completed' : 'Pending')}</span>
                    <span>Due ${escapeHtml(dueLabel)}</span>
                </div>
            </article>
        `;
    });
}

function escapeHtml(text) {
    if (typeof text !== 'string') return '';
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

prevBtn.addEventListener('click', () => {
    state.current = new Date(state.current.getFullYear(), state.current.getMonth() - 1, 1);
    renderCalendar();
    renderDetailPanel();
    updateHeader();
});

nextBtn.addEventListener('click', () => {
    state.current = new Date(state.current.getFullYear(), state.current.getMonth() + 1, 1);
    renderCalendar();
    renderDetailPanel();
    updateHeader();
});

todayBtn.addEventListener('click', () => {
    state.current = new Date();
    state.selected = new Date();
    renderCalendar();
    renderDetailPanel();
    updateHeader();
});

updateHeader();
renderCalendar();
renderDetailPanel();
</script>

<?php require __DIR__ . '/partial/footer.php'; ?>
