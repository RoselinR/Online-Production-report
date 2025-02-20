<?php
session_start();
include 'dbcon.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: profile.php");
    exit();
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: profile.php");
    exit();
}


if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    // Database connection (replace with your connection details)
    $host = 'localhost';
    $dbname = 'trg';
    $username = 'root';
    $password = '';
    $dsn = "mysql:host=$host;port=3377;dbname=$dbname";

    try {
        $conn = new PDO($dsn, $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get the start and end dates from the request
        $startDate = $_GET['start_date'];
        $endDate = $_GET['end_date'];

        // SQL query to get PerNo, Design, MachineNo, DrawingNo, ComponentsProduced, MachineDescription, ComponentDescription, and Variant
        $sql = "
            SELECT 
                p.PerNo, 
                e.Name,
                e.Design, 
                p.MachineNo, 
                p.DrawingNo, 
                SUM(p.ComponentsProduced) AS ComponentsProduced, 
                m.Description as MachineDescription, 
                c.Description as ComponentDescription, 
                c.Variant
            FROM productiondetails p
            LEFT JOIN machinedetails m ON p.MachineNo = m.HVFNo
            LEFT JOIN componentdetails c ON p.DrawingNo = c.DrawingNo
            LEFT JOIN employeedetails e ON p.PerNo = e.PerNo
            WHERE p.FillDate BETWEEN :startDate AND :endDate
            GROUP BY p.PerNo, p.MachineNo, p.DrawingNo
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->execute();

        // Fetch the results
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debug: output data to verify it's fetched
        if ($data) {
            echo json_encode($data); // Return data as JSON if available
        } else {
            echo json_encode(["error" => "No data found for the selected date range."]); // Handle no data case
        }
        exit;

    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]); // Return error in case of issues
        exit;
    }
}


// Fetch current user data
$current_user_id = "1"; // Replace with actual session variable
$stmt = $conn->prepare("SELECT username, password FROM users WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $current_username = $user['username'];
    $current_password = $user['password']; // Optional, handle with care
} else {
    echo "No user found with the provided ID.";
}

// Close the PDO connection
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <style>
        body {
            
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: #e8e8e8;
        }

        .sidebar {
            
            width: 220px;
            background-color: #727272;
            padding: 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar h1 {
            
            font-size: 24px;
            padding: 11.11% 0px;
            margin: 0;
    
            background-color: #727272;
            color: #fff;
            text-align: center;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
	    border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
       	    height:100px;
            
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #fff;
            font-size: 20px;
            display: block;
            padding: 40px 20px;
        }

        .sidebar ul li a:hover {
            background-color: #fab23e;
            color: #fff;
	    max-height:50px;

        }

        .main-content {
	    margin-top:-20px;
            flex-grow: 1;
            padding: 20px;
            background-color: #ffffff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
	    margin-left:-18px;
            background-color: #727272;
            padding: 26px 0px;
            border-radius: 5px;
        }

        .header h1 {
            
       	    padding-left:600px;
            font-size: 24px;
            margin: 0;
            color: #fff;
        }

        .header .profile {
            background-color: #cccccc;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        h2 {
            margin-top:-18px;
            margin-left:-18px;
            background-color: #fab23e;
            padding: 10px;
            padding-left:655px;
            border-radius: 5px;
            color: #fff;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .search-bar {
            display: flex;
            margin-bottom: 20px;
        }

        .search-bar input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-right: 10px;
        }

        .search-bar button {
            background-color: #ffcc00;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .search-bar button:hover {
            background-color: #ffa500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }

        table th {
            background-color: #f2f2f2;
        }
        .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
    justify-content: center;
    align-items: center;
    z-index: 1000; /* Ensure it's above other elements */
}

.modal-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    width: 400px;
    text-align: center;
    z-index: 1001; /* Higher than table header */
}


        .modal-content label {
            display: block;
            margin-bottom: 5px;
            text-align: left;
        }

        .modal-content input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-content .close {
            background-color: red;
            color: white;
        }

        .modal-content .submit {
            background-color: green;
            color: white;
        }

        .open-modal {
            margin: 10px;
            padding: 10px 20px;
            background-color: blue;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .table-container {
    width: 100%;
    max-height: 400px; /* Adjust the height as needed */
    overflow-x: auto; /* Horizontal scroll */
    overflow-y: auto; /* Vertical scroll */
    border: 1px solid #ccc; /* Optional: border for visibility */
}

table {
    width: 100%; /* Ensure table takes full width inside the container */
    border-collapse: collapse;
}

table th, table td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: left;
}

table th {
    background-color: #f2f2f2;
    position: sticky;
    top: 0;
    z-index: 1; /* Ensure it's below the modal */
}


    </style>
