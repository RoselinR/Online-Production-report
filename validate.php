<?php
session_start(); // Start the session

include 'dbcon.php';

$message = ''; // Initialize the message variable

// Function to connect to the database using PDO
include 'dbcon.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        

        $PerNo = trim($_POST['PerNo'] ?? '');
        $Name = trim($_POST['Name'] ?? '');

        // Validate input
        if (empty($PerNo) || empty($Name)) {
            $message = 'Invalid input. Please provide both Person No and Name.';
        } else {
            // Prepare and execute query using PDO
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM employeedetails WHERE PerNo = :PerNo AND Name = :Name");
            $stmt->bindParam(':PerNo', $PerNo, PDO::PARAM_STR);
            $stmt->bindParam(':Name', $Name, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();

            if ($row['count'] > 0) {
                $_SESSION['PerNo'] = $PerNo;
                $_SESSION['Name'] = $Name;
                session_regenerate_id(true);
                header('Location: Chargeman.php');
                exit;
            } else {
                $message = 'Invalid credentials. Please try again.';
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred: ' . $e->getMessage();
    }
}

// Handle AJAX request for user information
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['PerNo'])) {
    try {
        $PerNo = trim($_GET['PerNo']);
        

        $stmt = $conn->prepare("SELECT Name FROM employeedetails WHERE PerNo = :PerNo");
        $stmt->bindParam(':PerNo', $PerNo, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        if ($row) {
            echo json_encode(['status' => 'success', 'name' => $row['Name']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Person not found.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    }
    exit;
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page - Heavy Vehicle Factory</title>
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
      margin: 20px auto;
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

    .form-group select, .form-group input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .form-group input[readonly] {
      background-color: #e9ecef;
    }

    .btn {
      display: block;
      text-align: center;
      margin: auto;
      margin-top: 30px;
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
    <h2 align="center">Login Form</h2>
    <form id="loginForm" method="POST">
      <div class="form-group">
        <label for="section">Select Section:</label>
        <select id="section" name="section" required>
          <option value="">--Select--</option>
          <option value="TRG1">TRG1</option>
          <option value="TRG2">TRG2</option>
        </select>
      </div>
      <div class="form-group">
        <label for="PerNo">In-Charge Person No:</label>
        <input type="text" id="PerNo" name="PerNo" placeholder="Enter In-Charge No" required>
      </div>
      <div class="form-group">
        <label for="Name">In-Charge Name:</label>
        <input type="text" id="Name" name="Name" readonly>
      </div>
      <button type="submit" class="btn">Go</button>
    </form>

    
    <?php if ($message): ?>
      <div id="message" style="text-align: center; color: red; margin-top: 10px;">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    document.getElementById('PerNo').addEventListener('blur', function () {
      const PerNo = this.value.trim();
      if (PerNo) {
        fetch(window.location.href + '?PerNo=' + PerNo)
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              document.getElementById('Name').value = data.name;  // Autofill Name field
            } else {
              document.getElementById('Name').value = ''; // Clear the Name field if not found
              alert('Error: ' + data.message);
            }
          })
          .catch(error => console.error('Error:', error));
      }
    });
  </script>
</body>
</html>
