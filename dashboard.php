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
try {
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First Chart: WorkingCondition distribution
    $stmt1 = $conn->prepare("SELECT WorkingCondition, COUNT(*) AS count FROM machinedetails GROUP BY WorkingCondition");
    $stmt1->execute();
    $dataPoints1 = [];
    while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        $dataPoints1[] = [
            'y' => (int)$row['count'],
            'name' => $row['WorkingCondition']
        ];
    }

    // Second Chart: CLS and Bal sums based on Variant=T72
    $stmt2 = $conn->prepare("
        SELECT 
            SUM(o.CLS) AS achieved, 
            SUM(o.Bal) AS not_achieved, 
            SUM(o.TGT) AS total 
        FROM operationdetails o
        INNER JOIN componentdetails c ON o.DrawingNo = c.DrawingNo
        WHERE c.Variant = 'T72'
    ");
    $stmt2->execute();
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);
    $achieved = (int)$row['achieved'];
    $notAchieved = (int)$row['not_achieved'];
    $totalTGT = (int)$row['total'];

    $dataPoints2 = [
        ['y' => $achieved, 'name' => 'Achieved (CLS)', 'color' => '#4CAF50'],
        ['y' => $notAchieved, 'name' => 'Not Achieved (Bal)', 'color' => '#F44336']
    ];
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    die();
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
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <style>
          .chart-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap; /* Ensures charts stack vertically on smaller screens */
            gap: 20px;
        }
        .chart {
            flex: 1;
            min-width: 200px; /* Ensures responsive design */
            max-width: 45%; /* Prevents overly large charts */
        }
        .chart canvas {
            width: 100% !important;
            height: auto !important;
        }
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
window.onload = function () {
            // First Chart: Machine Working Conditions
            var dataPoints1 = <?php echo json_encode($dataPoints1); ?>;
            var totalVisitors = dataPoints1.reduce((sum, point) => sum + point.y, 0);

            var chart1 = new CanvasJS.Chart("chartContainer1", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "Machine Working Conditions"
                },
                subtitles: [{
                    text: "Distribution of Working Conditions",
                    backgroundColor: "#2eacd1",
                    fontSize: 16,
                    fontColor: "white",
                    padding: 5
                }],
                legend: {
                    fontFamily: "calibri",
                    fontSize: 14,
                    itemTextFormatter: function (e) {
                        return e.dataPoint.name + ": " + Math.round(e.dataPoint.y / totalVisitors * 100) + "%";
                    }
                },
                data: [{
                    type: "doughnut",
                    innerRadius: "75%",
                    showInLegend: true,
                    dataPoints: dataPoints1
                }]
            });
            chart1.render();

            // Second Chart: TGT Analysis
            var dataPoints2 = <?php echo json_encode($dataPoints2); ?>;
            var totalTGT = <?php echo json_encode($totalTGT); ?>;

            var chart2 = new CanvasJS.Chart("chartContainer2", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "TGT Analysis for Variant T72"
                },
                subtitles: [{
                    text: `Total TGT: ${totalTGT}`,
                    backgroundColor: "#2eacd1",
                    fontSize: 16,
                    fontColor: "white",
                    padding: 5
                }],
                legend: {
                    fontFamily: "calibri",
                    fontSize: 14,
                    itemTextFormatter: function (e) {
                        return e.dataPoint.name + ": " + Math.round(e.dataPoint.y / totalTGT * 100) + "%";
                    }
                },
                data: [{
                    type: "doughnut",
                    innerRadius: "75%",
                    showInLegend: true,
                    dataPoints: dataPoints2
                }]
            });
            chart2.render();
        };
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
            <li><a href="/mainproject/report.php">Report</a></li>
            <li><a href="#">Dashboard</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Transmission Shop (TRG 1 & 2)</h1>
            <div class="profile" style="cursor: pointer;" onclick="openProfileModal()">My Profile</div>
        </div>
        <div class="chart-container">
        <div id="chartContainer1" class="chart"></div>
        <div id="chartContainer2" class="chart"></div>
    </div>  
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
