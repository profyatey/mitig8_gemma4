<?php

include("../config/db.php");
include("includes/header.php");
include("includes/sidebar.php");

// Fetch all reports
$query = mysqli_query($conn, "SELECT * FROM reports");

$reports = [];
while($row = mysqli_fetch_assoc($query)){
    $reports[] = $row;
}

?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="main">

    <h2>🗺 Flood Incident Map</h2>

    <div id="map"></div>

</div>

<script>

const reports = <?php echo json_encode($reports); ?>;

// Returns marker styling based on risk_level — colors match the reports.php badges
function getRiskMarkerStyle(risk) {
    switch (risk) {
        case 'High':
            return { color: '#ef4444', emoji: '🌊', label: 'High Risk' };
        case 'Medium':
            return { color: '#f59e0b', emoji: '🌊', label: 'Medium Risk' };
        case 'Low':
            return { color: '#22c55e', emoji: '🌊', label: 'Low Risk' };
        case 'Unreviewed':
            return { color: '#8b5cf6', emoji: '⚠️', label: 'Unreviewed — Needs Manual Check' };
        default:
            return { color: '#64748b', emoji: '🌊', label: risk || 'Unknown' };
    }
}

document.addEventListener("DOMContentLoaded", function(){

    // Create the map
    var map = L.map('map').setView([5.6037, -0.1870], 12);

    // Load OpenStreetMap
    L.tileLayer(
        'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom:19,
            attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }
    ).addTo(map);

    // Draw markers
    reports.forEach(function(report){

        // Ignore reports without valid coordinates
        if (!report.latitude || !report.longitude || isNaN(report.latitude) || isNaN(report.longitude)) {
            return;
        }

        const style = getRiskMarkerStyle(report.risk_level);

        // Color-coded circular icon based on risk level
        var floodIcon = L.divIcon({
            html: '<div style="' +
                    'width: 30px; height: 30px; border-radius: 50%; ' +
                    'background: ' + style.color + '; ' +
                    'display: flex; align-items: center; justify-content: center; ' +
                    'font-size: 16px; border: 2px solid white; ' +
                    'box-shadow: 0px 2px 4px rgba(0,0,0,0.3);">' +
                    style.emoji +
                  '</div>',
            className: 'custom-flood-marker',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -10]
        });

        var marker = L.marker([
            parseFloat(report.latitude),
            parseFloat(report.longitude)
        ], { icon: floodIcon }).addTo(map);

        // Map popup HTML binding with .zoomable-img scaling hooks
        marker.bindPopup(
            "<h3>Report #" + report.id + "</h3>" +
            "<img src='/uploads/" + report.image + "' class='zoomable-img' width='200' style='border-radius:6px;'><br><br>" +
            "<strong>Description:</strong><br>" + report.description + "<br><br>" +
            "<strong>Risk:</strong> <span style='color:" + style.color + "; font-weight:600;'>" + style.label + "</span><br>" +
            "<strong>Status:</strong> " + report.status + "<br>" +
            "<strong>Date:</strong> " + report.created_at
        );

    });

    // Refresh the map size context after rendering layout 
    setTimeout(function(){
        map.invalidateSize();
    },400);

});

</script>

<script src="js/lightbox.js"></script>

<?php include("includes/footer.php"); ?>