<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    $_SESSION['flash_error'] = 'Invalid session. Please log in again.';
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = isset($pdo) ? $pdo : (class_exists('Database') ? (new Database())->getConnection() : null);
if (!$db) {
    die('Database connection failed. Please try again later.');
}

try {
    $stmtUserCheck = $db->prepare('SELECT user_id, role FROM users WHERE user_id = ?');
    $stmtUserCheck->execute([$_SESSION['user_id']]);
    $currentUser = $stmtUserCheck->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        session_destroy();
        $_SESSION['flash_error'] = 'User not found. Please log in again.';
        header('Location: ../auth/login.php');
        exit;
    }

    if ($currentUser['role'] !== 'admin') {
        $_SESSION['flash_error'] = 'Access denied. Admin privileges required.';
        header('Location: ../index.php');
        exit;
    }
} catch (Throwable $e) {
    error_log('User validation error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'An error occurred while validating your account.';
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    $name = isset($_POST['project_name']) ? trim($_POST['project_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $members = isset($_POST['members']) && is_array($_POST['members']) ? array_filter($_POST['members'], 'strlen') : [];
    $addMeAsManager = isset($_POST['add_me_manager']) ? (bool)$_POST['add_me_manager'] : true;
    $managerUserId = isset($_POST['manager_user_id']) && $_POST['manager_user_id'] !== '' ? (int)$_POST['manager_user_id'] : null;
    $createInitialTask = isset($_POST['create_initial_task']) ? (bool)$_POST['create_initial_task'] : false;
    $taskTitle = isset($_POST['task_title']) ? trim($_POST['task_title']) : '';
    $taskDescription = isset($_POST['task_description']) ? trim($_POST['task_description']) : null;
    $taskPriorityId = isset($_POST['priority_id']) && $_POST['priority_id'] !== '' ? (int)$_POST['priority_id'] : null;
    $taskStatusId = isset($_POST['status_id']) && $_POST['status_id'] !== '' ? (int)$_POST['status_id'] : null;
    $taskDeadline = isset($_POST['deadline']) ? $_POST['deadline'] : null; // expect YYYY-MM-DD
    $reminderDatetime = isset($_POST['reminder_datetime']) ? trim($_POST['reminder_datetime']) : null;
    $taskComment = isset($_POST['task_comment']) ? trim($_POST['task_comment']) : null;

    if (empty($name)) {
        $_SESSION['flash_error'] = 'Project name is required.';
        header('Location: manage_projects.php');
        exit;
    }
    if (!$addMeAsManager && $managerUserId) {
        $stmtCheckManager = $db->prepare('SELECT user_id FROM users WHERE user_id = ? AND role IN ("admin", "manager")');
        $stmtCheckManager->execute([$managerUserId]);
        if (!$stmtCheckManager->fetch()) {
            $_SESSION['flash_error'] = 'Invalid manager selected.';
            header('Location: manage_projects.php');
            exit;
        }
    }
    if (!empty($members)) {
        $placeholders = str_repeat('?,', count($members) - 1) . '?';
        $stmtCheckMembers = $db->prepare("SELECT COUNT(*) FROM users WHERE user_id IN ($placeholders)");
        $stmtCheckMembers->execute($members);
        if ($stmtCheckMembers->fetchColumn() != count($members)) {
            $_SESSION['flash_error'] = 'One or more selected members are invalid.';
            header('Location: manage_projects.php');
            exit;
        }
    }
    if ($createInitialTask) {
        if (empty($taskTitle) || empty($taskDeadline)) {
            $_SESSION['flash_error'] = 'Task title and deadline are required when creating an initial task.';
            header('Location: manage_projects.php');
            exit;
        }
        if ($taskPriorityId) {
            $stmtCheckPriority = $db->prepare('SELECT priority_id FROM priorities WHERE priority_id = ?');
            $stmtCheckPriority->execute([$taskPriorityId]);
            if (!$stmtCheckPriority->fetch()) {
                $_SESSION['flash_error'] = 'Invalid priority selected.';
                header('Location: manage_projects.php');
                exit;
            }
        }

        if ($taskStatusId) {
            $stmtCheckStatus = $db->prepare('SELECT status_id FROM statuses WHERE status_id = ?');
            $stmtCheckStatus->execute([$taskStatusId]);
            if (!$stmtCheckStatus->fetch()) {
                $_SESSION['flash_error'] = 'Invalid status selected.';
                header('Location: manage_projects.php');
                exit;
            }
        }
    }

    try {
        $db->beginTransaction();
        $stmtCheckName = $db->prepare('SELECT project_id FROM projects WHERE project_name = ?');
        $stmtCheckName->execute([$name]);
        if ($stmtCheckName->fetch()) {
            throw new Exception('A project with this name already exists.');
        }
        $stmt = $db->prepare('INSERT INTO projects (project_name, description, created_by) VALUES (:name, :description, :created_by)');
        $stmt->execute([
            ':name' => $name,
            ':description' => $description !== '' ? $description : null,
            ':created_by' => $_SESSION['user_id'] ?? null,
        ]);
        $projectId = (int)$db->lastInsertId();
        $managerToAssign = null;
        if ($addMeAsManager && !empty($_SESSION['user_id'])) {
            $managerToAssign = (int)$_SESSION['user_id'];
        } elseif (!$addMeAsManager && $managerUserId) {
            $managerToAssign = $managerUserId;
        }
        if (!empty($members) || $managerToAssign) {
            $stmtPM = $db->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (:pid, :uid, :role)');
            if ($managerToAssign) {
                $stmtPM->execute([
                    ':pid' => $projectId,
                    ':uid' => $managerToAssign,
                    ':role' => 'manager',
                ]);
            }
            if (!empty($members)) {
                foreach ($members as $uid) {
                    $uid = (int)$uid;
                    if ($uid <= 0) continue;
                    if ($managerToAssign && $uid === $managerToAssign) {
                        continue;
                    }

                    $stmtPM->execute([
                        ':pid' => $projectId,
                        ':uid' => $uid,
                        ':role' => 'contributor',
                    ]);
                }
            }
        }
        if ($createInitialTask) {
            $assignedTo = $managerToAssign ?: null;
            $stmtTask = $db->prepare('INSERT INTO tasks (project_id, assigned_to, title, description, priority_id, status_id, deadline) VALUES (:project_id, :assigned_to, :title, :description, :priority_id, :status_id, :deadline)');
            $stmtTask->execute([
                ':project_id' => $projectId,
                ':assigned_to' => $assignedTo,
                ':title' => $taskTitle,
                ':description' => $taskDescription !== '' ? $taskDescription : null,
                ':priority_id' => $taskPriorityId ?: 2,
                ':status_id' => $taskStatusId ?: 1,
                ':deadline' => $taskDeadline,
            ]);
            $taskId = (int)$db->lastInsertId();
            if (!empty($_FILES['attachment']) && isset($_FILES['attachment']['error']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                // Validate file upload
                if (!is_uploaded_file($_FILES['attachment']['tmp_name'])) {
                    throw new Exception('Invalid file upload.');
                }
                $maxFileSize = 10 * 1024 * 1024;
                if ($_FILES['attachment']['size'] > $maxFileSize) {
                    throw new Exception('File size exceeds maximum allowed size of 10MB.');
                }
                $originalName = $_FILES['attachment']['name'];
                $fileSize = $_FILES['attachment']['size'];
                $mimeType = $_FILES['attachment']['type'];
                $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'];
                $allowedMimeTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/zip',
                    'application/x-rar-compressed'
                ];

                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions));
                }

                if (!in_array($mimeType, $allowedMimeTypes) && !empty($mimeType)) {
                    throw new Exception('Invalid file type detected.');
                }
                $uploadDir = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'uploads';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        throw new Exception('Failed to create upload directory.');
                    }
                }
                $safeName = 'att_' . $taskId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
                $realUploadDir = realpath($uploadDir);
                $realDestPath = realpath(dirname($destPath));
                if ($realDestPath !== $realUploadDir) {
                    throw new Exception('Invalid upload path.');
                }

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destPath)) {
                    if (!file_exists($destPath)) {
                        throw new Exception('File upload failed - file not found after upload.');
                    }
                    $relPath = 'uploads/' . $safeName;
                    $stmtAtt = $db->prepare('INSERT INTO attachments (task_id, file_path) VALUES (:task_id, :file_path)');
                    $stmtAtt->execute([
                        ':task_id' => $taskId,
                        ':file_path' => $relPath,
                    ]);
                } else {
                    throw new Exception('Failed to move uploaded file.');
                }
            } elseif (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds maximum size allowed by server.',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum size allowed by form.',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
                ];
                $errorCode = $_FILES['attachment']['error'];
                $errorMessage = isset($uploadErrors[$errorCode]) ? $uploadErrors[$errorCode] : 'Unknown upload error.';
                throw new Exception('File upload error: ' . $errorMessage);
            }
            if (!empty($taskComment)) {
                $stmtCom = $db->prepare('INSERT INTO comments (task_id, user_id, content) VALUES (:task_id, :user_id, :content)');
                $stmtCom->execute([
                    ':task_id' => $taskId,
                    ':user_id' => $_SESSION['user_id'] ?? null,
                    ':content' => $taskComment,
                ]);
            }
            if (!empty($reminderDatetime)) {
                $reminderDate = date('Y-m-d H:i:s', strtotime($reminderDatetime));
                $stmtRem = $db->prepare('INSERT INTO reminders (task_id, remind_at) VALUES (:task_id, :remind_at)');
                $stmtRem->execute([
                    ':task_id' => $taskId,
                    ':remind_at' => $reminderDate,
                ]);
            }
        }

        $db->commit();
        $_SESSION['flash_success'] = 'Project created successfully.';
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['flash_error'] = 'Failed to create project: ' . $e->getMessage();
    }

    header('Location: manage_projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_project'])) {
    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $name = isset($_POST['project_name']) ? trim($_POST['project_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $members = isset($_POST['members']) && is_array($_POST['members']) ? array_filter($_POST['members'], 'strlen') : [];
    $addMeAsManager = isset($_POST['add_me_manager']) ? (bool)$_POST['add_me_manager'] : false;
    $managerUserId = isset($_POST['manager_user_id']) && $_POST['manager_user_id'] !== '' ? (int)$_POST['manager_user_id'] : null;
    $createInitialTask = isset($_POST['create_initial_task']) ? (bool)$_POST['create_initial_task'] : false;
    $taskTitle = isset($_POST['task_title']) ? trim($_POST['task_title']) : '';
    $taskDescription = isset($_POST['task_description']) ? trim($_POST['task_description']) : null;
    $taskPriorityId = isset($_POST['priority_id']) && $_POST['priority_id'] !== '' ? (int)$_POST['priority_id'] : null;
    $taskStatusId = isset($_POST['status_id']) && $_POST['status_id'] !== '' ? (int)$_POST['status_id'] : null;
    $taskDeadline = isset($_POST['deadline']) ? $_POST['deadline'] : null;
    $reminderDatetime = isset($_POST['reminder_datetime']) ? trim($_POST['reminder_datetime']) : null;
    $taskComment = isset($_POST['task_comment']) ? trim($_POST['task_comment']) : null;

    if ($projectId <= 0) {
        $_SESSION['flash_error'] = 'Invalid project ID.';
        header('Location: manage_projects.php');
        exit;
    }
    $stmtCheckProject = $db->prepare('SELECT project_id FROM projects WHERE project_id = ?');
    $stmtCheckProject->execute([$projectId]);
    if (!$stmtCheckProject->fetch()) {
        $_SESSION['flash_error'] = 'Project not found.';
        header('Location: manage_projects.php');
        exit;
    }

    if (empty($name)) {
        $_SESSION['flash_error'] = 'Project name is required.';
        header('Location: manage_projects.php');
        exit;
    }
    if (!$addMeAsManager && $managerUserId) {
        $stmtCheckManager = $db->prepare('SELECT user_id FROM users WHERE user_id = ? AND role IN ("admin", "manager")');
        $stmtCheckManager->execute([$managerUserId]);
        if (!$stmtCheckManager->fetch()) {
            $_SESSION['flash_error'] = 'Invalid manager selected.';
            header('Location: manage_projects.php');
            exit;
        }
    }
    if (!empty($members)) {
        $placeholders = str_repeat('?,', count($members) - 1) . '?';
        $stmtCheckMembers = $db->prepare("SELECT COUNT(*) FROM users WHERE user_id IN ($placeholders)");
        $stmtCheckMembers->execute($members);
        if ($stmtCheckMembers->fetchColumn() != count($members)) {
            $_SESSION['flash_error'] = 'One or more selected members are invalid.';
            header('Location: manage_projects.php');
            exit;
        }
    }
    if ($createInitialTask) {
        if (empty($taskTitle) || empty($taskDeadline)) {
            $_SESSION['flash_error'] = 'Task title and deadline are required when creating an initial task.';
            header('Location: manage_projects.php');
            exit;
        }
        if ($taskPriorityId) {
            $stmtCheckPriority = $db->prepare('SELECT priority_id FROM priorities WHERE priority_id = ?');
            $stmtCheckPriority->execute([$taskPriorityId]);
            if (!$stmtCheckPriority->fetch()) {
                $_SESSION['flash_error'] = 'Invalid priority selected.';
                header('Location: manage_projects.php');
                exit;
            }
        }

        if ($taskStatusId) {
            $stmtCheckStatus = $db->prepare('SELECT status_id FROM statuses WHERE status_id = ?');
            $stmtCheckStatus->execute([$taskStatusId]);
            if (!$stmtCheckStatus->fetch()) {
                $_SESSION['flash_error'] = 'Invalid status selected.';
                header('Location: manage_projects.php');
                exit;
            }
        }
    }

    try {
        $db->beginTransaction();
        $stmtCheckName = $db->prepare('SELECT project_id FROM projects WHERE project_name = ? AND project_id != ?');
        $stmtCheckName->execute([$name, $projectId]);
        if ($stmtCheckName->fetch()) {
            throw new Exception('A project with this name already exists.');
        }
        $stmt = $db->prepare('UPDATE projects SET project_name = :name, description = :description WHERE project_id = :id');
        $stmt->execute([
            ':name' => $name,
            ':description' => $description !== '' ? $description : null,
            ':id' => $projectId,
        ]);
        $db->prepare('DELETE FROM project_members WHERE project_id = :pid')->execute([':pid' => $projectId]);
        $managerToAssign = null;
        if ($addMeAsManager && !empty($_SESSION['user_id'])) {
            $managerToAssign = (int)$_SESSION['user_id'];
        } elseif (!$addMeAsManager && $managerUserId) {
            $managerToAssign = $managerUserId;
        }
        if (!empty($members) || $managerToAssign) {
            $stmtPM = $db->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (:pid, :uid, :role)');
            if ($managerToAssign) {
                $stmtPM->execute([
                    ':pid' => $projectId,
                    ':uid' => $managerToAssign,
                    ':role' => 'manager',
                ]);
            }
            if (!empty($members)) {
                foreach ($members as $uid) {
                    $uid = (int)$uid;
                    if ($uid <= 0) continue;
                    if ($managerToAssign && $uid === $managerToAssign) {
                        continue;
                    }

                    $stmtPM->execute([
                        ':pid' => $projectId,
                        ':uid' => $uid,
                        ':role' => 'contributor',
                    ]);
                }
            }
        }
        if ($createInitialTask) {
            $assignedTo = $managerToAssign ?: null;
            $stmtTask = $db->prepare('INSERT INTO tasks (project_id, assigned_to, title, description, priority_id, status_id, deadline) VALUES (:project_id, :assigned_to, :title, :description, :priority_id, :status_id, :deadline)');
            $stmtTask->execute([
                ':project_id' => $projectId,
                ':assigned_to' => $assignedTo,
                ':title' => $taskTitle,
                ':description' => $taskDescription !== '' ? $taskDescription : null,
                ':priority_id' => $taskPriorityId ?: 2,
                ':status_id' => $taskStatusId ?: 1,
                ':deadline' => $taskDeadline,
            ]);
            $taskId = (int)$db->lastInsertId();

            if (!empty($_FILES['attachment']) && isset($_FILES['attachment']['error']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                if (!is_uploaded_file($_FILES['attachment']['tmp_name'])) {
                    throw new Exception('Invalid file upload.');
                }
                $maxFileSize = 10 * 1024 * 1024;
                if ($_FILES['attachment']['size'] > $maxFileSize) {
                    throw new Exception('File size exceeds maximum allowed size of 10MB.');
                }
                $originalName = $_FILES['attachment']['name'];
                $fileSize = $_FILES['attachment']['size'];
                $mimeType = $_FILES['attachment']['type'];
                $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'];
                $allowedMimeTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/zip',
                    'application/x-rar-compressed'
                ];

                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions));
                }

                if (!in_array($mimeType, $allowedMimeTypes) && !empty($mimeType)) {
                    throw new Exception('Invalid file type detected.');
                }
                $uploadDir = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'uploads';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        throw new Exception('Failed to create upload directory.');
                    }
                }
                $safeName = 'att_' . $taskId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
                $realUploadDir = realpath($uploadDir);
                $realDestPath = realpath(dirname($destPath));
                if ($realDestPath !== $realUploadDir) {
                    throw new Exception('Invalid upload path.');
                }

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destPath)) {
                    if (!file_exists($destPath)) {
                        throw new Exception('File upload failed - file not found after upload.');
                    }
                    $relPath = 'uploads/' . $safeName;
                    $stmtAtt = $db->prepare('INSERT INTO attachments (task_id, file_path) VALUES (:task_id, :file_path)');
                    $stmtAtt->execute([':task_id' => $taskId, ':file_path' => $relPath]);
                } else {
                    throw new Exception('Failed to move uploaded file.');
                }
            } elseif (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds maximum size allowed by server.',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum size allowed by form.',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
                ];
                $errorCode = $_FILES['attachment']['error'];
                $errorMessage = isset($uploadErrors[$errorCode]) ? $uploadErrors[$errorCode] : 'Unknown upload error.';
                throw new Exception('File upload error: ' . $errorMessage);
            }

            if (!empty($taskComment)) {
                $stmtCom = $db->prepare('INSERT INTO comments (task_id, user_id, content) VALUES (:task_id, :user_id, :content)');
                $stmtCom->execute([':task_id' => $taskId, ':user_id' => $_SESSION['user_id'] ?? null, ':content' => $taskComment]);
            }
            if (!empty($reminderDatetime)) {
                $reminderDate = date('Y-m-d H:i:s', strtotime($reminderDatetime));
                $stmtRem = $db->prepare('INSERT INTO reminders (task_id, remind_at) VALUES (:task_id, :remind_at)');
                $stmtRem->execute([
                    ':task_id' => $taskId,
                    ':remind_at' => $reminderDate,
                ]);
            }
        }

        $db->commit();
        $_SESSION['flash_success'] = 'Project updated successfully.';
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['flash_error'] = 'Failed to update project: ' . $e->getMessage();
    }

    header('Location: manage_projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $pid = (int)$_POST['delete_project'];

    if ($pid <= 0) {
        $_SESSION['flash_error'] = 'Invalid project ID.';
        header('Location: manage_projects.php');
        exit;
    }
    $stmtCheckProject = $db->prepare('SELECT project_id FROM projects WHERE project_id = ?');
    $stmtCheckProject->execute([$pid]);
    if (!$stmtCheckProject->fetch()) {
        $_SESSION['flash_error'] = 'Project not found.';
        header('Location: manage_projects.php');
        exit;
    }

    try {
        $db->beginTransaction();
        $stmtDelAttachments = $db->prepare('DELETE FROM attachments WHERE task_id IN (SELECT task_id FROM tasks WHERE project_id = ?)');
        $stmtDelAttachments->execute([$pid]);

        $stmtDelComments = $db->prepare('DELETE FROM comments WHERE task_id IN (SELECT task_id FROM tasks WHERE project_id = ?)');
        $stmtDelComments->execute([$pid]);

        $stmtDelReminders = $db->prepare('DELETE FROM reminders WHERE task_id IN (SELECT task_id FROM tasks WHERE project_id = ?)');
        $stmtDelReminders->execute([$pid]);

        $stmtDelTasks = $db->prepare('DELETE FROM tasks WHERE project_id = ?');
        $stmtDelTasks->execute([$pid]);

        $stmtDelMembers = $db->prepare('DELETE FROM project_members WHERE project_id = ?');
        $stmtDelMembers->execute([$pid]);

        $stmt = $db->prepare('DELETE FROM projects WHERE project_id = ?');
        $stmt->execute([$pid]);

        $db->commit();
        $_SESSION['flash_success'] = 'Project deleted successfully.';
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $_SESSION['flash_error'] = 'Failed to delete project: ' . $e->getMessage();
    }

    header('Location: manage_projects.php');
    exit;
}

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($perPage, [5,10,20,50], true)) { $perPage = 10; }
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q !== '') {
    $totalStmt = $db->prepare('SELECT COUNT(DISTINCT p.project_id) FROM projects p LEFT JOIN users u ON p.created_by = u.user_id WHERE p.project_name LIKE :q1 OR p.description LIKE :q2 OR u.full_name LIKE :q3');
    $totalStmt->execute([':q1' => "%$q%", ':q2' => "%$q%", ':q3' => "%$q%"]);
    $totalProjects = (int)$totalStmt->fetchColumn();
    $stmt = $db->prepare('SELECT p.project_id, p.project_name, p.description, MIN(t.deadline) AS deadline, u.full_name AS owner, COUNT(DISTINCT pm.user_id) AS member_count FROM projects p LEFT JOIN users u ON p.created_by = u.user_id LEFT JOIN project_members pm ON pm.project_id = p.project_id LEFT JOIN tasks t ON t.project_id = p.project_id WHERE p.project_name LIKE :q1 OR p.description LIKE :q2 OR u.full_name LIKE :q3 GROUP BY p.project_id, p.project_name, p.description, u.full_name ORDER BY (deadline IS NULL), deadline ASC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':q1', "%$q%", PDO::PARAM_STR);
    $stmt->bindValue(':q2', "%$q%", PDO::PARAM_STR);
    $stmt->bindValue(':q3', "%$q%", PDO::PARAM_STR);
} else {
    $totalStmt = $db->query('SELECT COUNT(*) FROM projects');
    $totalProjects = (int)$totalStmt->fetchColumn();
    $stmt = $db->prepare('SELECT p.project_id, p.project_name, p.description, MIN(t.deadline) AS deadline, u.full_name AS owner, COUNT(DISTINCT pm.user_id) AS member_count FROM projects p LEFT JOIN users u ON p.created_by = u.user_id LEFT JOIN project_members pm ON pm.project_id = p.project_id LEFT JOIN tasks t ON t.project_id = p.project_id GROUP BY p.project_id, p.project_name, p.description, u.full_name ORDER BY (deadline IS NULL), deadline ASC LIMIT :limit OFFSET :offset');
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$overdueByProject = [];
$completedStatusId = $db->query("SELECT status_id FROM statuses WHERE name='completed'")->fetchColumn();
$overdueStatusId = $db->query("SELECT status_id FROM statuses WHERE name='overdue'")->fetchColumn();
if (!empty($projects)) {
    $ids = array_column($projects, 'project_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $parts = [];
    $sql1 = "SELECT project_id, MIN(deadline) AS earliest_overdue FROM tasks WHERE project_id IN ($placeholders) AND deadline < CURDATE()";
    if ($completedStatusId !== false && $completedStatusId !== null) {
        $sql1 .= " AND status_id <> ?";
    }
    $sql1 .= " GROUP BY project_id";
    $parts[] = $sql1;
    if ($overdueStatusId !== false && $overdueStatusId !== null) {
        $sql2 = "SELECT project_id, MIN(deadline) AS earliest_overdue FROM tasks WHERE project_id IN ($placeholders) AND status_id = ? GROUP BY project_id";
        $parts[] = $sql2;
    }
    $sqlOverdue = implode(' UNION ', $parts);
    $ovStmt = $db->prepare($sqlOverdue);
    $bindIndex = 1;
    foreach ($ids as $pid) { $ovStmt->bindValue($bindIndex++, (int)$pid, PDO::PARAM_INT); }
    if ($completedStatusId !== false && $completedStatusId !== null) {
        $ovStmt->bindValue($bindIndex++, (int)$completedStatusId, PDO::PARAM_INT);
    } 
    if ($overdueStatusId !== false && $overdueStatusId !== null) {
        foreach ($ids as $pid) { $ovStmt->bindValue($bindIndex++, (int)$pid, PDO::PARAM_INT); }
        $ovStmt->bindValue($bindIndex++, (int)$overdueStatusId, PDO::PARAM_INT);
    }
    $ovStmt->execute();
    while ($row = $ovStmt->fetch(PDO::FETCH_ASSOC)) {
        $pid = (int)$row['project_id'];
        $earliest = $row['earliest_overdue'];
        if (!isset($overdueByProject[$pid]) || ($earliest !== null && $earliest < $overdueByProject[$pid]['earliest_overdue'])) {
            $overdueByProject[$pid] = ['earliest_overdue' => $earliest];
        }
    }
}

$inProgressStatusId = $db->query("SELECT status_id FROM statuses WHERE name='in_progress'")->fetchColumn();
$pendingStatusId = $db->query("SELECT status_id FROM statuses WHERE name='pending'")->fetchColumn();

$projectIdsOnPage = !empty($projects) ? array_map('intval', array_column($projects, 'project_id')) : [];
$inClause = !empty($projectIdsOnPage) ? implode(',', array_fill(0, count($projectIdsOnPage), '?')) : '';

$overdueSet = [];
foreach ($overdueByProject as $pid => $_) { $overdueSet[(int)$pid] = true; }

$inProgressSet = [];
if ($inClause && $inProgressStatusId !== false && $inProgressStatusId !== null) {
    $sqlInProgress = "SELECT DISTINCT project_id FROM tasks WHERE project_id IN ($inClause) AND status_id = ?";
    $st = $db->prepare($sqlInProgress);
    $i = 1; foreach ($projectIdsOnPage as $pid) { $st->bindValue($i++, (int)$pid, PDO::PARAM_INT); }
    $st->bindValue($i++, (int)$inProgressStatusId, PDO::PARAM_INT);
    $st->execute();
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $pid) { $pid=(int)$pid; if (!isset($overdueSet[$pid])) $inProgressSet[$pid] = true; }
}

$pendingSet = [];
if ($inClause && $pendingStatusId !== false && $pendingStatusId !== null) {
    $sqlPending = "SELECT DISTINCT project_id FROM tasks WHERE project_id IN ($inClause) AND status_id = ?";
    $st = $db->prepare($sqlPending);
    $i = 1; foreach ($projectIdsOnPage as $pid) { $st->bindValue($i++, (int)$pid, PDO::PARAM_INT); }
    $st->bindValue($i++, (int)$pendingStatusId, PDO::PARAM_INT);
    $st->execute();
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $pid) { $pid=(int)$pid; if (!isset($overdueSet[$pid]) && !isset($inProgressSet[$pid])) $pendingSet[$pid] = true; }
}

$completedSet = [];
if ($inClause && $completedStatusId !== false && $completedStatusId !== null) {
    $sqlCompleted = "SELECT p.project_id FROM projects p JOIN tasks t ON t.project_id = p.project_id WHERE p.project_id IN ($inClause) GROUP BY p.project_id HAVING SUM(CASE WHEN t.status_id <> ? THEN 1 ELSE 0 END) = 0 AND COUNT(*) > 0";
    $st = $db->prepare($sqlCompleted);
    $i = 1; foreach ($projectIdsOnPage as $pid) { $st->bindValue($i++, (int)$pid, PDO::PARAM_INT); }
    $st->bindValue($i++, (int)$completedStatusId, PDO::PARAM_INT);
    $st->execute();
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $pid) { $pid=(int)$pid; if (!isset($overdueSet[$pid]) && !isset($inProgressSet[$pid]) && !isset($pendingSet[$pid])) $completedSet[$pid] = true; }
}

