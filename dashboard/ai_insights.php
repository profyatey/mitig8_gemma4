<?php
include("../config/db.php");
include("../config/gemini.php");

include("includes/header.php");
include("includes/sidebar.php");

/*
|--------------------------------------------------------------------------
| 1. Gather Aggregated System Statistics
|--------------------------------------------------------------------------
*/
$totalCount = 0;
$highRisk = 0;
$medRisk = 0;
$lowRisk = 0;
$unreviewed = 0;
$pending = 0;
$resolved = 0;

if ($conn) {
    $totalRes = $conn->query("SELECT COUNT(*) as total FROM reports");
    if ($totalRes) {
        $totalCount = $totalRes->fetch_assoc()['total'] ?? 0;
    }

    $riskRes = $conn->query("SELECT risk_level, COUNT(*) as count FROM reports GROUP BY risk_level");
    $riskArray = [];
    if ($riskRes) {
        while ($row = $riskRes->fetch_assoc()) {
            $riskArray[$row['risk_level']] = $row['count'];
        }
    }
    $highRisk   = $riskArray['High'] ?? 0;
    $medRisk    = $riskArray['Medium'] ?? 0;
    $lowRisk    = $riskArray['Low'] ?? 0;
    $unreviewed = $riskArray['Unreviewed'] ?? 0;

    $statusRes = $conn->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
    $statusArray = [];
    if ($statusRes) {
        while ($row = $statusRes->fetch_assoc()) {
            $statusArray[$row['status']] = $row['count'];
        }
    }
    $pending  = $statusArray['Pending'] ?? 0;
    $resolved = $statusArray['Resolved'] ?? 0;
}

/*
|--------------------------------------------------------------------------
| 2. Deterministic Safety Score — computed in PHP, not by the AI.
|--------------------------------------------------------------------------
*/
function computeSafetyScore($highRisk, $medRisk, $unreviewed, $pending, $resolved, $totalCount) {
    if ($totalCount === 0) {
        return 100;
    }

    $score = 100;
    $score -= $highRisk * 8;
    $score -= $medRisk * 4;
    $score -= $unreviewed * 5;
    $score -= min($pending * 1, 20);

    if ($totalCount > 0) {
        $resolutionRate = $resolved / $totalCount;
        $score += round($resolutionRate * 10);
    }

    return max(0, min(100, (int) round($score)));
}

$safetyScore = computeSafetyScore($highRisk, $medRisk, $unreviewed, $pending, $resolved, $totalCount);

if ($safetyScore >= 80) {
    $scoreColor = '#16a34a'; $scoreLabel = 'Stable';
} elseif ($safetyScore >= 60) {
    $scoreColor = '#f59e0b'; $scoreLabel = 'Elevated Concern';
} elseif ($safetyScore >= 40) {
    $scoreColor = '#f97316'; $scoreLabel = 'High Concern';
} else {
    $scoreColor = '#ef4444'; $scoreLabel = 'Critical';
}

