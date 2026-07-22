<?php
// dashboard/analytics.php
require_once '../config/db.php';

// Fetch aggregate counts for status metrics
$status_counts = $conn->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
// Fetch aggregate counts for Gemma 4 risk evaluations
$risk_counts   = $conn->query("SELECT risk_level, COUNT(*) as count FROM reports GROUP BY risk_level");

$metrics = ['Pending' => 0, 'Assigned' => 0, 'Resolved' => 0];
while ($row = $status_counts->fetch_assoc()) {
    if (isset($metrics[$row['status']])) {
        $metrics[$row['status']] = $row['count'];
    }
}

$risks = ['Low' => 0, 'Medium' => 0, 'High' => 0];
while ($row = $risk_counts->fetch_assoc()) {
    // Match the exact ENUM capitalization used in your schema ('Low', 'Medium', 'High')
    if (isset($risks[$row['risk_level']])) {
        $risks[$row['risk_level']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drainage Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; }
        .nav-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .back-btn { text-decoration: none; color: #007bff; font-weight: bold; background: white; padding: 8px 16px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #cbd5e1; }
        .card.high { border-left-color: #dc3545; }
        .card.pending { border-left-color: #ffc107; }
        .card.assigned { border-left-color: #007bff; }
        .card.resolved { border-left-color: #28a745; }
        .card h3 { margin: 0; color: #666; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .card p { margin: 10px 0 0 0; font-size: 32px; font-weight: bold; color: #1e293b; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        .chart-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); min-height: 350px; }
        .chart-container h3 { margin-top: 0; color: #334155; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav-header">
        <h2>📊 System Performance & Risk Analytics</h2>
        <a href="index.php" class="back-btn">← Back to Main Dashboard</a>
    </div>

    <div class="metrics-grid">
        <div class="card high">
            <h3>Gemma 4 High Risk Threats</h3>
            <p><?php echo $risks['High']; ?></p>
        </div>
        <div class="card pending">
            <h3>Pending Evaluations</h3>
            <p><?php echo $metrics['Pending']; ?></p>
        </div>
        <div class="card assigned">
            <h3>Dispatched / Assigned</h3>
            <p><?php echo $metrics['Assigned']; ?></p>
        </div>
        <div class="card resolved">
            <h3>Resolved Bottlenecks</h3>
            <p><?php echo $metrics['Resolved']; ?></p>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-container">
            <h3>Severity Distribution (Pie)</h3>
            <div style="position: relative; height:280px;">
                <canvas id="riskChart"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <h3>Operational Mitigation Progress (Bar)</h3>
            <div style="position: relative; height:280px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Risk Chart (Pie Chart displaying model allocations)
    const ctxRisk = document.getElementById('riskChart').getContext('2d');
    new Chart(ctxRisk, {
        type: 'pie',
        data: {
            labels: ['High Risk', 'Medium Risk', 'Low Risk'],
            datasets: [{
                data: [
                    <?php echo $risks['High']; ?>, 
                    <?php echo $risks['Medium']; ?>, 
                    <?php echo $risks['Low']; ?>
                ],
                backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                borderWidth: 2
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // 2. Operational Status Chart (Horizontal/Vertical Bar Chart tracking progress)
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'bar',
        data: {
            labels: ['Pending Review', 'Assigned Crews', 'Resolved Incidents'],
            datasets: [{
                label: 'Active Incident Records',
                data: [
                    <?php echo $metrics['Pending']; ?>, 
                    <?php echo $metrics['Assigned']; ?>, 
                    <?php echo $metrics['Resolved']; ?>
                ],
                backgroundColor: '#3b82f6',
                borderRadius: 4
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: { 
                y: { beginAtZero: true, ticks: { stepSize: 1 } } 
            },
            plugins: { legend: { display: false } }
        }
    });
</script>

</body>
</html>