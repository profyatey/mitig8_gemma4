<?php
ob_clean();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");
include("../config/db.php");
include("../config/gemini.php");

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
    exit;
}

if (
    !isset($_FILES['image']) ||
    !isset($_POST['latitude']) ||
    !isset($_POST['longitude']) ||
    !isset($_POST['description'])
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);
    exit;
}

$user_id = $_POST['user_id'] ?? 1;
$image = $_FILES['image'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];
$description = $_POST['description'];

if (!is_numeric($latitude) || !is_numeric($longitude)) {
    echo json_encode([
        "success" => false,
        "message" => "Latitude and longitude must be valid numbers."
    ]);
    exit;
}

$uploadDir = "../uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$imageName = time() . "_" . basename($image["name"]);
$imagePath = $uploadDir . $imageName;

if (!move_uploaded_file($image["tmp_name"], $imagePath)) {
    echo json_encode([
        "success" => false,
        "message" => "Image upload failed."
    ]);
    exit;
}

// =======================================================
// 🤖 AI ANALYSIS — Gemma 4 (multimodal) via Google Gemini API
// =======================================================

$aiResult = analyzeDrainageImage($imagePath, $description);

$riskLevel   = $aiResult['risk_level'];
$aiReasoning = $aiResult['reasoning'];
$status      = "Pending"; // always starts Pending regardless of AI risk; NADMO triages from there

