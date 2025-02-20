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

// Database connection parameters
include 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $DrawingNo = $_POST['DrawingNo'] ?? '';
    $OpnNo = $_POST['OpnNo'] ?? '';
    $OpnName = $_POST['OpnName'] ?? '';
    $OpnDesc = $_POST['OpnDesc'] ?? '';
    $HVFNO = $_POST['HVFNO'] ?? '';
    $MachineType = $_POST['MachineType'] ?? '';
    $TGT = $_POST['TGT'] ?? '';
    $CLS = $_POST['CLS'] ?? '';
    $Bal = $_POST['Bal'] ?? '';

    if ($action === 'add') {
        // Add new record
        $stmt = $conn->prepare("SELECT MAX(SNO) AS max_SNO FROM operationdetails");
        $stmt->execute();
        $row = $stmt->fetch();
        $next_SNO = $row['max_SNO'] ? $row['max_SNO'] + 1 : 1;

        $sql = "INSERT INTO operationdetails (SNO, DrawingNo, OpnNo, OpnName, OpnDesc, HVFNO, MachineType, TGT, CLS, Bal) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$next_SNO, $DrawingNo, $OpnNo, $OpnName, $OpnDesc, $HVFNO, $MachineType, $TGT, $CLS, $Bal]);
    } elseif ($action === 'delete') {
        // Delete selected records
        $delete_ids = $_POST['delete_ids'] ?? [];
        if (!empty($delete_ids)) {
            $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));
            $sql = "DELETE FROM operationdetails WHERE SNO IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($delete_ids);

            // Re-index SNO
            $stmt = $conn->query("SELECT SNO FROM operationdetails ORDER BY SNO");
            $new_SNO = 1;
            foreach ($stmt as $row) {
                $updateStmt = $conn->prepare("UPDATE operationdetails SET SNO = ? WHERE SNO = ?");
                $updateStmt->execute([$new_SNO, $row['SNO']]);
                $new_SNO++;
            }
        }
    } elseif ($action === 'modify') {
        // Modify existing records
        $modified_data = json_decode($_POST['modified_data'], true);
        foreach ($modified_data as $row) {
            $sql = "UPDATE operationdetails 
                    SET OpnNo = ?, OpnName = ?, OpnDesc = ?, HVFNO = ?, MachineType = ?, CLS = ?, Bal = ? 
                    WHERE SNO = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $row['OpnNo'], $row['OpnName'], $row['OpnDesc'], $row['HVFNO'],
                $row['MachineType'], $row['CLS'], $row['Bal'], $row['SNO']
            ]);
        }

        try {
            // Update operationdetails.TGT from target table
            $conn->beginTransaction();
            $sql1 = "
                UPDATE operationdetails od
                JOIN target t ON od.DrawingNo = t.DrawingNo
                SET od.TGT = t.Target;
            ";
            $conn->exec($sql1);

            // Update operationdetails.BAL based on TGT and CLS
            $sql2 = "
                UPDATE operationdetails
                SET BAL = TGT - CLS;
            ";
            $conn->exec($sql2);

            // Commit the transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback if an error occurs
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['drawingNo']) && $_GET['drawingNo'] !== '') {
    // Fetch data based on drawing number
    $selectedDrawingNo = $_GET['drawingNo'];
    $stmt = $conn->prepare("SELECT * FROM operationdetails WHERE DrawingNo = ? ORDER BY SNO");
    $stmt->execute([$selectedDrawingNo]);
    $results = $stmt->fetchAll();
}

// Fetch data to display in the table
$stmt = $conn->query("SELECT * FROM operationdetails ORDER BY SNO");
$rows = $stmt->fetchAll();

