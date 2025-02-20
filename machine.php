<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: profile.php");
    exit();
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: profile.php");
    exit();
}

include 'dbcon.php';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $Location = $_POST['Location'] ?? '';
    $HVFNo = $_POST['HVFNo'] ?? '';
    $Make = $_POST['Make'] ?? '';
    $Name = $_POST['Name'] ?? '';
    $Model = $_POST['Model'] ?? '';
    $Description = $_POST['Description'] ?? '';
    $Bay = $_POST['Bay'] ?? '';
    $WorkingCondition = $_POST['WorkingCondition'] ?? '';

    if ($action === 'add') {
        // Insert a new record
        $stmt = $conn->prepare("INSERT INTO machinedetails (Location, HVFNo, Make, Model, Name, Description, Bay, WorkingCondition) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$Location, $HVFNo, $Make, $Model, $Name, $Description, $Bay, $WorkingCondition]);
    } elseif ($action === 'delete') {
        // Delete selected records
        $delete_ids = $_POST['delete_ids'] ?? [];
        if (!empty($delete_ids)) {
            $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));
            $stmt = $conn->prepare("DELETE FROM machinedetails WHERE SNO IN ($placeholders)");
            $stmt->execute($delete_ids);

            // Update SNO values to maintain sequential order
            $result = $conn->query("SELECT SNO FROM machinedetails ORDER BY SNO");
            $new_SNO = 1;
            foreach ($result as $row) {
                $updateStmt = $conn->prepare("UPDATE machinedetails SET SNO = ? WHERE SNO = ?");
                $updateStmt->execute([$new_SNO, $row['SNO']]);
                $new_SNO++;
            }
        }
    } elseif ($action === 'modify') {
        // Update modified rows
        $modified_data = json_decode($_POST['modified_data'], true);
        foreach ($modified_data as $row) {
            $stmt = $conn->prepare("UPDATE machinedetails SET Location = ?, HVFNo = ?, Make = ?, Model = ?, Name = ?, Description = ?, Bay = ?, WorkingCondition = ? WHERE SNO = ?");
            $stmt->execute([
                $row['Location'], $row['HVFNo'], $row['Make'], $row['Model'], $row['Name'],
                $row['Description'], $row['Bay'], $row['WorkingCondition'], $row['SNO']
            ]);
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_profile') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $new_password = $_POST['new_password'];

    // Verify current password
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    if ($stmt->rowCount() > 0) {
        // Update to new password if provided
        if (!empty($new_password)) {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->execute([$new_password, $username]);
            $pwerr = "Password updated successfully!";
        } else {
            $pwerr = "New password cannot be empty!";
        }
    } else {
        $pwerr = "Invalid current password!";
    }
}

// Fetch data to display in the table
$sql = "SELECT * FROM machinedetails ORDER BY SNO";
$stmt = $conn->query($sql);
$rows = $stmt->fetchAll();

// Fetch user data for the profile modal
$current_user_id = 1; // Replace with actual session variable
$current_username = "";
$current_password = "";
$stmt = $conn->prepare("SELECT username, password FROM users WHERE user_id = ?");
$stmt->execute([$current_user_id]);
if ($user = $stmt->fetch()) {
    $current_username = $user['username'];
    $current_password = $user['password'];
}

$conn = null; // Close the connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Data</title>
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
    position: sticky; /* Keep header fixed when scrolling */
    top: 0;
    z-index: 1; /* Ensure header stays above other content */
}

    </style>
</head>
<body>
<script>
let isEditable = false; // Track whether the table is editable

// Toggle the editability of the table
function toggleEditable() {
    const tableCells = document.querySelectorAll("td.Location, td.HVFNo, td.Make, td.Model, td.Name, td.Description, td.Bay, td.WorkingCondition");
    var x = document.getElementById("modif");
  if (x.hidden === true) {
    x.hidden = false;
  } else {
    x.hidden = true;
  }
    isEditable = !isEditable; // Toggle the state

    tableCells.forEach(cell => {
        cell.contentEditable = isEditable; // Enable or disable editing
    });

    const modifyButton = document.querySelector(".modify-button");
    modifyButton.textContent = isEditable ? "Disable Edit" : "Modify"; // Update button text
}
    const modifiedRows = new Set();

// Mark a row as modified when a cell changes
function markModified(event) {
    const row = event.target.closest('tr');
    if (row && row.dataset.sno) {
        modifiedRows.add(row.dataset.sno);
    }
}