/*
|--------------------------------------------------------------------------
| 3. Pull the most urgent open reports as real context for the AI
|--------------------------------------------------------------------------
*/
$priorityReports = [];
$priorityQuery = $conn->query("
    SELECT id, description, risk_level, latitude, longitude 
    FROM reports 
    WHERE status != 'Resolved' AND risk_level IN ('High', 'Unreviewed')
    ORDER BY FIELD(risk_level, 'High', 'Unreviewed'), created_at DESC
    LIMIT 5
");
if ($priorityQuery) {
    while ($row = $priorityQuery->fetch_assoc()) {
        $priorityReports[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| 4. Cache check — only call the AI if the underlying data has actually
|    changed since the last generated narrative, or a manual refresh
|    was requested.
|--------------------------------------------------------------------------
*/
$dataFingerprint = md5(json_encode([
    $totalCount, $highRisk, $medRisk, $lowRisk, $unreviewed, $pending, $resolved,
    array_column($priorityReports, 'id')
]));

$forceRefresh = isset($_GET['refresh']);
$aiNarrative = null;
$generatedAt = null;

if (!$forceRefresh) {
    $cacheStmt = $conn->prepare("SELECT narrative, generated_at FROM ai_insight_cache WHERE data_hash = ?");
    $cacheStmt->bind_param("s", $dataFingerprint);
    $cacheStmt->execute();
    $cacheResult = $cacheStmt->get_result();
    if ($cacheResult->num_rows > 0) {
        $cached = $cacheResult->fetch_assoc();
        $aiNarrative = $cached['narrative'];
        $generatedAt = $cached['generated_at'];
    }
    $cacheStmt->close();
}

/*
|--------------------------------------------------------------------------
| 5. Generate a fresh narrative only if nothing cached for this exact
|    data state — via Google's Gemini API (Gemma 4).
|--------------------------------------------------------------------------
*/
if ($aiNarrative === null && $totalCount > 0) {

    if (!defined('GEMINI_API_KEY') || !defined('GEMINI_MODEL')) {
        $aiNarrative = "Configuration error: GEMINI_API_KEY / GEMINI_MODEL not defined in config/gemini.php.";
    } else {

        $reportContext = "";
        foreach ($priorityReports as $r) {
            $reportContext .= "- Report #{$r['id']} ({$r['risk_level']}): " . substr($r['description'], 0, 150) . "\n";
        }
        if (empty($reportContext)) {
            $reportContext = "(No open High-risk or Unreviewed reports currently.)";
        }

        $prompt = "You are writing a short operational brief for NADMO flood response staff in Ghana. "
            . "Given this data, write a 2-4 sentence executive summary in plain language — no scores, "
            . "you are not computing any score, just describing the situation and what needs attention.\n\n"
            . "STATS:\n"
            . "- Total reports: $totalCount\n"
            . "- High risk (open): $highRisk\n"
            . "- Medium risk (open): $medRisk\n"
            . "- Low risk (open): $lowRisk\n"
            . "- Unreviewed (AI couldn't assess): $unreviewed\n"
            . "- Pending dispatch: $pending\n"
            . "- Resolved: $resolved\n\n"
            . "PRIORITY OPEN REPORTS:\n{$reportContext}\n"
            . "Write your 2-4 sentence summary.";

        $payload = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [["text" => $prompt]]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.4,
                "maxOutputTokens" => 400,
                "response_mime_type" => "application/json",
                "response_schema" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "narrative" => ["type" => "STRING"]
                    ],
                    "required" => ["narrative"]
                ]
            ]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/"
            . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_TIMEOUT => 45,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200 || !$response) {
            error_log("Gemini insight call failed: HTTP $httpCode | $curlError");
            $aiNarrative = "AI narrative unavailable right now — showing computed statistics only. Try refreshing shortly.";
        } else {
            $decoded = json_decode($response, true);
            $rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $clean = preg_replace('/```json|```/', '', $rawText);
            $clean = trim($clean);
            if (strpos($clean, '{') !== 0 && preg_match('/\{.*\}/s', $clean, $m)) {
                $clean = $m[0];
            }
            $parsed = json_decode($clean, true);

            $aiNarrative = (json_last_error() === JSON_ERROR_NONE && !empty($parsed['narrative']))
                ? $parsed['narrative']
                : "AI response couldn't be parsed — showing computed statistics only.";
        }

        // Cache it — keyed by data fingerprint, so identical data never re-triggers a call
        $insertStmt = $conn->prepare("
            INSERT INTO ai_insight_cache (data_hash, narrative, generated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE narrative = VALUES(narrative), generated_at = NOW()
        ");
        $insertStmt->bind_param("ss", $dataFingerprint, $aiNarrative);
        $insertStmt->execute();
        $insertStmt->close();
        $generatedAt = date('Y-m-d H:i:s');
    }
} elseif ($aiNarrative === null) {
    $aiNarrative = "No reports in the system yet to summarize.";
}
?>

<div class="main" style="padding: 20px; font-family: 'Segoe UI', Arial, sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: #1e293b; margin: 0;">🤖 AI Operational Insights</h2>
        <a href="?refresh=1" style="background: #0d6e5c; color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; text-decoration: none; display: inline-block;">
            🔄 Refresh Live Assessment
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid <?= $scoreColor ?>;">
            <div style="color: #64748b; font-size: 14px; font-weight: 600; text-transform: uppercase;">District Safety Score</div>
            <div style="font-size: 32px; font-weight: bold; color: <?= $scoreColor ?>; margin-top: 5px;"><?= $safetyScore ?>/100</div>
            <div style="font-size: 13px; color: <?= $scoreColor ?>; font-weight: 600;"><?= $scoreLabel ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid #ef4444;">
            <div style="color: #64748b; font-size: 14px; font-weight: 600; text-transform: uppercase;">Open High Risk</div>
            <div style="font-size: 32px; font-weight: bold; color: #ef4444; margin-top: 5px;"><?= $highRisk ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid #8b5cf6;">
            <div style="color: #64748b; font-size: 14px; font-weight: 600; text-transform: uppercase;">Unreviewed</div>
            <div style="font-size: 32px; font-weight: bold; color: #8b5cf6; margin-top: 5px;"><?= $unreviewed ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid #f59e0b;">
            <div style="color: #64748b; font-size: 14px; font-weight: 600; text-transform: uppercase;">Pending Dispatch</div>
            <div style="font-size: 32px; font-weight: bold; color: #f59e0b; margin-top: 5px;"><?= $pending ?></div>
        </div>
    </div>

    <div style="background: #ffffff; padding: 35px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px;">
            <div style="background: #eff6ff; padding: 12px; border-radius: 50%; margin-right: 15px; font-size: 24px;">🧠</div>
            <div>
                <h4 style="margin: 0; color: #1e293b; font-size: 20px; font-weight: 700;">Situation Summary</h4>
                <?php if ($generatedAt): ?>
                    <small style="color: #94a3b8; font-size: 13px;">Generated <?= date('M d, Y H:i', strtotime($generatedAt)) ?><?= $forceRefresh ? ' (just refreshed)' : ' (cached)' ?></small>
                <?php endif; ?>
            </div>
        </div>
        <p style="font-size: 15px; color: #334155; line-height: 1.7;"><?= nl2br(htmlspecialchars($aiNarrative)) ?></p>

        <?php if (!empty($priorityReports)): ?>
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
            <h5 style="color: #1e293b; margin-bottom: 10px;">Priority Reports Referenced</h5>
            <?php foreach ($priorityReports as $r): ?>
                <div style="padding: 8px 0; font-size: 14px; color: #475569;">
                    <a href="report_details.php?id=<?= $r['id'] ?>" style="color: #0d6e5c; font-weight: 600; text-decoration: none;">#<?= $r['id'] ?></a>
                    — <?= htmlspecialchars(substr($r['description'], 0, 100)) ?> <span style="color:#94a3b8;">(<?= htmlspecialchars($r['risk_level']) ?>)</span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include("includes/footer.php"); ?>