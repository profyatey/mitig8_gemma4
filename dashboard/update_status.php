<?php

include("../config/db.php");

if (!isset($_GET['id'])) {
    die("No report selected.");
}

$id = intval($_GET['id']);

/*
|--------------------------------------------------------------------------
| Get Current Report
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Report not found.");
}

$report = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Update Status
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $status = $_POST['status'];

    $update = $conn->prepare(
        "UPDATE reports SET status = ? WHERE id = ?"
    );

    $update->bind_param("si", $status, $id);

    if ($update->execute()) {

        header("Location: report_details.php?id=" . $id);
        exit();

    } else {

        echo "Failed to update report.";

    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Update Status</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="main">

    <h2>Update Report Status</h2>

    <br>

    <p>
        <strong>Report ID:</strong>
        <?= $report['id']; ?>
    </p>

    <br>

    <form method="POST">

        <label>Status</label>

        <br><br>

        <select name="status">

            <option value="Pending"
                <?= ($report['status'] == 'Pending') ? 'selected' : ''; ?>>
                Pending
            </option>

            <option value="Assigned"
                <?= ($report['status'] == 'Assigned') ? 'selected' : ''; ?>>
                Assigned
            </option>

            <option value="Resolved"
                <?= ($report['status'] == 'Resolved') ? 'selected' : ''; ?>>
                Resolved
            </option>

        </select>

        <br><br>

        <button class="btn" type="submit">
            Save Changes
        </button>

        <a class="btn"
           href="report_details.php?id=<?= $report['id']; ?>">
           Cancel
        </a>

    </form>

</div>

</body>

</html>