$stmt = $conn->prepare("
    INSERT INTO reports 
    (user_id, image, latitude, longitude, description, risk_level, status, ai_reasoning) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isddssss",
    $user_id,
    $imageName,
    $latitude,
    $longitude,
    $description,
    $riskLevel,
    $status,
    $aiReasoning
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Success! Image uploaded, saved, and analyzed by AI.",
        "report_id" => $conn->insert_id,
        "ai_analysis" => [
            "risk_level" => $riskLevel,
            "reasoning" => $aiReasoning,
            "debug" => $aiResult['debug'] ?? null // ⚠️ TEMPORARY — remove once issue is confirmed fixed
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();


/**
 * Resizes and re-compresses an image to keep the AI payload small and fast,
 * regardless of what resolution/size the source device produced.
 * Returns the path to a new, smaller JPEG file, or null if the format
 * can't be read by GD (most commonly: HEIC from an iPhone camera).
 */
function prepareImageForAI($sourcePath, $maxDimension = 1280, $quality = 80) {

    $info = @getimagesize($sourcePath);
    if (!$info) {
        return null; // not a readable image (could be HEIC, which GD can't open)
    }

    [$width, $height, $type] = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $srcImg = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $srcImg = imagecreatefromwebp($sourcePath);
            break;
        default:
            return null; // unsupported format (e.g. HEIC) — GD can't decode it
    }

    if (!$srcImg) {
        return null;
    }

    // Only shrink if it's actually bigger than our target — never upscale
    $ratio = min($maxDimension / $width, $maxDimension / $height, 1);
    $newWidth = (int) round($width * $ratio);
    $newHeight = (int) round($height * $ratio);

    $resized = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($resized, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $tmpPath = $sourcePath . "_ai.jpg";
    imagejpeg($resized, $tmpPath, $quality);

    imagedestroy($srcImg);
    imagedestroy($resized);

    return $tmpPath;
}

/**
 * Sends the uploaded drainage image + description to Gemma 4 (multimodal)
 * via Google's Gemini API, and returns a normalized
 * ['risk_level' => 'High'|'Medium'|'Low'|'Unreviewed', 'reasoning' => string].
 *
 * Fails soft: if the API call errors out, times out, or can't be parsed,
 * returns "Unreviewed" (NOT "Low") so a failed AI call is never mistaken
 * for a genuine low-risk assessment on the dashboard/map.
 */
function analyzeDrainageImage($imagePath, $description) {

    $fallback = [
        "risk_level" => "Unreviewed",
        "reasoning"  => "AI analysis unavailable at time of submission — needs manual review."
    ];

    if (!file_exists($imagePath)) {
        return $fallback;
    }

    $aiImagePath = prepareImageForAI($imagePath);

    if ($aiImagePath === null) {
        error_log("Image format unsupported by GD (likely HEIC): $imagePath");
        $fallback['reasoning'] = "Photo format not supported for AI analysis (likely HEIC from iPhone camera). Please switch iPhone Camera settings to 'Most Compatible' or select from gallery, then resubmit — needs manual review meanwhile.";
        return $fallback;
    }

    $base64Image = base64_encode(file_get_contents($aiImagePath));
    @unlink($aiImagePath);

    // Calibration is the actual fix for "gutters rated High": the model needs
    // explicit permission to say Low with confidence, and an anchor for what
    // a healthy drain looks like — otherwise a safety-framed prompt biases upward.
    $descriptionText = trim($description) !== '' ? $description : "(no description provided)";

    $prompt = "You are assisting a flood mitigation triage system for NADMO in Ghana. "
        . "Analyze this citizen-submitted photo of a drainage system. "
        . "Base your assessment primarily on what is visible in the image. "
        . "Treat the citizen's description as supplementary context only — do not "
        . "inflate the risk level just because the description sounds urgent; "
        . "citizens may exaggerate. Citizen's description: \"{$descriptionText}\".\n\n"
        . "Risk level guide:\n"
        . "- Low: drain is clear, water is flowing or absent, no significant debris or damage. "
        . "Respond Low confidently — do not default upward out of caution.\n"
        . "- Medium: partial blockage, some standing water, or moderate debris that could "
        . "worsen but isn't an immediate hazard.\n"
        . "- High: severe blockage, significant standing water, sewage overflow, or clear "
        . "structural damage posing an immediate flood risk.\n\n"
        . "Respond with your risk_level and a 1-2 sentence reasoning grounded in what you "
        . "actually see in the image.";

    $payload = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $prompt],
                    ["inline_data" => [
                        "mime_type" => "image/jpeg",
                        "data" => $base64Image
                    ]]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.2,
            "topP" => 0.7,
            "maxOutputTokens" => 300,
            "response_mime_type" => "application/json",
            "response_schema" => [
                "type" => "OBJECT",
                "properties" => [
                    "risk_level" => [
                        "type" => "STRING",
                        "enum" => ["Low", "Medium", "High"]
                    ],
                    "reasoning" => ["type" => "STRING"]
                ],
                "required" => ["risk_level", "reasoning"]
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
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 45,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 200 || !$response) {
        error_log("Gemini Gemma 4 call failed: HTTP $httpCode | $curlError | $response");
        $fallback['debug'] = [ // ⚠️ TEMPORARY — remove once confirmed fixed
            "http_code" => $httpCode,
            "curl_error" => $curlError,
            "raw_response" => substr($response ?? '', 0, 500)
        ];
        return $fallback;
    }

    $decoded = json_decode($response, true);
    $rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$rawText) {
        error_log("Gemini Gemma 4 response had no content: " . $response);
        return $fallback;
    }

    // response_schema forces valid JSON, but keep the safety net for edge cases
    $clean = preg_replace('/```json|```/', '', $rawText);
    $clean = trim($clean);
    if (strpos($clean, '{') !== 0 && preg_match('/\{.*\}/s', $clean, $m)) {
        $clean = $m[0];
    }

    $parsed = json_decode($clean, true);

    if (
        json_last_error() === JSON_ERROR_NONE &&
        isset($parsed['risk_level']) &&
        in_array($parsed['risk_level'], ['High', 'Medium', 'Low'])
    ) {
        return [
            "risk_level" => $parsed['risk_level'],
            "reasoning"  => $parsed['reasoning'] ?? "No reasoning provided."
        ];
    }

    error_log("Gemini Gemma 4 response wasn't valid JSON: " . $rawText);
    return $fallback;
}