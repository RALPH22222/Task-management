<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
require_once __DIR__ . '/../config/database.php';

$db = isset($pdo) ? $pdo : (class_exists('Database') ? (new Database())->getConnection() : null);
if (!$db) { die('Database connection required.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'employee';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($full_name === '' || $email === '' || $password === '') {
        $_SESSION['flash_error'] = 'Full name, email and password are required.';
        header('Location: manage_users.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_error'] = 'Invalid email address.';
        header('Location: manage_users.php');
        exit;
    }

    try {
        $chk = $db->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $chk->execute([':email' => $email]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new Exception('Email already exists.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)');
        $stmt->execute([':full_name' => $full_name, ':email' => $email, ':password' => $hash, ':role' => $role]);
        $_SESSION['flash_success'] = 'User added successfully.';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = 'Failed to add user: ' . $e->getMessage();
    }
    header('Location: manage_users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'employee';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($user_id <= 0 || $full_name === '' || $email === '') {
        $_SESSION['flash_error'] = 'Invalid input.';
        header('Location: manage_users.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_error'] = 'Invalid email address.';
        header('Location: manage_users.php');
        exit;
    }
    try {
        $chk = $db->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND user_id <> :uid');
        $chk->execute([':email' => $email, ':uid' => $user_id]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new Exception('Email already exists.');
        }
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET full_name = :full_name, email = :email, role = :role, password = :password WHERE user_id = :id');
            $stmt->execute([':full_name' => $full_name, ':email' => $email, ':role' => $role, ':password' => $hash, ':id' => $user_id]);
        } else {
            $stmt = $db->prepare('UPDATE users SET full_name = :full_name, email = :email, role = :role WHERE user_id = :id');
            $stmt->execute([':full_name' => $full_name, ':email' => $email, ':role' => $role, ':id' => $user_id]);
        }
        $_SESSION['flash_success'] = 'User updated successfully.';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = 'Failed to update user: ' . $e->getMessage();
    }
    header('Location: manage_users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['delete_user'];
    $stmt = $db->prepare('DELETE FROM users WHERE user_id = :id');
    $stmt->execute([':id' => $uid]);
    header('Location: manage_users.php');
    exit;
}

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($perPage, [5,10,20,50], true)) { $perPage = 10; }
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q !== '') {
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role <> 'admin' AND (full_name LIKE :q OR email LIKE :q OR role LIKE :q)");
    $totalStmt->execute([':q' => "%$q%"]);
    $totalUsers = (int)$totalStmt->fetchColumn();
    $stmt = $db->prepare("SELECT user_id, full_name, email, role, created_at FROM users WHERE role <> 'admin' AND (full_name LIKE :q1 OR email LIKE :q2 OR role LIKE :q3) ORDER BY user_id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':q1', "%$q%", PDO::PARAM_STR);
    $stmt->bindValue(':q2', "%$q%", PDO::PARAM_STR);
    $stmt->bindValue(':q3', "%$q%", PDO::PARAM_STR);
} else {
    $totalStmt = $db->query("SELECT COUNT(*) FROM users WHERE role <> 'admin'");
    $totalUsers = (int)$totalStmt->fetchColumn();
    $stmt = $db->prepare("SELECT user_id, full_name, email, role, created_at FROM users WHERE role <> 'admin' ORDER BY user_id DESC LIMIT :limit OFFSET :offset");
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pages = (int)ceil($totalUsers / max(1,$perPage));
$baseQuery = http_build_query(['q' => $q, 'per_page' => $perPage]);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Manage Users</title>
	<link rel="icon" type="image/x-icon" href="../favicon.ico">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="css/dashboard.css">
	<link rel="stylesheet" href="inc/sidebar.css">
	<link rel="stylesheet" href="css/manage_users.css">
</head>
<body>
	<div class="d-flex">
		<?php $active='users'; include __DIR__ . '/inc/sidebar.php'; ?>

		<div class="flex-grow-1 p-4">
			<div class="container-fluid">
				<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
					<h3 class="m-0 page-title">Manage Users</h3>
					<div class="d-flex align-items-center gap-2">
						<button id="btnAddUser" class="btn btn-sm" style="background:var(--primary);color:#fff">Add User</button>
						<form method="get" class="d-flex align-items-center gap-2 m-0">
							<input type="hidden" name="per_page" value="<?php echo (int)$perPage; ?>">
							<input type="text" class="form-control form-control-sm" name="q" placeholder="Search users..." value="<?php echo htmlspecialchars($q); ?>" style="min-width:240px">
							<button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
						</form>
					</div>
				</div>

				<div class="card shadow-sm">
					<div class="card-body">
						<table class="table table-hover table-striped align-middle">
							<thead class="table-light"><tr><th>Full name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
							<tbody>
								<?php if (empty($users)): ?>
									<tr><td colspan="6">No users found.</td></tr>
								<?php else: foreach ($users as $u): ?>
									<tr>
										<td><?php echo htmlspecialchars($u['full_name']); ?></td>
										<td><?php echo htmlspecialchars($u['email']); ?></td>
										<td><?php echo htmlspecialchars($u['role']); ?></td>
										<td><?php echo htmlspecialchars($u['created_at']); ?></td>
										<td>
											<button class="btn btn-sm btn-outline-primary btnEditUser" data-uid="<?php echo (int)$u['user_id']; ?>" data-fullname="<?php echo htmlspecialchars($u['full_name']); ?>" data-email="<?php echo htmlspecialchars($u['email']); ?>" data-role="<?php echo htmlspecialchars($u['role']); ?>">Edit</button>
											<form method="post" style="display:inline-block" class="deleteUserForm">
												<input type="hidden" name="delete_user" value="<?php echo (int)$u['user_id']; ?>">
												<button class="btn btn-sm btn-outline-danger btnDeleteUser" type="button">Delete</button>
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
	<form id="addUserForm" method="post" style="display:none">
		<input type="hidden" name="add_user" value="1">
	</form>
	<form id="editUserForm" method="post" style="display:none">
		<input type="hidden" name="edit_user" value="1">
	</form>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="inc/sidebar.js" defer></script>
	<script src="js/manage_users.js" defer></script>
	<script>
	  const successMsg = <?php echo json_encode($_SESSION['flash_success'] ?? null); unset($_SESSION['flash_success']); ?>;
	  const errorMsg = <?php echo json_encode($_SESSION['flash_error'] ?? null); unset($_SESSION['flash_error']); ?>;
	  document.addEventListener('DOMContentLoaded', function() {
	    handleFlashMessages(successMsg, errorMsg);
	  });
	</script>
</body>
</html>
