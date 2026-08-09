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


$tasks_data = [];
while ($task = $tasks_result->fetch_assoc()) {
    if (empty($task['due_date'])) {
        continue;
    }
    $tasks_data[] = [
        'id' => (int)$task['id'],
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
                <div class="calendar-topbar">
                    <div class="calendar-topbar-copy">
                        <p class="eyebrow">Calendar</p>
                        <h1>Stay aligned with every deadline.</h1>
                        <p class="section-subtitle">See what's slipping. Move what's not.</p>
                    </div>
                    <div class="calendar-topbar-actions">
                        <button type="button" class="btn btn-secondary btn-icon" title="Add task">＋</button>
                        <button type="button" class="btn btn-secondary btn-icon" title="Settings">⚙</button>
                    </div>
                </div>

                <div class="calendar-controls">
                    <div class="calendar-nav-row">
                        <button type="button" class="calendar-nav-btn" id="prevBtn" aria-label="Previous period">←</button>
                        <div class="calendar-period-wrap">
                            <p class="calendar-period" id="calendarPeriodLabel"></p>
                            <p class="calendar-period-meta" id="calendarMetaLabel">Month view · Tasks by due date</p>
                        </div>
                        <button type="button" class="calendar-nav-btn" id="nextBtn" aria-label="Next period">→</button>
                    </div>
                    <div class="calendar-actions-row">
                        <div class="toggle-pill-group" role="group" aria-label="Toggle schedule or due dates">
                            <button type="button" class="toggle-pill active" data-mode="schedule">Schedule</button>
                            <button type="button" class="toggle-pill" data-mode="due">Due Dates</button>
                        </div>
                        <div class="view-switch" role="group" aria-label="Switch calendar view">
                            <button type="button" class="view-pill active" data-view="month">Month</button>
                            <button type="button" class="view-pill" data-view="day">Day</button>
                        </div>
                    </div>
                </div>
            </div>

            <section class="calendar-board">
                <div class="calendar-week-strip hidden" id="calendarWeekStrip"></div>

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

                <div class="calendar-day-timeline hidden" id="calendarTimeline">
                    <div class="timeline-header">
                        <div>
                            <p class="eyebrow">Day timeline</p>
                            <h3 id="timelineLabel"></h3>
                        </div>
                        <span class="timeline-badge">Focused view</span>
                    </div>
                    <div class="timeline-body">
                        <div class="timeline-hours" id="timelineHours"></div>
                        <div class="timeline-lines" id="timelineGrid"></div>
                    </div>
                </div>

                <div class="calendar-day-panel hidden" id="dayViewPanel">
                    <div class="day-view-heading">
                        <div>
                            <p class="eyebrow">Day overview</p>
                            <h2 id="dayViewLabel"></h2>
                            <p class="section-subtitle" id="dayViewSubtitle">Tasks scheduled for the selected day.</p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-pill" id="jumpToToday">Today</button>
                    </div>
                    <div class="day-task-list" id="dayTaskList"></div>
                </div>
            </section>

            <div class="calendar-tooltip hidden" id="calendarTooltip"></div>
        </main>

        <aside class="calendar-panel card" id="detailPanel">
            <div class="detail-header">
                <div>
                    <p class="eyebrow">Selected date</p>
                    <h2 id="selectedDateLabel"></h2>
                    <p class="panel-subtitle" id="selectedTaskCount"></p>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="closeDetailPanel">Close</button>
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
    view: 'month',
    mode: 'schedule'
};

const calendarPeriodLabel = document.getElementById('calendarPeriodLabel');
const calendarMetaLabel = document.getElementById('calendarMetaLabel');
const calendarGrid = document.getElementById('calendarGrid');
const calendarWeekStrip = document.getElementById('calendarWeekStrip');
const calendarTimeline = document.getElementById('calendarTimeline');
const timelineLabel = document.getElementById('timelineLabel');
const timelineHours = document.getElementById('timelineHours');
const timelineGrid = document.getElementById('timelineGrid');
const dayViewPanel = document.getElementById('dayViewPanel');
const dayViewLabel = document.getElementById('dayViewLabel');
const dayTaskList = document.getElementById('dayTaskList');
const selectedDateLabel = document.getElementById('selectedDateLabel');
const selectedTaskCount = document.getElementById('selectedTaskCount');
const detailTaskList = document.getElementById('detailTaskList');
const calendarTooltip = document.getElementById('calendarTooltip');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const jumpToToday = document.getElementById('jumpToToday');
const closeDetailPanel = document.getElementById('closeDetailPanel');

const viewButtons = Array.from(document.querySelectorAll('.view-pill'));
const modeButtons = Array.from(document.querySelectorAll('.toggle-pill'));

function formatLongDate(date) {
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

function formatDayLabel(date) {
    return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
}

function toIsoDate(date) {
    return date.toISOString().slice(0, 10);
}

function isSameDate(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

function updateHeader() {
    if (state.view === 'month') {
        calendarPeriodLabel.textContent = state.current.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        calendarMetaLabel.textContent = `${capitalize(state.mode)} mode · Month overview`;
    } else {
        calendarPeriodLabel.textContent = formatDayLabel(state.selected);
        calendarMetaLabel.textContent = `${capitalize(state.mode)} mode · Day focus`;
    }
}

function capitalize(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function renderMonthView() {
    const firstOfMonth = new Date(state.current.getFullYear(), state.current.getMonth(), 1);
    const startDay = firstOfMonth.getDay();
    const gridStart = new Date(firstOfMonth);
    gridStart.setDate(firstOfMonth.getDate() - startDay);

    calendarGrid.innerHTML = '';
    for (let i = 0; i < 42; i += 1) {
        const cellDate = new Date(gridStart);
        cellDate.setDate(gridStart.getDate() + i);
        const iso = toIsoDate(cellDate);
        const tasksForDate = taskMap[iso] || [];
        const isCurrentMonth = cellDate.getMonth() === state.current.getMonth();
        const isToday = isSameDate(cellDate, new Date());
        const isSelected = isSameDate(cellDate, state.selected);

        const cell = document.createElement('button');
        cell.type = 'button';
        cell.className = `calendar-day${isCurrentMonth ? '' : ' calendar-day--muted'}${isToday ? ' calendar-day--today' : ''}${isSelected ? ' calendar-day--selected' : ''}${tasksForDate.length ? ' calendar-day--has-tasks' : ''}`;
        cell.dataset.date = iso;
        cell.innerHTML = `
            <span class="date-number">${cellDate.getDate()}</span>
            ${tasksForDate.length ? '<span class="day-dot"></span>' : ''}
        `;

        cell.addEventListener('click', () => {
            state.selected = new Date(cellDate);
            render();
        });
        cell.addEventListener('mouseenter', () => showTooltip(iso, tasksForDate, cell));
        cell.addEventListener('mouseleave', hideTooltip);

        calendarGrid.appendChild(cell);
    }
}

function getWeekDates(date) {
    const week = [];
    const firstDay = new Date(date);
    firstDay.setDate(date.getDate() - date.getDay());

    for (let i = 0; i < 7; i += 1) {
        const nextDay = new Date(firstDay);
        nextDay.setDate(firstDay.getDate() + i);
        week.push(nextDay);
    }
    return week;
}

function renderWeekStrip() {
    const currentWeek = getWeekDates(state.selected);
    calendarWeekStrip.innerHTML = '';

    currentWeek.forEach(day => {
        const iso = toIsoDate(day);
        const tasksForDate = taskMap[iso] || [];
        const isToday = isSameDate(day, new Date());
        const isSelected = isSameDate(day, state.selected);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = `week-day${isSelected ? ' week-day--selected' : ''}${isToday ? ' week-day--today' : ''}`;
        button.innerHTML = `
            <span>${day.toLocaleDateString('en-US', { weekday: 'short' })}</span>
            <strong>${day.getDate()}</strong>
            ${tasksForDate.length ? '<span class="week-dot"></span>' : ''}
        `;

        button.addEventListener('click', () => {
            state.selected = new Date(day);
            render();
        });

        calendarWeekStrip.appendChild(button);
    });
}

function renderTimeline() {
    const selected = state.selected;
    timelineLabel.textContent = formatDayLabel(selected);
    timelineHours.innerHTML = '';
    timelineGrid.innerHTML = '';

    for (let hour = 7; hour <= 20; hour += 1) {
        const label = hour % 12 === 0 ? 12 : hour % 12;
        const period = hour < 12 ? 'AM' : 'PM';
        const timeLabel = `${label} ${period}`;

        const labelCell = document.createElement('div');
        labelCell.className = 'timeline-hour-label';
        labelCell.textContent = timeLabel;
        timelineHours.appendChild(labelCell);

        const row = document.createElement('div');
        row.className = 'timeline-row';
        timelineGrid.appendChild(row);
    }
}

function renderDayView() {
    const selected = state.selected;
    const selectedKey = toIsoDate(selected);
    const tasksForDay = taskMap[selectedKey] || [];

    dayViewLabel.textContent = formatDayLabel(selected);
    dayTaskList.innerHTML = '';

    if (tasksForDay.length === 0) {
        dayTaskList.innerHTML = `<div class="empty-state card-empty"><div class="empty-state-icon">☁</div><h3>No tasks scheduled</h3><p>Choose another day or add a new task with a due date.</p></div>`;
        return;
    }

    tasksForDay.forEach(task => {
        const card = document.createElement('article');
        card.className = 'task-card';
        card.innerHTML = `
            <div>
                <div class="task-card-meta">
                    <span class="task-card-title">${escapeHtml(task.title)}</span>
                    <span class="task-card-priority priority-${task.priority.toLowerCase()}">${escapeHtml(task.priority)}</span>
                </div>
                <p class="task-card-desc">${escapeHtml(task.description || 'No description added.')}</p>
            </div>
            <div class="task-card-time">
                <span>All day</span>
                <small>${escapeHtml(task.category || 'Personal')}</small>
            </div>
        `;
        dayTaskList.appendChild(card);
    });
}

function renderDetailPanel() {
    const selected = state.selected;
    const iso = toIsoDate(selected);
    const tasksForDate = taskMap[iso] || [];

    selectedDateLabel.textContent = formatLongDate(selected);
    selectedTaskCount.textContent = `${tasksForDate.length} task${tasksForDate.length === 1 ? '' : 's'} scheduled`;
    detailTaskList.innerHTML = '';

    if (tasksForDate.length === 0) {
        detailTaskList.innerHTML = `<div class="empty-state card-empty"><div class="empty-state-icon">✓</div><h3>No tasks due</h3><p>Try a different date to find scheduled work.</p></div>`;
        return;
    }

    tasksForDate.forEach(task => {
        const item = document.createElement('article');
        item.className = 'detail-task-row';
        item.innerHTML = `
            <div class="detail-task-title-row">
                <h3>${escapeHtml(task.title)}</h3>
                <span class="priority-pill priority-${task.priority.toLowerCase()}">${escapeHtml(task.priority)}</span>
            </div>
            <p class="task-meta">${escapeHtml(task.description || 'No description provided.')}</p>
            <div class="detail-task-foot">
                <span>${escapeHtml(task.category || 'Personal')}</span>
                <span>Due ${formatLongDate(new Date(task.due_date))}</span>
            </div>
        `;
        detailTaskList.appendChild(item);
    });
}

function showTooltip(dateKey, tasks, target) {
    if (!tasks || tasks.length === 0) {
        hideTooltip();
        return;
    }

    calendarTooltip.innerHTML = `
        <strong>${tasks.length} task${tasks.length === 1 ? '' : 's'}</strong>
        <div class="tooltip-list">
            ${tasks.slice(0, 3).map(task => `
                <div class="tooltip-item">
                    <span class="tooltip-title">${escapeHtml(task.title)}</span>
                    <small>${escapeHtml(task.priority)} • All day</small>
                </div>
            `).join('')}
        </div>
    `;
    calendarTooltip.classList.remove('hidden');

    const rect = target.getBoundingClientRect();
    const parentRect = document.querySelector('.calendar-main').getBoundingClientRect();
    const left = rect.left - parentRect.left + rect.width / 2;
    calendarTooltip.style.left = `${Math.min(Math.max(left, 16), parentRect.width - 280)}px`;
    calendarTooltip.style.top = `${rect.top - parentRect.top - 12}px`;
}

function hideTooltip() {
    if (calendarTooltip) {
        calendarTooltip.classList.add('hidden');
    }
}

function escapeHtml(text) {
    if (typeof text !== 'string') return '';
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function render() {
    updateHeader();
    viewButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.view === state.view));
    modeButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.mode === state.mode));

    const calendarWeekdays = document.querySelector('.calendar-weekdays');

    if (state.view === 'month') {
        calendarWeekStrip.classList.add('hidden');
        calendarTimeline.classList.add('hidden');
        calendarGrid.classList.remove('hidden');
        calendarWeekdays.classList.remove('hidden');
        dayViewPanel.classList.add('hidden');
        renderMonthView();
    } else {
        calendarWeekStrip.classList.remove('hidden');
        calendarTimeline.classList.remove('hidden');
        calendarGrid.classList.add('hidden');
        calendarWeekdays.classList.add('hidden');
        dayViewPanel.classList.remove('hidden');
        renderWeekStrip();
        renderTimeline();
        renderDayView();
    }

    renderDetailPanel();
}

prevBtn.addEventListener('click', () => {
    if (state.view === 'month') {
        state.current = new Date(state.current.getFullYear(), state.current.getMonth() - 1, 1);
    } else {
        state.selected.setDate(state.selected.getDate() - 1);
        state.current = new Date(state.selected.getFullYear(), state.selected.getMonth(), 1);
    }
    render();
});

nextBtn.addEventListener('click', () => {
    if (state.view === 'month') {
        state.current = new Date(state.current.getFullYear(), state.current.getMonth() + 1, 1);
    } else {
        state.selected.setDate(state.selected.getDate() + 1);
        state.current = new Date(state.selected.getFullYear(), state.selected.getMonth(), 1);
    }
    render();
});

jumpToToday.addEventListener('click', () => {
    state.current = new Date();
    state.selected = new Date();
    render();
});

closeDetailPanel.addEventListener('click', () => {
    document.getElementById('detailPanel').classList.toggle('calendar-panel--closed');
});

viewButtons.forEach(button => {
    button.addEventListener('click', () => {
        state.view = button.dataset.view;
        render();
    });
});

modeButtons.forEach(button => {
    button.addEventListener('click', () => {
        state.mode = button.dataset.mode;
        render();
    });
});

render();
</script>

<?php require __DIR__ . '/partial/footer.php'; ?>
