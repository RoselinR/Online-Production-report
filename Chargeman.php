<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['PerNo'])) {
    // Redirect to login page if not logged in
    header("Location: validate.php");
    exit();
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: validate.php");
    exit();
}

include 'dbcon.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'getOperator':
            $PerNo = $_POST['PerNo'];
            $stmt = $conn->prepare("SELECT Name FROM employeedetails WHERE PerNo = :PerNo");
            $stmt->execute([':PerNo' => $PerNo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['Name' => $result['Name'] ?? '']);
            break;

        case 'getMachine':
            $HVFNo = $_POST['HVFNo'];
            $stmt = $conn->prepare("SELECT Make, Model, Name, Description FROM machinedetails WHERE HVFNo = :HVFNo");
            $stmt->execute([':HVFNo' => $HVFNo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $description = $result ? "Make: {$result['Make']}, Model: {$result['Model']}, Name: {$result['Name']}, Description: {$result['Description']}" : '';
            echo json_encode(['MachineDescription' => $description]);
            break;

        case 'getComponent':
            $DrawingNo = $_POST['DrawingNo'];
            $stmt = $conn->prepare("SELECT CompName, Description FROM componentdetails WHERE DrawingNo = :DrawingNo");
            $stmt->execute([':DrawingNo' => $DrawingNo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $desc = $result ? "Name {$result['CompName']}, Description: {$result['Description']}" : '';
            echo json_encode(['ComponentDescription' => $desc]);
            break;

        case 'getOperation':
            $OpnNo = $_POST['OpnNo'];
            $stmt = $conn->prepare("SELECT OpnDesc FROM operationdetails WHERE OpnNo = :OpnNo");
            $stmt->execute([':OpnNo' => $OpnNo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['OpnDesc' => $result['OpnDesc'] ?? '']);
            break;

        case 'getCLS':
            $DrawingNo = $_POST['DrawingNo'];
            $OpnNo = $_POST['OpnNo'];
            $stmt = $conn->prepare("SELECT CLS FROM operationdetails WHERE DrawingNo = :DrawingNo AND OpnNo = :OpnNo");
            $stmt->execute([':DrawingNo' => $DrawingNo, ':OpnNo' => $OpnNo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['CLS' => $result['CLS'] ?? 0]); // Default value if no match
            break;

        case 'incrementAndSubmit':
            $DrawingNo = $_POST['DrawingNo'];
            $OpnNo = $_POST['OpnNo'];
            $componentsProduced = $_POST['componentsProduced'];
            $machineStatus = $_POST['machineStatus'];
            $otherMachineStatus = $_POST['otherMachineStatus'] ?? ''; // For "others" machine status
            
            $finalMachineStatus = ($machineStatus === '5' && !empty($otherMachineStatus)) ? $otherMachineStatus : $machineStatus;

            $conn->beginTransaction();

            try {
                // Fetch current CLS
                $stmt = $conn->prepare("SELECT TGT, CLS FROM operationdetails WHERE DrawingNo = :DrawingNo AND OpnNo = :OpnNo");
                $stmt->execute([':DrawingNo' => $DrawingNo, ':OpnNo' => $OpnNo]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$result) {
                    throw new Exception('Operation or Component not found.');
                }

                $newCLS = $result['CLS'] + $componentsProduced;

                if ($newCLS > $result['TGT']) {
                    throw new Exception('CLS exceeds target');
                }

                $newBal = $result['TGT'] - $newCLS;

                // Update CLS and balance
                $updateStmt = $conn->prepare("UPDATE operationdetails SET CLS = :newCLS, Bal = :newBal WHERE DrawingNo = :DrawingNo AND OpnNo = :OpnNo");
                $updateStmt->execute([':newCLS' => $newCLS, ':newBal' => $newBal, ':DrawingNo' => $DrawingNo, ':OpnNo' => $OpnNo]);

                // Insert into productiondetails
                $insertStmt = $conn->prepare("INSERT INTO productiondetails 
                    (TodaysDate, FillDate, MachineStatus, Shift, BayNo, PerNo, MachineNo, DrawingNo, ComponentsProduced, OpnNo, CLS) 
                    VALUES (:todaysDate, :fillDate, :machineStatus, :shift, :bayNo, :perNo, :machineNo, :drawingNo, :componentsProduced, :opnNo, :cls)");
                $insertStmt->execute([
                    ':todaysDate' => $_POST['todaysDate'],
                    ':fillDate' => $_POST['fillDate'],
                    ':machineStatus' => $finalMachineStatus,
                    ':shift' => $_POST['shift'],
                    ':bayNo' => $_POST['bayNo'],
                    ':perNo' => $_POST['PerNo'],
                    ':machineNo' => $_POST['HVFNo'],
                    ':drawingNo' => $DrawingNo,
                    ':componentsProduced' => $componentsProduced,
                    ':opnNo' => $OpnNo,
                    ':cls' => $newCLS
                ]);

                // Update machine working condition
                $updateMachineStmt = $conn->prepare("UPDATE machinedetails SET WorkingCondition = :workingCondition WHERE HVFNo = :machineNo");
                $updateMachineStmt->execute([':workingCondition' => $finalMachineStatus, ':machineNo' => $_POST['HVFNo']]);

                $conn->commit();
                echo json_encode(['success' => true, 'CLS' => $newCLS]);

            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'getAssemblies':
            $DrawingNo = $_POST['DrawingNo'];
            $stmt = $conn->prepare("SELECT HigherAssembly FROM componentdetails WHERE DrawingNo = :DrawingNo");
            $stmt->execute([':DrawingNo' => $DrawingNo]);
            $assemblies = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['assemblies' => $assemblies]);
            break;

        case 'getAssemblyDescription':
            $HigherAssembly = $_POST['HigherAssembly'];
            $stmt = $conn->prepare("SELECT CompName, Description FROM componentdetails WHERE CompName = :HigherAssembly");
            $stmt->execute([':HigherAssembly' => $HigherAssembly]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $desc = $result ? "Name: {$result['CompName']}, Description: {$result['Description']}" : '';
            echo json_encode(['AssemblyDescription' => $desc]);
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
  <title>Charge Man Details Entry</title>
  <style>

.form-row1{
margin-bottom: 15px;
}

.form-row {
  display: flex;
  justify-content: space-between; /* Space between left and right fields */
  margin-bottom: 15px;
}

.form-row .form-group {
  width: 48%; /* Each input takes half the width */
}
.form-group textarea {
  width: 100%;
  height: 60px; /* Adjust height as needed */
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  resize: none; /* Prevent resizing */
  box-sizing: border-box;


}
.form-group label {
  display: block;
  font-weight: bold;
  margin-bottom: 5px;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}

.form-group input[readonly] {
  background-color: #e9ecef;
}

.entry-container {
  background: #fff;
  padding: 20px;
  margin: auto;
  
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-width: 1200px;
  
}

h2 {
  text-align: center;
  color: #333;
  margin-bottom: 20px;
}

body {
  font-family: Arial, sans-serif;
  background-color: #f4f4f9;
  margin: 0;
display:grid;

  align-items:center;
  padding: 0;
  min-height: 100vh;
  background-image: url('Greynorange.jpg');
  background-size: 150%;
}

.btn {
  display: block;
  width: 100%;
  padding: 10px;
  background-color: #FF840A;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
}

.btn:hover {
  background-color: #0056b3;
} </style>
</head>
<body><article>
 <div class="entry-container">
  <h2>Daily Production Details</h2>
  <div style="text-align: right; margin: 10px;">
    <a href="?logout=true" class="btn" style="width: auto; display: inline-block; background-color: red;">Logout</a>
</div>

  <form id="detailsForm">
    <!-- Row 1 -->
    <div class="form-row">
      <div class="form-group">
        <label for="todaysDate">Today's Date:</label>
        <input type="date" id="todaysDate" name="todaysDate" readonly>

      </div>
      <div class="form-group">
        <label for="fillDate">Date of Production:</label>
        <input type="date" id="fillDate">
      </div>
    </div>
<div class="form-row1">
<div class="form-group">
      <label for="machinestatus">Machine Status:</label>
      <select id="machinestatus" >
<option value="">--Select Machine Status--</option>
          <option value="Working">Working</option>
          <option value="Breakdown">Breakdown</option>
          <option value="NoOperator">No Operator</option>
          <option value="WaitingForComponents">Waiting for components</option>
          <option value="5">others</option>
        </select>
	<input id="ip" type="text" name="format" value="" placeholder="" readonly>
    </div>
</div>
    <!-- Row 2 -->
    <div class="form-row">
      <div class="form-group">
        <label for="shift">Shift:</label>
        <select id="shift" required>
          <option value="">--Select Shift--</option>
          <option value="Shift 1">Shift 1</option>
          <option value="Shift 2">Shift 2</option>
          <option value="Shift 3">Shift 3</option>
          <option value="Shift 3">Shift 4</option>
          <option value="Shift 3">Shift 5</option>
          <option value="Shift 3">Shift 6</option>
          <option value="Shift 3">Shift 7</option>
        </select>
      </div>
      <div class="form-group">
        <label for="bayNo">Bay No:</label>
        <select id="bayNo">
          <option value="">--Select Bay--</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
        </select>
      </div>
    </div>
    <!-- Row 3 -->
    <div class="form-row">
      <div class="form-group">
        <label for="PerNo">Operator No:</label>
        <input type="text" id="PerNo" placeholder="Enter Operator No">
      </div>
      <div class="form-group">
        <label for="Name">Operator Name:</label>
        <input type="text" id="Name" readonly>
      </div>
    </div>
    <!-- Row 4 -->
    <div class="form-row">
      <div class="form-group">
        <label for="HVFNo">Machine No:</label>
        <input type="text" id="HVFNo">
      </div>
      <div class="form-group">
        <label for="machineDescription">Machine Description:</label>
        <textarea id="machineDescription" readonly></textarea>
      </div>
    </div>
    <!-- Row 5 -->
    <div class="form-row">
      <div class="form-group">
        <label for="DrawingNo">Component Drawing No:</label>
        <input type="text" id="DrawingNo" placeholder="Enter Drawing No">
      </div>
      <div class="form-group">
        <label for="componentDescription">Component Description:</label>
        <textarea id="componentDescription" readonly></textarea>
      </div>
    </div>
    <!-- Row 6 -->
    <div class="form-row">
      <div class="form-group">
        <label for="assembly">Used in Assembly:</label>
        <select id="assembly"> </select>
      </div>
      <div class="form-group">
        <label for="assemblyDescription">Assembly Description:</label>
        <textarea id="assemblyDescription" readonly></textarea>
      </div>
    </div>
    <!-- Row 7 -->
    <div class="form-row">
      <div class="form-group">
        <label for="OpnNo">Operation No:</label>
        <input type="text" id="OpnNo">
      </div>
      <div class="form-group">
        <label for="OpnDesc">Operation Description:</label>
        <textarea id="OpnDesc" readonly></textarea>
      </div>
    </div>
    <!-- Row 8 -->
<div class="form-row">
    <div class="form-group">
      <label for="componentsProduced">Number of Components Produced:</label>
      <input type="number" id="componentsProduced" placeholder="Enter Number of Components">
    </div>
    <div class="form-group">
      <label id="clearence" for="componentCLS">Component CLS for Operation:</label>
      <input type="number" id="componentCLS" readonly>
    </div>
</div>
    <button type="submit" class="btn">Submit</button>
  </form>
</div>
</article>
<script>

  async function fetchData(endpoint, data) {
      const response = await fetch("", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams(data),
      });
      return await response.json();
  }
document.getElementById('machinestatus').addEventListener('change', function () {
    const machineStatus = this.value;
    const othersInputField = document.getElementById('ip');

    if (machineStatus === '5') { // '5' corresponds to "Others"
        othersInputField.removeAttribute('readonly');  // Make the input field editable
    } else {
        othersInputField.setAttribute('readonly', 'true');  // Make the input field readonly
        othersInputField.value = ''; // Clear the input field when not selected
    }
});

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

  document.getElementById('detailsForm').addEventListener('submit', async function (event) {
    event.preventDefault(); // Prevent default form submission

    const DrawingNo = document.getElementById('DrawingNo').value;
    const OpnNo = document.getElementById('OpnNo').value;
    const componentsProduced = document.getElementById('componentsProduced').value;
    const machineStatus = document.getElementById('machinestatus').value;
    const otherMachineStatus = document.getElementById('ip').value; // Value from the "others" input field

    if (!DrawingNo || !OpnNo || !componentsProduced || !machineStatus) {
        alert('Please fill all required fields.');
        return;
    }

    const data = await fetchData("", {
        action: 'incrementAndSubmit',
        DrawingNo,
        OpnNo,
        componentsProduced,
        machineStatus,
        otherMachineStatus,
        shift: document.getElementById('shift').value,
        bayNo: document.getElementById('bayNo').value,
        PerNo: document.getElementById('PerNo').value,
        HVFNo: document.getElementById('HVFNo').value,
        fillDate: document.getElementById('fillDate').value,
        todaysDate: document.getElementById('todaysDate').value,
        
    });

    if (data.success) {
     
        alert('Form submitted successfully!');
        document.getElementById('detailsForm').reset()
    } else {
        alert(data.message || 'Failed to submit the form.');
    }
});



  async function fetchCLS() {
    const DrawingNo = document.getElementById('DrawingNo').value;
    const OpnNo = document.getElementById('OpnNo').value;

    // Only fetch if both fields have values
    if (DrawingNo && OpnNo) {
        const data = await fetchData("", {
            action: 'getCLS',
            DrawingNo,
            OpnNo
        });
        
        document.getElementById('componentCLS').value = data.CLS || 0;
    } else {
        document.getElementById('componentCLS').value = ''; // Clear the field if inputs are incomplete
    }
}
document.getElementById('DrawingNo').addEventListener('input', async function () {
    const DrawingNo = this.value;

    if (DrawingNo) {
        const data = await fetchData("", { action: 'getAssemblies', DrawingNo });
        const assemblySelect = document.getElementById('assembly');

        // Clear existing options
        assemblySelect.innerHTML = '<option value="">--Select Assembly--</option>';

        // Populate new options
        if (data.assemblies && data.assemblies.length > 0) {
            data.assemblies.forEach(assembly => {
                const option = document.createElement('option');
                option.value = assembly;
                option.textContent = assembly;
                assemblySelect.appendChild(option);
            });
        }
    }
});
document.getElementById('assembly').addEventListener('change', async function () {
    const HigherAssembly = this.value;

    if (HigherAssembly) {
        const data = await fetchData("", { action: 'getAssemblyDescription', HigherAssembly });
        document.getElementById('assemblyDescription').value = data.AssemblyDescription || '';
    } else {
        document.getElementById('assemblyDescription').value = ''; // Clear description if no assembly is selected
    }
});

window.onload = function() {
    // Get today's date
    const today = new Date();
    
    // Format the date as YYYY-MM-DD (the format expected by the input field)
    const formattedDate = today.toISOString().split('T')[0];
    
    // Set the value of the 'todaysDate' input field
    document.getElementById('todaysDate').value = formattedDate;
};

// Add event listeners
document.getElementById('DrawingNo').addEventListener('input', fetchCLS);
document.getElementById('OpnNo').addEventListener('input', fetchCLS);

</script>

</body>
</html>
