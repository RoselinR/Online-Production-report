<?php
// Database connection
$servername = "localhost:3377";
$username = "Divyesh";
$password = "Needivraj0705";
$dbname = "trg"; // Replace with your MySQL database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'getOperator':
            $PerNo = $_POST['PerNo'];
            $stmt = $conn->prepare("SELECT Name FROM EmployeeDetails WHERE PerNo = ?");
            $stmt->bind_param("s", $PerNo);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            echo json_encode(['Name' => $result['Name'] ?? '']);
            break;

        case 'getMachine':
            $HVFNo = $_POST['HVFNo'];
            $stmt = $conn->prepare("SELECT Make, Model, Name FROM MachineDetails WHERE HVFNo = ?");
            $stmt->bind_param("s", $HVFNo);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $description = $result ? "Make: {$result['Make']}, Model: {$result['Model']}, Name: {$result['Name']}" : '';
            echo json_encode(['MachineDescription' => $description]);
            break;

        case 'getComponent':
            $DrawingNo = $_POST['DrawingNo'];
            $stmt = $conn->prepare("SELECT Description FROM ComponentDetails WHERE DrawingNo = ?");
            $stmt->bind_param("s", $DrawingNo);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            echo json_encode(['ComponentDescription' => $result['Description'] ?? '']);
            break;

        case 'getOperation':
            $OpnNo = $_POST['OpnNo'];
            $stmt = $conn->prepare("SELECT OpnDesc FROM OperationDetails WHERE OpnNo = ?");
            $stmt->bind_param("s", $OpnNo);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            echo json_encode(['OpnDesc' => $result['OpnDesc'] ?? '']);
            break;

        case 'incrementCLS':
            $DrawingNo = $_POST['DrawingNo'];
            $OpnNo = $_POST['OpnNo'];
            $stmt = $conn->prepare("SELECT CLS FROM OperationDetails WHERE DrawingNo = ? AND OpnNo = ?");
            $stmt->bind_param("ss", $DrawingNo, $OpnNo);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result) {
                $newCLS = $result['CLS'] + 1;
                $updateStmt = $conn->prepare("UPDATE OperationDetails SET CLS = ? WHERE DrawingNo = ? AND OpnNo = ?");
                $updateStmt->bind_param("iss", $newCLS, $DrawingNo, $OpnNo);
                $updateStmt->execute();
                echo json_encode(['CLS' => $newCLS]);
            } else {
                echo json_encode(['CLS' => 0]);
            }
            break;
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Form</title>
    <style>
        /* Add your existing CSS here */
    </style>
</head>
<body>
    <div class="entry-container">
        <h2>Daily Production Details</h2>
        <form id="detailsForm">
            <!-- Form fields go here -->
            <div class="form-group">
                <label for="PerNo">Operator No:</label>
                <input type="text" id="PerNo" placeholder="Enter Operator No">
                <label for="Name">Operator Name:</label>
                <input type="text" id="Name" readonly>
            </div>
            <div class="form-group">
                <label for="HVFNo">Machine No:</label>
                <input type="text" id="HVFNo" placeholder="Enter Machine No">
                <label for="machineDescription">Machine Description:</label>
                <textarea id="machineDescription" readonly></textarea>
            </div>
            <div class="form-group">
                <label for="DrawingNo">Drawing No:</label>
                <input type="text" id="DrawingNo" placeholder="Enter Drawing No">
                <label for="componentDescription">Component Description:</label>
                <textarea id="componentDescription" readonly></textarea>
            </div>
            <div class="form-group">
                <label for="OpnNo">Operation No:</label>
                <input type="text" id="OpnNo" placeholder="Enter Operation No">
                <label for="OpnDesc">Operation Description:</label>
                <textarea id="OpnDesc" readonly></textarea>
            </div>
            <div class="form-group">
                <label for="componentCLS">Component CLS:</label>
                <input type="number" id="componentCLS" readonly>
            </div>
            <button type="button" onclick="submitForm()">Submit</button>
        </form>
    </div>

    <script>
        async function fetchData(endpoint, data) {
            const response = await fetch("", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data),
            });
            return await response.json();
        }

        document.getElementById('PerNo').addEventListener('input', async function () {
            const PerNo = this.value;
            const data = await fetchData("", { action: 'getOperator', PerNo });
            document.getElementById('Name').value = data.Name || '';
        });

        document.getElementById('HVFNo').addEventListener('input', async function () {
            const HVFNo = this.value;
            const data = await fetchData("", { action: 'getMachine', HVFNo });
            document.getElementById('machineDescription').value = data.MachineDescription || '';
        });

        document.getElementById('DrawingNo').addEventListener('input', async function () {
            const DrawingNo = this.value;
            const data = await fetchData("", { action: 'getComponent', DrawingNo });
            document.getElementById('componentDescription').value = data.ComponentDescription || '';
        });

        document.getElementById('OpnNo').addEventListener('input', async function () {
            const OpnNo = this.value;
            const data = await fetchData("", { action: 'getOperation', OpnNo });
            document.getElementById('OpnDesc').value = data.OpnDesc || '';
        });

        async function submitForm() {
            const DrawingNo = document.getElementById('DrawingNo').value;
            const OpnNo = document.getElementById('OpnNo').value;
            const data = await fetchData("", { action: 'incrementCLS', DrawingNo, OpnNo });
            document.getElementById('componentCLS').value = data.CLS || 0;
            alert('Form submitted successfully!');
        }
    </script>
</body>
</html>
