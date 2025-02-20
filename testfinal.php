<!DOCTYPE HTML>
<html>
<head>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
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
            min-width: 300px; /* Ensures responsive design */
            max-width: 30%; /* Prevents overly large charts */
        }
        .chart canvas {
            width: 100% !important;
            height: auto !important;
        }
    </style>
</head>
<body>
    <div class="chart-container">
        <div id="chartContainer1" class="chart"></div>
        <div id="chartContainer2" class="chart"></div>
        <div id="chartContainer3" class="chart"></div>
    </div>

    <?php
    // Database connection using PDO
    $host = 'localhost';
    $dbname = 'trg';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;port=3377;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // First Chart: WorkingCondition distribution
        $stmt1 = $pdo->prepare("SELECT WorkingCondition, COUNT(*) AS count FROM machinedetails GROUP BY WorkingCondition");
        $stmt1->execute();
        $dataPoints1 = [];
        while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
            $dataPoints1[] = [
                'y' => (int)$row['count'],
                'name' => $row['WorkingCondition']
            ];
        }

        // Second Chart: CLS and Bal sums based on Variant=T72
        $stmt2 = $pdo->prepare("
            SELECT 
                SUM(o.CLS) AS achieved, 
                SUM(o.Bal) AS not_achieved, 
                SUM(o.TGT) AS total 
            FROM operationdetails o
            INNER JOIN componentdetails c ON o.DrawingNo = c.DrawingNo
            WHERE c.Variant = 'T72'
        ");
        $stmt2->execute();
        $rowT72 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $achievedT72 = (int)$rowT72['achieved'];
        $notAchievedT72 = (int)$rowT72['not_achieved'];
        $totalTGTT72 = (int)$rowT72['total'];

        $dataPoints2 = [
            ['y' => $achievedT72, 'name' => 'Achieved (CLS)', 'color' => '#4CAF50'],
            ['y' => $notAchievedT72, 'name' => 'Not Achieved (Bal)', 'color' => '#F44336']
        ];

        // Third Chart: CLS and Bal sums based on Variant=T90
        $stmt3 = $pdo->prepare("
            SELECT 
                SUM(o.CLS) AS achieved, 
                SUM(o.Bal) AS not_achieved, 
                SUM(o.TGT) AS total 
            FROM operationdetails o
            INNER JOIN componentdetails c ON o.DrawingNo = c.DrawingNo
            WHERE c.Variant = 'T90'
        ");
        $stmt3->execute();
        $rowT90 = $stmt3->fetch(PDO::FETCH_ASSOC);
        $achievedT90 = (int)$rowT90['achieved'];
        $notAchievedT90 = (int)$rowT90['not_achieved'];
        $totalTGTT90 = (int)$rowT90['total'];

        $dataPoints3 = [
            ['y' => $achievedT90, 'name' => 'Achieved (CLS)', 'color' => '#4CAF50'],
            ['y' => $notAchievedT90, 'name' => 'Not Achieved (Bal)', 'color' => '#F44336']
        ];
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
        die();
    }
    ?>

    <script>
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

            // Second Chart: TGT Analysis for Variant T72
            var dataPoints2 = <?php echo json_encode($dataPoints2); ?>;
            var totalTGTT72 = <?php echo json_encode($totalTGTT72); ?>;

            var chart2 = new CanvasJS.Chart("chartContainer2", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "TGT Analysis for Variant T72"
                },
                subtitles: [{
                    text: `Total TGT: ${totalTGTT72}`,
                    backgroundColor: "#2eacd1",
                    fontSize: 16,
                    fontColor: "white",
                    padding: 5
                }],
                legend: {
                    fontFamily: "calibri",
                    fontSize: 14,
                    itemTextFormatter: function (e) {
                        return e.dataPoint.name + ": " + Math.round(e.dataPoint.y / totalTGTT72 * 100) + "%";
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

            // Third Chart: TGT Analysis for Variant T90
            var dataPoints3 = <?php echo json_encode($dataPoints3); ?>;
            var totalTGTT90 = <?php echo json_encode($totalTGTT90); ?>;

            var chart3 = new CanvasJS.Chart("chartContainer3", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "TGT Analysis for Variant T90"
                },
                subtitles: [{
                    text: `Total TGT: ${totalTGTT90}`,
                    backgroundColor: "#2eacd1",
                    fontSize: 16,
                    fontColor: "white",
                    padding: 5
                }],
                legend: {
                    fontFamily: "calibri",
                    fontSize: 14,
                    itemTextFormatter: function (e) {
                        return e.dataPoint.name + ": " + Math.round(e.dataPoint.y / totalTGTT90 * 100) + "%";
                    }
                },
                data: [{
                    type: "doughnut",
                    innerRadius: "75%",
                    showInLegend: true,
                    dataPoints: dataPoints3
                }]
            });
            chart3.render();
        };
    </script>
</body>
</html>
