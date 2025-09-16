<?php
session_start();
require_once '../config/database.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if(empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "Please fill in all fields";
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->rowCount() > 0) {
        $errors[] = "Email address is already registered";
    }
    
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'employee')");
        if($stmt->execute([$full_name, $email, $hashed_password])) {
            $success = "Registration successful! You can now login.";
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Task Management System</title>
  <link rel="stylesheet" href="css/register.css" />
  <link rel="icon" type="image/x-icon" href="../favicon.ico"> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <img src="../logo.png" alt="University Logo" />
        <h2>Task Management System</h2>
        <p>Create a new account</p>
      </div>
      
      <?php if(!empty($errors)): ?>
        <div class="alert error">
          <i class="fas fa-exclamation-circle"></i>
          <div>
            <?php foreach($errors as $error): ?>
              <div><?php echo $error; ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      
      <?php if(isset($success)): ?>
        <div class="alert success">
          <i class="fas fa-check-circle"></i>
          <span><?php echo $success; ?></span>
        </div>
      <?php endif; ?>
      
      <form class="auth-form" method="POST" action="">
        <div class="input-group">
          <label for="full_name">Full Name</label>
          <div class="input-with-icon">
            <i class="fas fa-user"></i>
            <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
          </div>
        </div>
        
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
            <input type="password" id="password" name="password" placeholder="Create a password" required>
          </div>
          <div class="input-help">Must be at least 6 characters</div>
        </div>
        
        <div class="input-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-with-icon">
            <i class="fas fa-lock"></i>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
          </div>
        </div>
        
        <button type="submit" class="btn primary-btn">Create Account</button>
      </form>
      
      <div class="auth-footer">
        <p>Already have an account? <a href="login.php">Sign in here</a></p>
      </div>
    </div>
  </div>
</body>
</html>