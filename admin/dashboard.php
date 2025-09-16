<?php

require_once '../config/database.php';

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: ../auth/login.php');
	exit;
}

$db = null;
if (class_exists('Database')) {
	$db = (new Database())->getConnection();
} elseif (isset($pdo) && $pdo instanceof PDO) {
	$db = $pdo;
} else {
	die('Database connection not found. Ensure config/database.php provides $pdo or a Database class.');
}

try {
	$stmt = $db->prepare('SELECT COUNT(*) AS total_projects FROM projects');
	$stmt->execute();
	$totalProjects = $stmt->fetch(PDO::FETCH_ASSOC)['total_projects'] ?? 0;
	$stmt = $db->prepare('SELECT COUNT(*) AS total_users FROM users');
	$stmt->execute();
	$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'] ?? 0;
	$completedIdForProj = $db->query("SELECT status_id FROM statuses WHERE name='completed'")->fetchColumn();
	$sqlOverdueProj = 'SELECT COUNT(DISTINCT t.project_id) AS c FROM tasks t WHERE t.deadline < CURDATE()';
	if ($completedIdForProj !== false && $completedIdForProj !== null) {
		$sqlOverdueProj .= ' AND t.status_id <> :completed_id_proj';
	}
	$stmt = $db->prepare($sqlOverdueProj);
	if ($completedIdForProj !== false && $completedIdForProj !== null) {
		$stmt->bindValue(':completed_id_proj', (int)$completedIdForProj, PDO::PARAM_INT);
	}
	$stmt->execute();
	$overdueProjectsCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
	$inProgressIdForProj = $db->query("SELECT status_id FROM statuses WHERE name='in_progress'")->fetchColumn();
	$pendingIdForProj = $db->query("SELECT status_id FROM statuses WHERE name='pending'")->fetchColumn();
	$overdueIdForProj = $db->query("SELECT status_id FROM statuses WHERE name='overdue'")->fetchColumn();
	$overdueProjIds = [];
	$qParts = [];
	$params = [];
	if ($overdueIdForProj !== false && $overdueIdForProj !== null) {
		$qParts[] = 'SELECT DISTINCT project_id FROM tasks WHERE status_id = :oid';
		$params[':oid'] = (int)$overdueIdForProj;
	}
	$qParts[] = 'SELECT DISTINCT project_id FROM tasks WHERE deadline < CURDATE()' . ($completedIdForProj !== false && $completedIdForProj !== null ? ' AND status_id <> :cid' : '');
	if ($completedIdForProj !== false && $completedIdForProj !== null) { $params[':cid'] = (int)$completedIdForProj; }
	$q = implode(' UNION ', $qParts);
	$stmt = $db->prepare($q);
	foreach ($params as $k => $v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
	$stmt->execute();
	$overdueProjIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
	$inProgressProjIds = [];
	if ($inProgressIdForProj !== false && $inProgressIdForProj !== null) {
		$stmt = $db->prepare('SELECT DISTINCT project_id FROM tasks WHERE status_id = :sid');
		$stmt->bindValue(':sid', (int)$inProgressIdForProj, PDO::PARAM_INT);
		$stmt->execute();
		$inProgressProjIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
	}
	$pendingProjIds = [];
	if ($pendingIdForProj !== false && $pendingIdForProj !== null) {
		$stmt = $db->prepare('SELECT DISTINCT project_id FROM tasks WHERE status_id = :sid');
		$stmt->bindValue(':sid', (int)$pendingIdForProj, PDO::PARAM_INT);
		$stmt->execute();
		$pendingProjIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
	}

	$completedProjIds = [];
	if ($completedIdForProj !== false && $completedIdForProj !== null) {
		$sql = 'SELECT p.project_id
			FROM projects p
			JOIN tasks t ON t.project_id = p.project_id
			GROUP BY p.project_id
			HAVING SUM(CASE WHEN t.status_id <> :cid THEN 1 ELSE 0 END) = 0 AND COUNT(*) > 0';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':cid', (int)$completedIdForProj, PDO::PARAM_INT);
		$stmt->execute();
		$completedProjIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
	}
	$overdueSet = array_flip($overdueProjIds);
	$inProgressSet = [];
	foreach ($inProgressProjIds as $pid) { if (!isset($overdueSet[$pid])) $inProgressSet[$pid] = true; }
	$pendingSet = [];
	foreach ($pendingProjIds as $pid) { if (!isset($overdueSet[$pid]) && !isset($inProgressSet[$pid])) $pendingSet[$pid] = true; }
	$completedSet = [];
	foreach ($completedProjIds as $pid) { if (!isset($overdueSet[$pid]) && !isset($inProgressSet[$pid]) && !isset($pendingSet[$pid])) $completedSet[$pid] = true; }
	$projectsWithTasks = $db->query('SELECT DISTINCT project_id FROM tasks')->fetchAll(PDO::FETCH_COLUMN);
	$projectsWithTasksSet = array_flip(array_map('intval', $projectsWithTasks));
	$allProjectIds = $db->query('SELECT project_id FROM projects')->fetchAll(PDO::FETCH_COLUMN);
	foreach ($allProjectIds as $pidRaw) {
		$pid = (int)$pidRaw;
		if (!isset($projectsWithTasksSet[$pid]) && !isset($overdueSet[$pid]) && !isset($inProgressSet[$pid]) && !isset($completedSet[$pid])) {
			$pendingSet[$pid] = true;
		}
	}

	$projectsOverdueCount = count($overdueSet);
	$projectsInProgressCount = count($inProgressSet);
	$projectsPendingCount = count($pendingSet);
	$projectsCompletedCount = count($completedSet);
	$stmt = $db->prepare('SELECT status_id, name FROM statuses ORDER BY status_id');
	$stmt->execute();
	$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$completedId = null;
	$overdueFound = false;
	foreach ($statuses as $s) {
		if (strtolower($s['name']) === 'completed') { $completedId = (int)$s['status_id']; break; }
	}
	$sqlOverdue = 'SELECT COUNT(*) AS c FROM tasks WHERE deadline < CURDATE()';
	if (!is_null($completedId)) { $sqlOverdue .= ' AND status_id <> :completed_id'; }
	$stmt = $db->prepare($sqlOverdue);
	if (!is_null($completedId)) { $stmt->bindValue(':completed_id', $completedId, PDO::PARAM_INT); }
	$stmt->execute();
	$overdueCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
	$tasksByStatus = [];
	foreach ($statuses as $s) {
		$name = strtolower($s['name']);
		$count = 0;
		$st = $db->prepare('SELECT COUNT(*) AS c FROM tasks WHERE status_id = :sid');
		$st->bindValue(':sid', (int)$s['status_id'], PDO::PARAM_INT);
		$st->execute();
		$count = (int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
		if ($name === 'overdue') { $overdueFound = true; }
		$tasksByStatus[] = ['status_name' => $name, 'count' => $count];
	}
	if (!$overdueFound) {
		$tasksByStatus[] = ['status_name' => 'overdue', 'count' => $overdueCount];
	}
	$stmt = $db->prepare('SELECT t.task_id, t.title, t.deadline, u.full_name AS assignee, p.project_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.user_id LEFT JOIN projects p ON t.project_id = p.project_id WHERE t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY t.deadline ASC LIMIT 10');
	$stmt->execute();
	$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$stmt = $db->prepare('SELECT a.log_id, a.action, a.created_at, u.full_name FROM activity_logs a LEFT JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 10');
	$stmt->execute();
	$recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	die('Database error: ' . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard - Task Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="inc/sidebar.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
</head>
<body>
    <div class="d-flex">
        <?php $active='dashboard'; include __DIR__ . '/inc/sidebar.php'; ?>
        <div class="flex-grow-1">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Dashboard</h1>
                    <div class="text-muted small"><?php echo date('l, F j, Y'); ?></div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Projects</h6>
                                        <p class="display-6"><?php echo $totalProjects; ?></p>
                                    </div>
                                    <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Users</h6>
                                        <p class="display-6"><?php echo $totalUsers; ?></p>
                                    </div>
                                    <div class="icon-circle bg-success bg-opacity-10 text-success">
                                        <i class="fa-solid fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Upcoming</h6>
                                        <p class="display-6"><?php echo count($upcoming); ?></p>
                                    </div>
                                    <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                                        <i class="fa-solid fa-calendar-days"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-transparent border-0">
                                <h5 class="card-title mb-0">Tasks by Status</h5>
                            </div>
                            <div class="card-body d-flex justify-content-center">
                                <div class="chart-container" style="position: relative; height:300px; width:100%;">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Upcoming Deadlines</h5>
                                <span class="badge bg-primary">Next 7 Days</span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($upcoming)): ?>
                                    <div class="text-center p-4 text-muted">
                                        <i class="fa-solid fa-circle-check fa-2x mb-2"></i>
                                        <p class="mb-0">No upcoming deadlines in the next 7 days</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Task</th>
                                                    <th>Project</th>
                                                    <th>Due Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($upcoming as $t): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-2">
                                                                    <i class="fa-solid fa-list-check text-primary"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-medium"><?php echo htmlspecialchars($t['title']); ?></div>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($t['assignee']); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($t['project_name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">
                                                                <i class="fa-regular fa-calendar-days me-1"></i>
                                                                <?php echo date('M j', strtotime($t['deadline'])); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inc/sidebar.js" defer></script>
    <script>
        window.statusLabels = <?php echo json_encode(array_map(function($r){ return ucwords(str_replace('_',' ',$r['status_name'])); }, $tasksByStatus)); ?>;
        window.statusData = <?php echo json_encode(array_map(function($r){ return (int)$r['count']; }, $tasksByStatus)); ?>;
    </script>
    <script src="js/dashboard.js" defer></script>
</body>
</html>
