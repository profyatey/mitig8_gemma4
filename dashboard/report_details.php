<?php

include("../config/db.php");
include("includes/header.php");
include("includes/sidebar.php");

if (!isset($_GET['id'])) {
    die("No report selected.");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Report not found.");
}

$report = $result->fetch_assoc();

?>

<div class="main">

<h2>Report Details</h2>

<table>

<tr>
<th>ID</th>
<td><?= $report['id']; ?></td>
</tr>

<tr>
<th>Image</th>
<td>
<img src="../uploads/<?= htmlspecialchars($report['image']); ?>" width="500" class="zoomable-img" style="border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
</td>
</tr>

<tr>
<th>Description</th>
<td><?= htmlspecialchars($report['description']); ?></td>
</tr>

<tr>
<th>Latitude</th>
<td><?= $report['latitude']; ?></td>
</tr>

<tr>
<th>Longitude</th>
<td><?= $report['longitude']; ?></td>
</tr>

<tr>
<th>Risk Level</th>
<td><?= $report['risk_level']; ?></td>
</tr>

<tr>
<th>Status</th>
<td><?= $report['status']; ?></td>
</tr>

<tr>
<th>Gemma4 Anaylysis</th>
<td><?= htmlspecialchars($report['ai_reasoning']); ?></td>
</tr>

<tr>
<th>Date Submitted</th>
<td><?= $report['created_at']; ?></td>
</tr>

</table>

<br>

<a class="btn" href="update_status.php?id=<?= $report['id']; ?>">
Update Status
</a>

<a class="btn" href="index.php">
Back
</a>

</div>

<script src="js/lightbox.js"></script>

<?php include("includes/footer.php"); ?>