// Gather all modified rows for submission
function submitModifications() {
    const data = [];
    modifiedRows.forEach((sno) => {
        const row = document.querySelector(`tr[data-sno='${sno}']`);
        const Location = row.querySelector('.Location').innerText;
        const HVFNo = row.querySelector('.HVFNo').innerText;
        const Make = row.querySelector('.Make').innerText;
        const Model = row.querySelector('.Model').innerText;
        const Name = row.querySelector('.Name').innerText;
        const Description = row.querySelector('.Description').innerText;
        const Bay = row.querySelector('.Bay').innerText;
        const WorkingCondition = row.querySelector('.WorkingCondition').innerText;

        data.push({ SNO: sno, Location, HVFNo, Make, Model, Name, Description, Bay, WorkingCondition});
    });

    // Submit data via a form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'modify';
    form.appendChild(actionInput);

    const dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'modified_data';
    dataInput.value = JSON.stringify(data);
    form.appendChild(dataInput);

    document.body.appendChild(form);
    form.submit();
}

// Attach the event listener for marking modifications
document.addEventListener('input', markModified);
        function openModal() {
            document.getElementById('entryModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('entryModal').style.display = 'none';
        }
        function myFunction() {
  var x = document.getElementById("delete");
  if (x.hidden === true) {
    x.hidden = false;
  } else {
    x.hidden = true;
  }
  const selectColumns = document.querySelectorAll('.select-column');

    selectColumns.forEach((column) => {
        column.style.display = column.style.display === 'none' ? '' : 'none';
    });
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
function confirmDeletion() {
        const checkedItems = document.querySelectorAll('input[name="delete_ids[]"]:checked');
        if (checkedItems.length === 0) {
            alert('Please select at least one item to delete.');
            return false;
        }
        return confirm('Are you sure you want to delete the selected items?');
    }
     function confirmModification() {
        return confirm('Are you sure you want to modify the selected items?');
    }
    </script>
    <form action="POST"></form>
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
        <h2>Machine Data</h2>
        <div style="text-align: right; margin: 10px;">
    <a href="?logout=true" class="btn" style="width: auto; display: inline-block; background-color: red;">Logout</a>
</div>
        <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search for description" onkeyup="filterTable()">
            <button class="open-modal" onclick="openModal()">Add</button>
            <button class="open-modal" onclick="toggleEditable()">Modify</button>
            <button class="open-modal" onclick="myFunction()" >Delete </button>
        </div>
     <form method="post" onsubmit="return confirmDeletion()">
        
        <div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="display: none;" class="select-column">Select</th>
                <th>S.NO</th>
                <th>Location</th>
                <th>HVFNo</th>
                <th>Make</th>
                <th>Model</th>
                <th>Name</th>
                <th>Description</th>
                <th>Bay</th>
                <th>WorkingCondition</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr data-sno="<?php echo $row['SNO']; ?>">
                <td style="display: none;" class="select-column"><input type="checkbox" name="delete_ids[]" value="<?php echo $row['SNO']; ?>"></td>
                <td><?php echo $row['SNO']; ?></td>
                <td class="Location" contenteditable="false"><?php echo $row['Location']; ?></td>
                <td class="HVFNo" contenteditable="false"><?php echo $row['HVFNo']; ?></td>
                <td class="Make" contenteditable="false"><?php echo $row['Make']; ?></td>
                <td class="Model" contenteditable="false"><?php echo $row['Model']; ?></td>
                <td class="Name" contenteditable="false"><?php echo $row['Name']; ?></td>
                <td class="Description" contenteditable="false"><?php echo $row['Description']; ?></td>
                <td class="Bay" contenteditable="false"><?php echo $row['Bay']; ?></td>
                <td class="WorkingCondition" contenteditable="false"><?php echo $row['WorkingCondition']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <button id="delete" type="submit" name="action" value="delete" hidden>Delete Selected</button>
        <button id="modif" type="button" onclick="submitModifications(); return confirmModification()" hidden>Modify Changes</button>
    </table>
</div>
</form>
    </div>
    

<div id="entryModal" class="modal">
    <div class="modal-content">
        <form method="POST">
            <label>Location:</label>
            <input type="text" name="Location" required>

            <label>HVFNo:</label>
            <input type="text" name="HVFNo" required>

            <label>Make:</label>
            <input type="text" name="Make" required>

            <label>Model:</label>
            <input type="text" name="Model" required>

            <label>Name:</label>
            <input type="text" name="Name" required>

            <label>Description:</label>
            <input type="text" name="Description" required>

            <label>Bay:</label>
            <input type="text" name="Bay" required>

            <label>WorkingCondition:</label>
            <input type="text" name="WorkingCondition" required>

            <button type="submit" name="action" value="add" class="submit">Add</button>
            <button type="button" class="close" onclick="closeModal()">Cancel</button>
        </form>
    </div>
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