// Fetch distinct drawing numbers
$drawingNumbers = [];
$stmt = $conn->query("SELECT DISTINCT DrawingNo FROM operationdetails");
$drawingNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch current user details
$current_user_id = 1; // Replace with actual session variable
$current_username = "";
$current_password = "";
$stmt = $conn->prepare("SELECT username, password FROM users WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch();
if ($user) {
    $current_username = $user['username'];
    $current_password = $user['password'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Details</title>
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
#opdet{
    margin-top:-18px;
}

    </style>
</head>
<body>
<script>
    
let isEditable = false; // Track whether the table is editable

// Toggle the editability of the table
function toggleEditable() {
    const tableCells = document.querySelectorAll("td.OpnNo, td.OpnName, td.OpnDesc, td.HVFNO, td.MachineType, td.CLS, td.Bal");
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
        const OpnNo = row.querySelector('.OpnNo').innerText;
        const OpnName = row.querySelector('.OpnName').innerText;
        const OpnDesc = row.querySelector('.OpnDesc').innerText;
        const HVFNO = row.querySelector('.HVFNO').innerText;
        const MachineType = row.querySelector('.MachineType').innerText;
        
        const CLS = row.querySelector('.CLS').innerText;
        const Bal = row.querySelector('.Bal').innerText;

        data.push({ SNO: sno, OpnNo, OpnName, OpnDesc, HVFNO, MachineType, CLS, Bal });
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
        <h2 id="opdet">Operation Details</h2>
        <div style="text-align: right; margin: 10px;">
    <a href="?logout=true" class="btn" style="width: auto; display: inline-block; background-color: red;">Logout</a>
</div>
        <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search for description" onkeyup="filterTable()">
            <button class="open-modal" onclick="openModal()">Add</button>
            <button class="open-modal" onclick="toggleEditable()">Modify</button>
            <button class="open-modal" onclick="myFunction()" >Delete </button>
        </div>
        <form method="GET" action="">
        <label for="drawingNo">Select Drawing No:</label>
        <select name="drawingNo" id="drawingNo">
            <option value="">--Select Drawing No--</option>
            <?php foreach ($drawingNumbers as $drawingNo): ?>
                <option value="<?= htmlspecialchars($drawingNo) ?>" <?= isset($_GET['drawingNo']) && $_GET['drawingNo'] === $drawingNo ? 'selected' : '' ?>>
                    <?= htmlspecialchars($drawingNo) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Submit</button>
    </form>
    <?php if (!empty($results)): ?>
        <h2>Results for Drawing No: <?= htmlspecialchars($selectedDrawingNo) ?></h2>
        <form method="post" onsubmit="return confirmDeletion()">
        <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="display: none;" class="select-column">Select</th>
                    <th>SNo</th>
                    <th>OpnNo</th>
                    <th>OpnName</th>
                    <th>OpnDesc</th>
                    <th>HVFNO</th>
                    <th>MachineType</th>
                    <th>TGT</th>
                    <th>CLS</th>
                    <th>Bal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                <tr data-sno="<?php echo $row['SNO']; ?>">
                <td style="display: none;" class="select-column"><input type="checkbox" name="delete_ids[]" value="<?php echo $row['SNO']; ?>"></td>
                <td><?php echo $row['SNO']; ?></td>
                <td class="OpnNo" contenteditable="false"><?php echo $row['OpnNo']; ?></td>
                <td class="OpnName" contenteditable="false"><?php echo $row['OpnName']; ?></td>
                <td class="OpnDesc" contenteditable="false"><?php echo $row['OpnDesc']; ?></td>
                <td class="HVFNO" contenteditable="false"><?php echo $row['HVFNo']; ?></td>
                <td class="MachineType" contenteditable="false"><?php echo $row['MachineType']; ?></td>
                <td class="TGT" contenteditable="false"><?php echo $row['TGT']; ?></td>
                <td class="CLS" contenteditable="false"><?php echo $row['CLS']; ?></td>
                <td class="Bal" contenteditable="false"><?php echo $row['Bal']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <button id="delete" type="submit" name="action" value="delete"  hidden>Delete Selected</button>
             <button id="modif" type="button" onclick="submitModifications();return confirmModification()" hidden>Modify Changes</button>
        </table>
    </div></form>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['drawingNo'])): ?>
        <p>No results found for the selected Drawing No.</p>
    <?php endif; ?>
    </div>
    

<div id="entryModal" class="modal">
    <div class="modal-content">
        <form method="POST">
        <label>Drawing No</label>
            <input type="text" name="DrawingNo" required>
            
            <label>Operation No</label>
            <input type="text" name="OpnNo" required>
            
            <label>Operation Name</label>
            <input type="text" name="OpnName" required>

            <label>Operation Description:</label>
            <input type="text" name="OpnDesc" required>

            <label>HVFNO:</label>
            <input type="text" name="HVFNO" required>

            <label>MachineType:</label>
            <input type="text" name="MachineType" required>

            <label>TGT:</label>
            <input type="text" name="TGT" required>

            <label>CLS:</label>
            <input type="text" name="CLS" required>

            <label>Bal:</label>
            <input type="text" name="Bal" required>


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