if ($inClause) {
    $sqlHasTasks = "SELECT DISTINCT project_id FROM tasks WHERE project_id IN ($inClause)";
    $st = $db->prepare($sqlHasTasks);
    $i = 1; foreach ($projectIdsOnPage as $pid) { $st->bindValue($i++, (int)$pid, PDO::PARAM_INT); }
    $st->execute();
    $hasTasks = array_flip(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));
    foreach ($projectIdsOnPage as $pid) {
        if (!isset($hasTasks[$pid]) && !isset($overdueSet[$pid]) && !isset($inProgressSet[$pid]) && !isset($completedSet[$pid])) {
            $pendingSet[$pid] = true;
        }
    }
}

$pages = (int)ceil($totalProjects / max(1,$perPage));
$baseQuery = http_build_query(['q' => $q, 'per_page' => $perPage]);

$usersStmt = $db->query('SELECT user_id, full_name, role FROM users ORDER BY full_name ASC');
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
$userNames = [];
foreach ($allUsers as $u) { $userNames[(int)$u['user_id']] = $u['full_name']; }

$projectMembersByProject = [];
$creatorManagerByProject = [];
if (!empty($projects)) {
    $ids = array_column($projects, 'project_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pmStmt = $db->prepare("SELECT project_id, user_id, role FROM project_members WHERE project_id IN ($placeholders)");
    $pmStmt->execute($ids);
    while ($row = $pmStmt->fetch(PDO::FETCH_ASSOC)) {
        $pid = (int)$row['project_id'];
        $uid = (int)$row['user_id'];
        $role = $row['role'];
        if (!isset($projectMembersByProject[$pid])) { $projectMembersByProject[$pid] = []; }
        $projectMembersByProject[$pid][] = $uid;
        if (!empty($_SESSION['user_id']) && $uid === (int)$_SESSION['user_id'] && $role === 'manager') {
            $creatorManagerByProject[$pid] = true;
        }
    }
}

$memberNamesByProject = [];
foreach ($projectMembersByProject as $pid => $uids) {
    $names = [];
    foreach ($uids as $uid) {
        if (isset($userNames[$uid])) { $names[] = $userNames[$uid]; }
    }
    $memberNamesByProject[$pid] = $names;
}

$managersStmt = $db->prepare("SELECT user_id, full_name FROM users WHERE role = 'manager' ORDER BY full_name ASC");
$managersStmt->execute();
$managers = $managersStmt->fetchAll(PDO::FETCH_ASSOC);

$priorities = $db->query('SELECT priority_id, name FROM priorities ORDER BY priority_id ASC')->fetchAll(PDO::FETCH_ASSOC);
$statuses = $db->query('SELECT status_id, name FROM statuses ORDER BY status_id ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Projects</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="inc/sidebar.css">
  <link rel="stylesheet" href="css/manage_projects.css">
</head>
<body>
  <div class="d-flex">
    <?php $active='projects'; include __DIR__ . '/inc/sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
      <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
          <h3 class="m-0 page-title">Manage Projects</h3>
          <div class="d-flex align-items-center gap-2">
            <button id="btnAddProject" class="btn btn-sm">Add Project</button>
            <form method="get" class="d-flex align-items-center gap-2 m-0">
              <input type="hidden" name="per_page" value="<?php echo (int)$perPage; ?>">
              <input type="text" class="form-control form-control-sm search-input" name="q" placeholder="Search projects..." value="<?php echo htmlspecialchars($q); ?>">
              <button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
            </form>
          </div>
        </div>

        <?php  ?>

        <div class="card shadow-sm">
          <div class="card-body">
            <table class="table table-hover table-striped align-middle">
              <thead class="table-light"><tr><th>Project</th><th>Owner</th><th>Members</th><th>Deadline</th><th>Actions</th></tr></thead>
              <tbody>
                <?php if (empty($projects)): ?>
                  <tr><td colspan="5">No projects found.</td></tr>
                <?php else: foreach ($projects as $p): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold project-name"><?php echo htmlspecialchars($p['project_name']); ?></div>
                      <?php if (!empty($p['description'])): ?>
                        <div class="text-muted small"><?php echo htmlspecialchars($p['description']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php 
                        $ownerName = $p['owner'] ?? null; 
                        $ownerCount = $ownerName ? 1 : 0; 
                        $ownerTooltip = $ownerName ? htmlspecialchars($ownerName) : 'Unassigned';
                      ?>
                      <span class="text-muted tooltip-cursor" data-bs-toggle="tooltip" title="<?php echo $ownerTooltip; ?>"><?php echo $ownerName ? htmlspecialchars($ownerName) : 'Unassigned'; ?></span>
                    </td>
                    <td>
                      <?php $names = $memberNamesByProject[(int)$p['project_id']] ?? []; $count = (int)($p['member_count'] ?? 0); ?>
                      <span class="text-dark tooltip-cursor" data-bs-toggle="tooltip" data-bs-html="true" title="<?php echo htmlspecialchars(implode('<br>', $names)); ?>"><?php echo $count; ?> member<?php echo $count != 1 ? 's' : ''; ?></span>
                    </td>
                    <td>
                      <?php 
                        $pid = (int)$p['project_id'];
                        $dl = $p['deadline'] ?? null; 
                        if (isset($overdueByProject[$pid])) {
                            $earliest = $overdueByProject[$pid]['earliest_overdue'];
                            echo '<span class="text-danger fw-medium">' . htmlspecialchars($earliest) . ' (Overdue)</span>';
                        } else {
                            echo $dl ? '<span class="text-dark">' . htmlspecialchars($dl) . '</span>' : '<span class="text-muted">No deadline</span>';
                        }
                      ?>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-outline-primary btnEditProject" data-pid="<?php echo $p['project_id']; ?>">Edit</button>
                      <form method="post" class="deleteProjectForm delete-project-form">
                        <input type="hidden" name="delete_project" value="<?php echo $p['project_id']; ?>">
                        <button class="btn btn-sm btn-outline-danger btnDeleteProject" type="button">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>

            <div class="d-flex justify-content-between align-items-center">
              <form method="get" class="d-flex align-items-center gap-2 m-0">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
                <label class="small text-muted">Show</label>
                <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                  <?php foreach ([5,10,20,50] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo $perPage==$opt?'selected':''; ?>><?php echo $opt; ?></option>
                  <?php endforeach; ?>
                </select>
                <label class="small text-muted">per page</label>
              </form>
              <nav>
                <ul class="pagination mb-0">
                  <?php for ($i=1; $i<=$pages; $i++): ?>
                    <li class="page-item <?php if($i==$page) echo 'active'; ?>">
                      <a class="page-link" href="?<?php echo $baseQuery; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                  <?php endfor; ?>
                </ul>
              </nav>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden stand-in forms for SweetAlert2 submission (Add/Edit) -->
  <form id="addProjectForm" method="post" enctype="multipart/form-data" class="hidden-form">
    <input type="hidden" name="add_project" value="1">
  </form>
  <form id="editProjectForm" method="post" enctype="multipart/form-data" class="hidden-form">
    <input type="hidden" name="edit_project" value="1">
  </form>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="inc/sidebar.js" defer></script>
  <script src="js/manage_projects.js" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const allUsers = <?php echo json_encode($allUsers, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
      const managers = <?php echo json_encode($managers, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
      const priorities = <?php echo json_encode($priorities, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
      const statuses = <?php echo json_encode($statuses, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
      const projects = <?php echo json_encode(array_map(function($p) use ($projectMembersByProject, $creatorManagerByProject){
        $pid = (int)$p['project_id'];
        return [
          'project_id'=>$pid,
          'project_name'=>$p['project_name'],
          'description'=>$p['description'],
          'members'=> $projectMembersByProject[$pid] ?? [],
          'is_creator_manager' => $creatorManagerByProject[$pid] ?? false,
        ];
      }, $projects), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
      
      setServerData(allUsers, managers, priorities, statuses, projects);
      
      const successMsg = <?php echo json_encode($_SESSION['flash_success'] ?? null); unset($_SESSION['flash_success']); ?>;
      const errorMsg = <?php echo json_encode($_SESSION['flash_error'] ?? null); unset($_SESSION['flash_error']); ?>;
      showFlashMessages(successMsg, errorMsg);
    });

  </script>
</body>
</html>
