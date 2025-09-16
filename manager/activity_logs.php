<?php

require_once '../config/database.php';

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
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
	$q = trim($_GET['q'] ?? '');
	$userId = $_GET['user_id'] ?? '';
	$dateFrom = $_GET['date_from'] ?? '';
	$dateTo = $_GET['date_to'] ?? '';
	$perPage = max(5, min(50, (int)($_GET['per_page'] ?? 10)));
	$page = max(1, (int)($_GET['page'] ?? 1));
	$where = [];
	$params = [];
	if ($q !== '') {
		$where[] = 'a.action LIKE :q';
		$params[':q'] = "%$q%";
	}
	if ($userId !== '' && ctype_digit((string)$userId)) {
		$where[] = 'a.user_id = :uid';
		$params[':uid'] = (int)$userId;
	}
	if ($dateFrom !== '') {
		$where[] = 'DATE(a.created_at) >= :dfrom';
		$params[':dfrom'] = $dateFrom;
	}
	if ($dateTo !== '') {
		$where[] = 'DATE(a.created_at) <= :dto';
		$params[':dto'] = $dateTo;
	}
	$whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
	$stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM activity_logs a $whereSql");
	$stmt->execute($params);
	$total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
	$totalPages = max(1, (int)ceil($total / $perPage));
	$page = min($page, $totalPages);
	$offset = ($page - 1) * $perPage;
	$sql = "SELECT a.log_id, a.action, a.created_at, a.related_task, u.full_name
			FROM activity_logs a
			LEFT JOIN users u ON a.user_id = u.user_id
			$whereSql
			ORDER BY a.created_at DESC
			LIMIT :limit OFFSET :offset";
	$stmt = $db->prepare($sql);
	foreach ($params as $k => $v) {
		$stmt->bindValue($k, $v);
	}
	$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
	$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
	$stmt->execute();
	$recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$users = $db->query('SELECT user_id, full_name FROM users ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Task Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/sidebar/sidebar.css">
    <link rel="stylesheet" href="assets/css/activity_log.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
    <div class="d-flex">
        <?php $active='activity'; include 'assets/sidebar/sidebar.php'; ?>
        <div class="flex-grow-1">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">Activity Logs</h1>
                        <p class="text-muted small mb-0">Monitor all system activities and user actions</p>
                    </div>
                    <div>
                        <span class="badge bg-primary">Total: <?php echo $total; ?> logs</span>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Filter Logs</h5>
                        <div>
                            <?php if ($q !== '' || $userId !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                                <a href="activity_logs.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-rotate-left me-1"></i>
                                    Reset Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form class="row g-3 align-items-end" method="get" action="">
                            <div class="col-12 col-md-3">
                                <label class="form-label">User</label>
                                <select name="user_id" class="form-select">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo (int)$u["user_id"]; ?>" <?php echo ($userId !== '' && (int)$userId === (int)$u['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="q" class="form-control" placeholder="Search action..." value="<?php echo htmlspecialchars($q); ?>">
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label">Per Page</label>
                                <select name="per_page" class="form-select">
                                    <?php foreach ([10, 20, 30, 40, 50] as $pp): ?>
                                        <option value="<?php echo $pp; ?>" <?php echo $perPage === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-1 d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>
                                    Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Activity Timeline</h5>
                        <button class="btn btn-outline-secondary btn-sm" onclick="refreshLogs()">
                            <i class="fas fa-sync-alt me-1"></i>
                            Refresh
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentLogs)): ?>
                            <div class="text-center p-4 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0">No logs available.</p>
                            </div>
                        <?php else: ?>
                            <ul class="activity-log">
                                <?php foreach ($recentLogs as $log): ?>
                                    <li class="d-flex align-items-start p-3 border-bottom">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                                                <?php echo strtoupper(substr($log['full_name'] ?? 'S', 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="text-primary"><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></strong>
                                                <small class="text-muted"><?php echo htmlspecialchars(time_elapsed_string($log['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-0"><?php echo htmlspecialchars($log['action']); ?></p>
                                            <?php if (!empty($log['related_task'])): ?>
                                                <small class="text-muted">Task #<?php echo (int)$log['related_task']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white">
                        <nav aria-label="Logs pagination">
                            <ul class="pagination mb-0">
                                <?php
                                    $qs = $_GET;
                                    unset($qs['page']);
                                    $base = 'activity_logs.php';
                                    $queryBase = http_build_query($qs);
                                    $makeUrl = function($p) use ($base, $queryBase) {
                                        $qs = $queryBase !== '' ? ($queryBase . '&') : '';
                                        return $base . '?' . $qs . 'page=' . $p;
                                    };
                                ?>
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $makeUrl(max(1, $page - 1)); ?>">Previous</a>
                                </li>
                                <?php for($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $makeUrl($p); ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $makeUrl(min($totalPages, $page + 1)); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/sidebar/sidebar.js"></script>
    <script src="assets/js/activity_logs.js"></script>
</body>
</html>

