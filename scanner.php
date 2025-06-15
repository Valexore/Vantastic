<?php
session_start();

// Verify manager login - only managers can access
if (!isset($_SESSION['user_id'])) { 
    header("Location: manager.php");
    exit();
}

include 'config.php';

// Get current manager's details
$manager_query = "SELECT full_name FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $manager_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$manager_result = mysqli_stmt_get_result($stmt);
$manager = mysqli_fetch_assoc($manager_result);
$manager_name = $manager['full_name'] ?? 'Manager';

// Handle barcode scan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $barcode = mysqli_real_escape_string($conn, $_POST['barcode']);
        
        // Check if barcode exists with prepared statement
        $query = "SELECT tb.*, t.user_id, t.total_amount, t.travel_date, t.travel_time,
                         u.full_name as passenger_name,
                         sc.full_name as scanned_by_name
                  FROM ticket_barcodes tb
                  JOIN tickets t ON tb.ticket_id = t.id
                  JOIN users u ON t.user_id = u.id
                  LEFT JOIN users sc ON tb.scanned_by = sc.id
                  WHERE tb.barcode_value = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $barcode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $ticket = mysqli_fetch_assoc($result);
            
            if ($ticket['scan_status'] == 'unscanned') {
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Mark as scanned and fully paid
                    $update_query = "UPDATE ticket_barcodes 
                                    SET scan_status = 'scanned', 
                                        scan_time = NOW(), 
                                        scanned_by = ?
                                    WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $ticket['id']);
                    mysqli_stmt_execute($stmt);
                    
                    // Mark ticket as fully paid
                    $ticket_update = "UPDATE tickets 
                                     SET fully_paid = 1 
                                     WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $ticket_update);
                    mysqli_stmt_bind_param($stmt, "i", $ticket['ticket_id']);
                    mysqli_stmt_execute($stmt);
                    
                    mysqli_commit($conn);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Ticket successfully scanned and marked as paid!',
                        'ticket' => $ticket,
                        'scanned_by' => $manager_name
                    ];
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $response['message'] = 'Database error: ' . $e->getMessage();
                }
            } else {
                $response['message'] = 'This ticket was already scanned on ' . $ticket['scan_time'] . 
                                     ' by ' . ($ticket['scanned_by_name'] ? $ticket['scanned_by_name'] : 'unknown');
            }
        } else {
            $response['message'] = 'Invalid barcode. Ticket not found.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error processing request: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Scanner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .scanner-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h1 {
            color: #0E386A;
            text-align: center;
            margin-bottom: 30px;
        }

        .manager-info {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f0f8ff;
            border-radius: 5px;
            border: 1px solid #d0e3ff;
        }

        .manager-info p {
            margin: 5px 0;
            font-weight: bold;
        }

        .scanner-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        #scanner-video {
            width: 100%;
            max-width: 500px;
            height: auto;
            border: 3px solid #0E386A;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #000;
        }

        #scan-result {
            width: 100%;
            max-width: 500px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
        }

        .btn {
            background-color: #0E386A;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin: 5px;
        }

        .btn:hover {
            background-color: #0a2a52;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-back {
            background-color: #6c757d;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        .result-section {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }

        .success {
            background-color: #e6f7ee;
            border: 1px solid #28a745;
            color: #28a745;
        }

        .error {
            background-color: #f8d7da;
            border: 1px solid #dc3545;
            color: #dc3545;
        }

        .ticket-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #eee;
        }

        .ticket-info h4 {
            margin-top: 0;
            color: #0E386A;
        }

        .ticket-info p {
            margin: 8px 0;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="scanner-container">
        <h1><i class="fas fa-qrcode"></i> Ticket Scanner</h1>
        
        <div class="manager-info">
            <p>Logged in as: <?php echo htmlspecialchars($manager_name); ?></p>
            <p>Role: Manager</p>
        </div>
        
        <div class="scanner-section">
            <video id="scanner-video" playsinline></video>
            <input type="text" id="scan-result" placeholder="Scan barcode or enter manually">
            <button class="btn" id="scan-btn"><i class="fas fa-camera"></i> Start Scanner</button>
            <button class="btn" id="manual-submit" style="display:none;"><i class="fas fa-check"></i> Submit</button>
            <a href="manager.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Manager</a>
        </div>
        
        <div id="result-message" class="result-section">
            <h3 id="result-title"></h3>
            <p id="result-text"></p>
            <div id="ticket-details" class="ticket-info" style="display:none;">
                <!-- Ticket details will be displayed here -->
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('scanner-video');
            const scanResult = document.getElementById('scan-result');
            const scanBtn = document.getElementById('scan-btn');
            const manualSubmit = document.getElementById('manual-submit');
            const resultMessage = document.getElementById('result-message');
            const resultTitle = document.getElementById('result-title');
            const resultText = document.getElementById('result-text');
            const ticketDetails = document.getElementById('ticket-details');
            
            let scannerActive = false;
            let stream = null;
            let scanning = false;
            
            scanBtn.addEventListener('click', function() {
                if (scannerActive) {
                    stopScanner();
                } else {
                    startScanner();
                }
            });
            
            manualSubmit.addEventListener('click', function() {
                const barcode = scanResult.value.trim();
                if (barcode) {
                    processBarcode(barcode);
                }
            });
            
            scanResult.addEventListener('input', function() {
                manualSubmit.style.display = this.value.trim() ? 'block' : 'none';
                scanBtn.style.display = this.value.trim() ? 'none' : 'block';
            });
            
            scanResult.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.trim()) {
                    processBarcode(this.value.trim());
                }
            });
            
            function startScanner() {
                scannerActive = true;
                scanBtn.innerHTML = '<i class="fas fa-stop"></i> Stop Scanner';
                scanResult.placeholder = 'Scanning...';
                scanResult.value = '';
                manualSubmit.style.display = 'none';
                resultMessage.style.display = 'none';
                
                navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: "environment",
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                })
                .then(function(s) {
                    stream = s;
                    video.srcObject = stream;
                    video.play()
                        .then(() => {
                            requestAnimationFrame(scanFrame);
                        })
                        .catch(err => {
                            console.error("Video play error: ", err);
                            showResult(false, "Could not start video stream.");
                            stopScanner();
                        });
                })
                .catch(function(err) {
                    console.error("Error accessing camera: ", err);
                    showResult(false, "Camera access denied. Please check permissions.");
                    stopScanner();
                });
            }
            
            function stopScanner() {
                scannerActive = false;
                scanBtn.innerHTML = '<i class="fas fa-camera"></i> Start Scanner';
                scanResult.placeholder = 'Scan barcode or enter manually';
                
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    video.srcObject = null;
                }
            }
            
            function scanFrame() {
                if (!scannerActive) return;
                
                if (video.readyState === video.HAVE_ENOUGH_DATA && !scanning) {
                    scanning = true;
                    
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: "dontInvert",
                    });
                    
                    if (code) {
                        scanResult.value = code.data;
                        processBarcode(code.data);
                        stopScanner();
                    }
                    
                    scanning = false;
                }
                
                requestAnimationFrame(scanFrame);
            }
            
            function processBarcode(barcode) {
                const submitBtn = manualSubmit;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = 'Processing <span class="loading"></span>';
                submitBtn.disabled = true;
                
                fetch('scanner.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'barcode=' + encodeURIComponent(barcode)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showResult(true, data.message, data.ticket, data.scanned_by);
                        scanResult.value = '';
                    } else {
                        showResult(false, data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResult(false, "An error occurred while processing the barcode.");
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
            
            function showResult(success, message, ticket = null, scannedBy = null) {
                resultMessage.className = 'result-section ' + (success ? 'success' : 'error');
                resultTitle.textContent = success ? 'Success!' : 'Error';
                resultText.textContent = message;
                resultMessage.style.display = 'block';
                
                if (success && ticket) {
                    const scanTime = new Date().toLocaleString();
                    const travelTime = new Date(ticket.travel_date + ' ' + ticket.travel_time).toLocaleString();
                    
                    ticketDetails.innerHTML = `
                        <h4>Ticket Details</h4>
                        <p><strong>Ticket ID:</strong> VT${String(ticket.ticket_id).padStart(4, '0')}</p>
                        <p><strong>Passenger:</strong> ${ticket.passenger_name}</p>
                        <p><strong>Amount Paid:</strong> ₱${parseFloat(ticket.total_amount).toFixed(2)}</p>
                        <p><strong>Travel Date:</strong> ${travelTime}</p>
                        <p><strong>Scanned At:</strong> ${scanTime}</p>
                        <p><strong>Scanned By:</strong> ${scannedBy || 'You'}</p>
                    `;
                    ticketDetails.style.display = 'block';
                } else {
                    ticketDetails.style.display = 'none';
                }
                
                resultMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            window.addEventListener('beforeunload', () => {
                stopScanner();
            });
        });
    </script>
</body>
</html>