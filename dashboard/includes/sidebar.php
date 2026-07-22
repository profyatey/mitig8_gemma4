<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

    <h2>🌊 Mitig8</h2>

    <ul>

        <li>
            <a class="<?= ($currentPage == 'index.php') ? 'active' : ''; ?>"
               href="index.php">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a class="<?= ($currentPage == 'reports.php') ? 'active' : ''; ?>"
               href="reports.php">
                📄 Reports
            </a>
        </li>

        <li>
            <a class="<?= ($currentPage == 'map.php') ? 'active' : ''; ?>"
               href="map.php">
                🗺 Flood Map
            </a>
        </li>

        <li>
            <a class="<?= ($currentPage == 'analytics.php') ? 'active' : ''; ?>"
               href="analytics.php">
                📊 Analytics
            </a>
        </li>

        <li>
            <a class="<?= ($currentPage == 'ai.php') ? 'active' : ''; ?>"
               href="ai_insights.php">
                🤖 AI Insights
            </a>
        </li>

        <li>
            <a class="<?= ($currentPage == 'settings.php') ? 'active' : ''; ?>"
               href="#">
                ⚙ Settings
            </a>
        </li>

    </ul>

</div>