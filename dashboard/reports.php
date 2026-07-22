<?php

include("../config/db.php");

/*
|--------------------------------------------------------------------------
| Search & Filter
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$status = isset($_GET['status']) ? trim($_GET['status']) : "";

/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM reports WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND description LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$totalReports = $result->num_rows;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link rel="stylesheet" href="css/style.css">         
</head>
<body>
<br>
<h2>Reports</h2>
<br>

<p><strong>Total Results:</strong> <?= $totalReports; ?></p>

<form method="GET">

    <input
        type="text"
        name="search"
        placeholder="Search description..."
        value="<?= htmlspecialchars($search); ?>">

    <select name="status">
        <option value="">All Status</option>
        <option value="Pending" <?= ($status=="Pending") ? "selected" : ""; ?>>Pending</option>
        <option value="Assigned" <?= ($status=="Assigned") ? "selected" : ""; ?>>Assigned</option>
        <option value="Resolved" <?= ($status=="Resolved") ? "selected" : ""; ?>>Resolved</option>
    </select>

    <button class="btn">Search</button>
    <a class="btn" href="reports.php">Reset</a>

</form>

<br>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Image</th>
    <th>Description</th>
    <th>Risk Level</th>
    <th>Status</th>
    <th>Reporting Date</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php while($row = $result->fetch_assoc()) { 
    // Dynamic styling variables for Risks
    $riskColor = '#64748b'; $riskBg = '#f1f5f9'; $riskLabel = htmlspecialchars($row['risk_level']);
    if($row['risk_level'] == 'High') { $riskColor = '#b91c1c'; $riskBg = '#fee2e2'; }
    elseif($row['risk_level'] == 'Medium') { $riskColor = '#b45309'; $riskBg = '#fef9c3'; }
    elseif($row['risk_level'] == 'Low') { $riskColor = '#15803d'; $riskBg = '#dcfce7'; }
    elseif($row['risk_level'] == 'Unreviewed') { $riskColor = '#7c3aed'; $riskBg = '#f3e8ff'; $riskLabel = '⚠ Unreviewed'; }

    // Dynamic styling variables for Statuses
    $statusColor = '#475569'; $statusBg = '#f1f5f9';
    if($row['status'] == 'Pending') { $statusColor = '#475569'; $statusBg = '#f1f5f9'; }
    elseif($row['status'] == 'Assigned') { $statusColor = '#2563eb'; $statusBg = '#eff6ff'; }
    elseif($row['status'] == 'Resolved') { $statusColor = '#16a34a'; $statusBg = '#f0fdf4'; }
?>
<tr>
    <td><strong>#<?= $row['id']; ?></strong></td>
    <td>
        <img src="/uploads/<?= htmlspecialchars($row['image']); ?>" class="zoomable-img" width="70" height="50">
    </td>
    <td style="max-width: 350px; line-height: 1.4;"><?= htmlspecialchars($row['description']); ?></td>
    <td>
        <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; color: <?= $riskColor ?>; background: <?= $riskBg ?>;">
            <?= $riskLabel; ?>
        </span>
    </td>
    <td>
        <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; color: <?= $statusColor ?>; background: <?= $statusBg ?>;">
            ● <?= htmlspecialchars($row['status']); ?>
        </span>
    </td>
    <td style="color: #64748b; font-size: 13px;"><?= date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
    <td>
        <a class="btn" style="padding: 6px 12px; font-size: 13px;" href="report_details.php?id=<?= $row['id']; ?>">View Details</a>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

<script src="js/lightbox.js"></script>

</body>
</html>