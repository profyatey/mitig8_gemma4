<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Drainage Blockage</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background-color: #f3f6fa;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .app-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
            padding: 24px;
            box-sizing: border-box;
        }

        h2 {
            color: #0d6e5c;
            margin-top: 0;
            text-align: center;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        /* Camera Preview Box Style */
        .image-picker-box {
            width: 100%;
            height: 200px;
            border: 2px dashed #0d6e5c;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f3f6fa;
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }

        .image-picker-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .image-picker-box span {
            color: #0d6e5c;
            font-size: 14px;
            text-align: center;
            padding: 0 10px;
        }

        textarea {
            width: 100%;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            box-sizing: border-box;
            resize: none;
            font-family: inherit;
        }

        textarea:focus {
            border-color: #0d6e5c;
            outline: none;
        }

        .btn-submit {
            width: 100%;
            background: #0d6e5c;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: .3s;
        }

        .btn-submit:hover { background: #095445; }
        .btn-submit:disabled { background: #94a3b8; cursor: not-allowed; }

        /* Modal Popup */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-content {
            background: white;
            padding: 24px;
            border-radius: 10px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }

        .modal h3 { color: #0d6e5c; margin-top: 0; }

        .reasoning-text {
            color: #333;
            text-align: left;
            background: #f3f6fa;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #0d6e5c;
            margin-top: 10px;
            font-size: 14px;
        }

        .btn-close {
            background: #0d6e5c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
            transition: .3s;
        }

        .btn-close:hover { background: #095445; }
    </style>
</head>
<body>

<div class="app-container">
    <h2>🌊 Report Flood Risk</h2>
    
    <form id="reportForm">
        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">

        <div class="form-group">
            <label>Capture / Upload Image</label>
            <input type="file" id="imageInput" name="image" accept="image/*" style="display: none;" required>
            <div class="image-picker-box" id="dropzone" onclick="document.getElementById('imageInput').click()">
                <span id="uploadPlaceholder">📸 Tap to take picture or select gallery file</span>
                <img id="imagePreview" alt="Preview">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Situation Description</label>
            <textarea id="description" name="description" placeholder="Describe the drainage blockage or issue here..." required></textarea>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">Submit Report</button>
    </form>
</div>

<div class="modal" id="successModal">
    <div class="modal-content">
        <h3>✅ Report Submitted Successfully</h3>
        <p class="reasoning-text">
            Thank you. Your report has been received and will be reviewed by NADMO.
        </p>
        <button class="btn-close" onclick="window.location.reload()">Done</button>
    </div>
</div>

<script>
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const form = document.getElementById('reportForm');
    const submitBtn = document.getElementById('submitBtn');

    // Handle instant image selection preview
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    });

    // Capture precise browser-level Geolocation coordinates immediately on submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerText = "Fetching location...";

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    sendDataToServer();
                },
                (error) => {
                    // Fallback Mock data coordinates for local/offline testing profiles
                    console.warn("Location error, utilizing system mock values.");
                    document.getElementById('latitude').value = "5.603717";
                    document.getElementById('longitude').value = "-0.186964";
                    sendDataToServer();
                },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        } else {
            document.getElementById('latitude').value = "5.603717";
            document.getElementById('longitude').value = "-0.186964";
            sendDataToServer();
        }
    });

    // Send the multipart form details down to your active API script natively
    function sendDataToServer() {
        submitBtn.innerText = "Submitting report...";
        
        // Target relative file mapping directly to match folder organization schemas
        const formData = new FormData(form);

        fetch('api/submit_report.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                document.getElementById('successModal').style.display = 'flex';
            } else {
                alert("Upload failed error: " + result.message);
                submitBtn.disabled = false;
                submitBtn.innerText = "Submit Report";
            }
        })
        .catch(error => {
            console.error("Link processing error:", error);
            alert("Network submission failure. Verify endpoint links.");
            submitBtn.disabled = false;
            submitBtn.innerText = "Submit Report";
        });
    }
</script>

</body>
</html>