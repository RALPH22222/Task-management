<?php
session_start();
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, full_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['role'] === 'manager') {
                header("Location: ../manager/dashboard.php");
            } elseif ($user['role'] === 'employee') {
                header("Location: ../employee/assign-task.php");
            } else {
                header("Location: ../admin/dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Task Management System</title>
  <link rel="stylesheet" href="css/login.css" />
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <img src="../logo.png" alt="University Logo" />
        <h2>Task Management System</h2>
        <p>Sign in to your account</p>
      </div>
      
      <?php if(isset($error)): ?>
        <div class="alert error">
          <i class="fas fa-exclamation-circle"></i>
          <span><?php echo $error; ?></span>
        </div>
      <?php endif; ?>
      
      <form class="auth-form" method="POST" action="">
        <div class="input-group">
          <label for="email">Email Address</label>
          <div class="input-with-icon">
            <i class="fas fa-envelope"></i>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
          </div>
        </div>
        
        <div class="input-group">
          <label for="password">Password</label>
          <div class="input-with-icon">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>
        </div>
        
        <button type="submit" class="btn primary-btn">Sign In</button>
      </form>
      
      <div class="auth-footer">
        <p>Don't have an account? <a href="register.php">Sign up here</a></p>
      </div>
    </div>
  </div>
</body>
</html>