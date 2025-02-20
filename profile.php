<?php
session_start(); // Start the session

include 'dbcon.php';

$message = ''; // Initialize the message variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch and sanitize POST data
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $message = 'Please provide both username and password.';
    } else {
        try {
            

            // Prepare the SQL query
            $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);

            // Fetch the user record
            $user = $stmt->fetch();

            if ($user) {
                // Verify the password
                if ($password === $user['password']) { // Replace with `password_verify()` for hashed passwords
                    if ($user['role'] === 'admin') {
                        // Valid admin - set session and redirect
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        session_regenerate_id(true); // Regenerate session ID for security
                        header('Location: target.php'); // Redirect to admin dashboard
                        exit;
                    } else {
                        $message = 'Access denied. You are not an admin.';
                    }
                } else {
                    $message = 'Invalid password. Please try again.';
                }
            } else {
                $message = 'Invalid username. Please try again.';
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <style>
    h1 {
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      margin: 0;
      padding: 20px;
      background-color: #ffffffcc;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f9;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background-image: url('Indian-Army-T-72.jpg');
      background-size: cover;
    }

    .login-container {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      width: 400px;
    }

    .login-container h2 {
      text-align: center;
      color: #333;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .form-group input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .btn {
      display: block;
      text-align: center;
      margin: auto;
      margin-top: 20px;
      padding: 10px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }

    .btn:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>
<h1>ProdReport Hub</h1>
  <div class="login-container">
    <h2>Admin Login</h2>
    <form method="POST">
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder="Enter your username" required>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>

    <?php if ($message): ?>
      <div style="text-align: center; color: red; margin-top: 10px;">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>

