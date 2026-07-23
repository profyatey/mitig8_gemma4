<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Drainage Blockage</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: #f8fafc;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .app-container {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            padding: 32px;
            border: 1px solid #e2e8f0;
        }

        h2 {
            color: #0f172a;
            margin-top: 0;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .subtitle {
            color: #64748b;
            text-align: center;
            font-size: 14px;
            margin-bottom: 28px;
        }

        .form-group { margin-bottom: 24px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        /* Location Indicator Box */
        .location-badge {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #475569;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .location-badge .status-dot {
            width: 8px;
            height: 8px;
            background: #cbd5e1;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .location-badge.success .status-dot {
            background: #16a34a;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.15);
        }

        .location-badge.error .status-dot {
            background: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.15);
        }

        .location-badge button {
            margin-left: auto;
            background: none;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #2563eb;
            cursor: pointer;
        }

        /* Image capture buttons */
        .image-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .image-action-btn {
            flex: 1;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 10px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            text-align: center;
        }

        .image-action-btn:hover, .image-action-btn:active {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
        }

        .image-preview-box {
            width: 100%;
            height: 180px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            display: none;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            overflow: hidden;
        }

        .image-preview-box.has-image {
            display: flex;
            border-style: solid;
        }

        .image-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        textarea {
            width: 100%;
            height: 110px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 14px;
            box-sizing: border-box;
            resize: none;
            font-family: inherit;
            font-size: 14px;
            color: #334155;
            transition: all 0.15s ease;
        }

        textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .btn-submit {
            width: 100%;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            transition: all 0.2s ease;
        }

        .btn-submit:hover { background: #1d4ed8; }
        .btn-submit:disabled { background: #94a3b8; box-shadow: none; cursor: not-allowed; }

        /* Modal Popup Styling */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal h3 { color: #0f172a; margin-top: 0; font-size: 20px; font-weight: 700; }

        .reasoning-text {
            color: #334155;
            text-align: left;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #16a34a;
            margin-top: 16px;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn-close {
            background: #0f172a;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 24px;
            font-size: 14px;
            transition: background 0.15s ease;
        }

        .btn-close:hover { background: #1e293b; }
    </style>
</head>
<body>

<div class="app-container">
    <h2>🌊 Report Flood Risk</h2>
    <p class="subtitle">Provide photo evidence to trigger mitigation responses.</p>

    <form id="reportForm">
        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">

        <div class="location-badge" id="locationBadge">
            <span class="status-dot"></span>
            <span id="locationStatusText">Acquiring device coordinates...</span>
        </div>

        <div class="form-group">
            <label>Capture / Upload Image</label>

            <!-- Two separate inputs: one forces the camera app, the other opens the gallery.
                 Mobile browsers only show a camera option in the OS chooser over HTTPS. -->
            <input type="file" id="cameraInput" name="image" accept="image/*" capture="environment" style="display: none;">
            <input type="file" id="galleryInput" accept="image/*" style="display: none;">

            <div class="image-actions">
                <button type="button" class="image-action-btn" id="takePhotoBtn">📸 Take Photo</button>
                <button type="button" class="image-action-btn" id="chooseGalleryBtn">🖼️ Choose from Gallery</button>
            </div>

            <div class="image-preview-box" id="previewBox">
                <img id="imagePreview" alt="Preview">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Situation Description</label>
            <textarea id="description" name="description" placeholder="Describe the drainage blockage or issue context here..." required></textarea>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">Submit Report</button>
    </form>
</div>

<div class="modal" id="successModal">
    <div class="modal-content">
        <h3>🎉 Report Filed Successfully</h3>
        <div class="reasoning-text" id="modalText">
            Thank you. Your report has been dispatched to operations teams for automated analysis and logging.
        </div>
        <button class="btn-close" onclick="window.location.reload()">Done</button>
    </div>
</div>

<script>
    const cameraInput = document.getElementById('cameraInput');
    const galleryInput = document.getElementById('galleryInput');
    const takePhotoBtn = document.getElementById('takePhotoBtn');
    const chooseGalleryBtn = document.getElementById('chooseGalleryBtn');
    const imagePreview = document.getElementById('imagePreview');
    const previewBox = document.getElementById('previewBox');
    const form = document.getElementById('reportForm');
    const submitBtn = document.getElementById('submitBtn');
    const locationBadge = document.getElementById('locationBadge');
    const locationStatusText = document.getElementById('locationStatusText');

    let activeImageFile = null;

    // --- Image capture / selection ---
    takePhotoBtn.addEventListener('click', () => cameraInput.click());
    chooseGalleryBtn.addEventListener('click', () => galleryInput.click());

    cameraInput.addEventListener('change', function () {
        if (this.files.length) handleImagePreview(this.files[0]);
    });

    galleryInput.addEventListener('change', function () {
        if (this.files.length) handleImagePreview(this.files[0]);
    });

    function handleImagePreview(file) {
        activeImageFile = file;
        const reader = new FileReader();
        reader.onload = function (e) {
            imagePreview.src = e.target.result;
            previewBox.classList.add('has-image');
        };
        reader.readAsDataURL(file);
    }

    // --- Geolocation, with a manual timeout as a backstop ---
    // Note: navigator.geolocation only works in a secure context (HTTPS, or
    // localhost). On plain HTTP some mobile browsers never invoke either
    // callback, which is why this also sets its own timer as a fallback.
    window.addEventListener('DOMContentLoaded', () => {
        requestLocation();
    });

    function requestLocation() {
        // Clear any leftover retry button from a previous failed attempt
        // so they don't stack up.
        const existingRetry = document.getElementById('locationRetryBtn');
        if (existingRetry) existingRetry.remove();

        locationBadge.classList.remove('success', 'error');
        locationStatusText.innerText = "Acquiring device coordinates...";

        if (!window.isSecureContext) {
            showLocationError("Page isn't served over HTTPS — location & camera are blocked by the browser.");
            return;
        }

        if (!navigator.geolocation) {
            showLocationError("Geolocation isn't supported on this device.");
            return;
        }

        // First attempt: high accuracy (GPS), generous timeout to allow for
        // a cold GPS fix (can take 15-30s on a fresh permission grant).
        tryGetPosition(
            { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
            (error) => {
                // Fallback: low accuracy (network/cell-based), which returns
                // fast even when GPS hasn't locked yet.
                tryGetPosition(
                    { enableHighAccuracy: false, timeout: 8000, maximumAge: 60000 },
                    (fallbackError) => {
                        showLocationError(fallbackError && fallbackError.message ? fallbackError.message : "Couldn't get location.");
                    }
                );
            }
        );
    }

    function tryGetPosition(options, onFail) {
        let settled = false;
        const backstop = setTimeout(() => {
            if (!settled) {
                settled = true;
                onFail({ message: "Location request timed out." });
            }
        }, options.timeout + 2000);

        navigator.geolocation.getCurrentPosition(
            (position) => {
                if (settled) return;
                settled = true;
                clearTimeout(backstop);
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
                locationBadge.classList.add('success');
                locationStatusText.innerText = "Location locked (" + position.coords.latitude.toFixed(4) + ", " + position.coords.longitude.toFixed(4) + ")";
            },
            (error) => {
                if (settled) return;
                settled = true;
                clearTimeout(backstop);
                onFail(error);
            },
            options
        );
    }

    function showLocationError(message) {
        locationBadge.classList.add('error');
        locationStatusText.innerText = message;

        // Reuse the existing retry button if one is already there instead
        // of creating a duplicate.
        let retryBtn = document.getElementById('locationRetryBtn');
        if (!retryBtn) {
            retryBtn = document.createElement('button');
            retryBtn.type = 'button';
            retryBtn.id = 'locationRetryBtn';
            retryBtn.innerText = 'Retry';
            retryBtn.onclick = requestLocation;
            locationBadge.appendChild(retryBtn);
        }
    }

    // --- Submit ---
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!activeImageFile) {
            alert("Please take or choose a photo first.");
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerText = "Submitting report data...";

        const formData = new FormData();
        formData.append('image', activeImageFile);
        formData.append('description', document.getElementById('description').value);
        formData.append('latitude', document.getElementById('latitude').value);
        formData.append('longitude', document.getElementById('longitude').value);
//==================================================================================================
        // For Offline version via ollama change fetch('api/submit_report.php') To 
        //'api/submit_report_ollama.php'
//==================================================================================================
        fetch('api/submit_report.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (result.risk_level) {
                        document.getElementById('modalText').innerHTML = `<strong>AI Assessment Complete:</strong> Found <strong>${result.risk_level} Risk</strong> conditions.<br><br>The management tracking dashboard logs have updated successfully.`;
                    }
                    document.getElementById('successModal').style.display = 'flex';
                } else {
                    alert("Upload assertion exception: " + result.message);
                    resetButtonState();
                }
            })
            .catch(error => {
                console.error("Network sync issues:", error);
                alert("Network submission failure. Verify live endpoint addresses.");
                resetButtonState();
            });
    });

    function resetButtonState() {
        submitBtn.disabled = false;
        submitBtn.innerText = "Submit Report";
    }
</script>

</body>
</html>