</head>
<body>
<script>
 function fetchData() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (startDate && endDate) {
                fetch(`?start_date=${startDate}&end_date=${endDate}`)
                    .then(response => response.json())
                    .then(data => {
                        const tableBody = document.getElementById('resultTable').getElementsByTagName('tbody')[0];
                        tableBody.innerHTML = '';  // Clear existing rows

                        // Initialize serial number counter
                        let sno = 1;

                        // Iterate through the fetched data and populate the table
                        data.forEach(item => {
                            const row = tableBody.insertRow();

                            // Insert Serial Number (SNo) in the first cell
                            row.insertCell(0).textContent = sno++;

                            // Insert data cells
                            row.insertCell(1).textContent = item.PerNo;
                            row.insertCell(2).textContent = item.Name;
                            row.insertCell(3).textContent = item.Design;
                            row.insertCell(4).textContent = item.MachineNo;
                            row.insertCell(5).textContent = item.MachineDescription;
                            row.insertCell(6).textContent = item.DrawingNo;
                            row.insertCell(7).textContent = item.ComponentDescription;
                            row.insertCell(8).textContent = item.Variant;
                            row.insertCell(9).textContent = item.ComponentsProduced;
                        });
                    })
                    .catch(error => {
                        console.error("Error fetching data:", error);
                        alert("Error fetching data. Please try again.");
                    });
            } else {
                alert("Please select both start and end dates.");
            }
        }

function filterTable() {
    const searchInput = document.getElementById("searchInput").value.toLowerCase();
    const table = document.querySelector("table tbody");
    const rows = table.getElementsByTagName("tr");

    for (const row of rows) {
        const cells = row.getElementsByTagName("td");
        let match = false;

        // Check each cell in the row
        for (const cell of cells) {
            if (cell.textContent.toLowerCase().includes(searchInput)) {
                match = true;
                break;
            }
        }

        // Show or hide the row based on the match
        row.style.display = match ? "" : "none";
    }
}
function openProfileModal() {
    // Populate the modal fields with current data
    const currentUsername = "<?php echo htmlspecialchars($current_username, ENT_QUOTES, 'UTF-8'); ?>";
    const currentPassword = "<?php echo htmlspecialchars($current_password, ENT_QUOTES, 'UTF-8'); ?>";
    document.getElementById('username').value = currentUsername; // Replace with dynamic value
    document.getElementById('password').value = currentPassword; // Replace with dynamic value

    document.getElementById('profileModal').style.display = 'flex';
}

function closeProfileModal() {
    document.getElementById('profileModal').style.display = 'none';
}
    </script>
    <div class="sidebar">
        <h1>Prodreport Hub</h1>
        <ul>
            <li><a href="/mainproject/target.php">Target</a></li>
            <li><a href="/mainproject/machine.php">Machine Data</a></li>
            <li><a href="/mainproject/employee.php">Employees Data</a></li>
            <li><a href="/mainproject/comp.php">Component Details</a></li>
            <li><a href="/mainproject/operation.php">Operation Details</a></li>
            <li><a href="/mainproject/production.php">Production Details</a></li>
            <li><a href="#">Report</a></li>
            <li><a href="#">Dashboard</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Transmission Shop (TRG 1 & 2)</h1>
            <div class="profile" style="cursor: pointer;" onclick="openProfileModal()">My Profile</div>
        </div>
        <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search for description" onkeyup="filterTable()"></div>
        <a href="reportmac.php"><button>Machine Report</button></a><a href="reportop.php"><button>Operator Report</button></a>
        <br><br>
        <h2>Machine Production Data Grouped by Operator</h2>

<!-- Dropdowns for Start and End Dates -->
<label for="startDate">Start Date:</label>
<input type="date" id="startDate" name="startDate">

<label for="endDate">End Date:</label>
<input type="date" id="endDate" name="endDate">

<button onclick="fetchData()">Fetch Data</button>

<!-- Table to Display Results -->
<table id="resultTable" border="1">
    <thead>
        <tr>
            <th>SNo</th>
            <th>OperatorNo</th>
            <th>Operator Name</th>
            <th>Operator Designation</th>
            <th>MachineNo</th>
            <th>MachineDescription</th>
            <th>DrawingNo</th>
            <th>ComponentDescription</th>
            <th>Variant</th>
            <th>ComponentsProduced</th>
        </tr>
    </thead>
    <tbody>
        <!-- Data will be inserted here -->
    </tbody>
</table>
<div id="profileModal" class="modal">
    <div class="modal-content">
        <form method="POST" action="">
            <label>Username:</label>
            <input type="text" name="username" id="username" value="" required readonly>
            
            <label>Password:</label>
            <input type="text" name="password" id="password" value="" readonly>
            
            <label>New Password:</label>
            <input type="password" name="new_password" placeholder="Enter new password">
            
            <button type="submit" name="action" value="update_profile" class="submit">Update</button>
            <button type="button" class="close" onclick="closeProfileModal()">Cancel</button>
        </form>
    </div>
</div>
</body>
</html>
