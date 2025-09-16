<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
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

$employeeId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $taskId = $_POST['task_id'];
    $statusId = $_POST['status_id'];
    
    try {
        $stmt = $db->prepare('UPDATE tasks SET status_id = ? WHERE task_id = ? AND assigned_to = ?');
        $stmt->execute([$statusId, $taskId, $employeeId]);
        
        $stmt = $db->prepare('INSERT INTO activity_logs (user_id, action, related_task) VALUES (?, ?, ?)');
        $stmt->execute([$employeeId, 'Updated task status', $taskId]);
        
        $_SESSION['success_message'] = 'Task status updated successfully!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error updating task status: ' . $e->getMessage();
    }
    header('Location: assign-task.php');
    exit;
}

try {
    $stmt = $db->prepare('SELECT 
            p.project_id,
            p.project_name,
            p.description,
            p.created_at,
            (SELECT MIN(t.deadline) FROM tasks t WHERE t.project_id = p.project_id AND t.assigned_to = ?) AS my_next_deadline,
            u.full_name AS created_by,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id) AS total_tasks,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.assigned_to = ?) AS my_tasks,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.assigned_to = ? AND t.deadline < CURDATE() AND t.status_id <> (SELECT status_id FROM statuses WHERE name="completed" LIMIT 1)) AS my_overdue
        FROM projects p
        INNER JOIN project_members pm ON pm.project_id = p.project_id
        LEFT JOIN users u ON p.created_by = u.user_id
        WHERE pm.user_id = ?
        ORDER BY p.project_name');
    $stmt->execute([$employeeId, $employeeId, $employeeId, $employeeId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalProjects = count($projects);
    $totalTasks = array_sum(array_map(function($p){ return (int)$p['my_tasks']; }, $projects));
    $overdueTasks = array_sum(array_map(function($p){ return (int)$p['my_overdue']; }, $projects));

    $inProgressId = $db->query('SELECT status_id FROM statuses WHERE name="in_progress"')->fetchColumn();
    $stmt = $db->prepare('SELECT COUNT(*) FROM tasks t 
        INNER JOIN project_members pm ON pm.project_id = t.project_id 
        WHERE pm.user_id = ? AND t.assigned_to = ? AND t.status_id = ?');
    $stmt->execute([$employeeId, $employeeId, $inProgressId]);
    $inProgressTasks = (int)$stmt->fetchColumn();

    $completedId = $db->query('SELECT status_id FROM statuses WHERE name="completed"')->fetchColumn();
    $stmt = $db->prepare('SELECT COUNT(*) AS c FROM (
            SELECT p.project_id
            FROM projects p
            INNER JOIN project_members pm ON pm.project_id = p.project_id
            LEFT JOIN tasks t ON t.project_id = p.project_id
            WHERE pm.user_id = :uid
            GROUP BY p.project_id
            HAVING SUM(CASE WHEN t.status_id <> :cid THEN 1 ELSE 0 END) = 0 AND COUNT(t.task_id) > 0
        ) sub');
    $stmt->bindValue(':uid', (int)$employeeId, PDO::PARAM_INT);
    $stmt->bindValue(':cid', (int)$completedId, PDO::PARAM_INT);
    $stmt->execute();
    $completedProjects = (int)$stmt->fetchColumn();

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'completed': return 'success';
        case 'in_progress': return 'warning';
        case 'pending': return 'secondary';
        case 'overdue': return 'danger';
        default: return 'secondary';
    }
}

function getPriorityClass($priority) {
    switch (strtolower($priority)) {
        case 'high': return 'danger';
        case 'medium': return 'warning';
        case 'low': return 'success';
        default: return 'secondary';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Projects - Task Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../logo.png" alt="Logo" class="logo-img">
                <span>Task Management</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="assign-task.php">
                            <i class="fas fa-clipboard-list me-1"></i>My Projects
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <section class="stats-section fade-in-up">
            <div class="stats-grid">
                <div class="stats-card">
                    <div class="stats-card-body">
                        <div class="stats-content">
                            <h6>Total Projects</h6>
                            <p class="stats-number"><?php echo $totalProjects; ?></p>
                        </div>
                        <div class="stats-icon bg-secondary">
                            <i class="fas fa-folder-open"></i>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-body">
                        <div class="stats-content">
                            <h6>Completed</h6>
                            <p class="stats-number"><?php echo $completedProjects; ?></p>
                        </div>
                        <div class="stats-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-body">
                        <div class="stats-content">
                            <h6>In Progress</h6>
                            <p class="stats-number"><?php echo $inProgressTasks; ?></p>
                        </div>
                        <div class="stats-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-body">
                        <div class="stats-content">
                            <h6>Overdue</h6>
                            <p class="stats-number"><?php echo $overdueTasks; ?></p>
                        </div>
                        <div class="stats-icon bg-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="projects-section fade-in-up">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-folder-tree me-2"></i>Projects You're Part Of
                </h2>
                <div class="text-muted">
                    <small><?php echo count($projects); ?> project<?php echo count($projects) !== 1 ? 's' : ''; ?> found</small>
                </div>
            </div>

            <?php if (empty($projects)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <h3 class="empty-state-title">No Projects Found</h3>
                    <p class="empty-state-text">You are not yet a member of any projects. Contact your manager to get assigned to projects.</p>
                </div>
            <?php else: ?>
                <div class="projects-grid">
                    <?php foreach ($projects as $index => $project): ?>
                        <div class="project-card fade-in-up clickable-card" 
                             style="animation-delay: <?php echo ($index * 0.1) . 's'; ?>"
                             data-project-id="<?php echo $project['project_id']; ?>"
                             data-project-name="<?php echo htmlspecialchars($project['project_name']); ?>"
                             data-project-description="<?php echo htmlspecialchars($project['description']); ?>"
                             data-created-by="<?php echo htmlspecialchars($project['created_by']); ?>"
                             data-created-at="<?php echo $project['created_at']; ?>"
                             data-total-tasks="<?php echo $project['total_tasks']; ?>"
                             data-my-tasks="<?php echo $project['my_tasks']; ?>"
                             data-my-overdue="<?php echo $project['my_overdue']; ?>"
                             data-my-next-deadline="<?php echo $project['my_next_deadline']; ?>">
                            <div class="project-card-header">
                                <div class="project-header-top">
                                    <div class="project-icon-wrapper">
                                        <i class="fas fa-project-diagram project-main-icon"></i>
                                    </div>
                                    <h5 class="project-title">
                                        <i class="fas fa-folder me-2"></i>
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </h5>
                                </div>
                                <div class="project-meta">
                                    <span class="project-badge">
                                        <i class="fa-regular fa-calendar-days me-1"></i>
                                        <?php echo $project['my_next_deadline'] ? date('M j, Y', strtotime($project['my_next_deadline'])) : 'No deadline'; ?>
                                    </span>
                                    <span class="project-badge">
                                        <i class="fa-solid fa-tasks me-1"></i>
                                        <?php echo $project['my_tasks']; ?> task<?php echo $project['my_tasks'] !== 1 ? 's' : ''; ?>
                                    </span>
                                    <?php if ($project['my_overdue'] > 0): ?>
                                        <span class="project-badge" style="background: rgba(220, 53, 69, 0.1); color: var(--danger); border-color: rgba(220, 53, 69, 0.3);">
                                            <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                            <?php echo $project['my_overdue']; ?> overdue
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="project-body">
                                <div class="project-info">
                                    <div class="info-item">
                                        <div class="info-icon-wrapper">
                                            <i class="fa-solid fa-user-tie info-icon"></i>
                                        </div>
                                        <div class="info-content">
                                            <small class="info-label">Created by</small>
                                            <small class="info-value"><?php echo htmlspecialchars($project['created_by']); ?></small>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-icon-wrapper">
                                            <i class="fa-solid fa-calendar-plus info-icon"></i>
                                        </div>
                                        <div class="info-content">
                                            <small class="info-label">Started</small>
                                            <small class="info-value"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-icon-wrapper">
                                            <i class="fa-solid fa-list-check info-icon"></i>
                                        </div>
                                        <div class="info-content">
                                            <small class="info-label">Total tasks</small>
                                            <small class="info-value"><?php echo $project['total_tasks']; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="project-actions">
                                    <button class="btn btn-sm btn-outline-primary view-details-btn">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Project Details Modal -->
    <div class="modal fade" id="projectDetailsModal" tabindex="-1" aria-labelledby="projectDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectDetailsModalLabel">
                        <i class="fas fa-folder me-2"></i>
                        <span id="modalProjectName">Project Details</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="project-details-content">
                        <!-- Project Overview -->
                        <div class="detail-section">
                            <h6 class="detail-section-title">
                                <i class="fas fa-info-circle me-2"></i>Project Overview
                            </h6>
                            <div class="detail-content">
                                <div class="detail-item">
                                    <label>Description:</label>
                                    <p id="modalProjectDescription" class="detail-value">No description available</p>
                                </div>
                                <div class="detail-item">
                                    <label>Created by:</label>
                                    <p id="modalCreatedBy" class="detail-value">-</p>
                                </div>
                                <div class="detail-item">
                                    <label>Start Date:</label>
                                    <p id="modalCreatedAt" class="detail-value">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Task Statistics -->
                        <div class="detail-section">
                            <h6 class="detail-section-title">
                                <i class="fas fa-chart-bar me-2"></i>Task Statistics
                            </h6>
                            <div class="stats-grid-modal">
                                <div class="stat-item">
                                    <div class="stat-icon bg-primary">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-number" id="modalTotalTasks">0</span>
                                        <span class="stat-label">Total Tasks</span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon bg-info">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-number" id="modalMyTasks">0</span>
                                        <span class="stat-label">My Tasks</span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon bg-danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-number" id="modalOverdueTasks">0</span>
                                        <span class="stat-label">Overdue</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Important Dates -->
                        <div class="detail-section">
                            <h6 class="detail-section-title">
                                <i class="fas fa-calendar-alt me-2"></i>Important Dates
                            </h6>
                            <div class="detail-content">
                                <div class="detail-item">
                                    <label>Next Deadline:</label>
                                    <p id="modalNextDeadline" class="detail-value">No upcoming deadlines</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" id="viewTasksBtn">
                        <i class="fas fa-list me-1"></i>View Tasks
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
