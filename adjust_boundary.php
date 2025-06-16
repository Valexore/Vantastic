<?php
session_start();
include 'config.php';

// Verify CSRF token
if (!isset($_POST['csrf_token'])) {
    die(json_encode(['success' => false, 'message' => 'CSRF token missing']));
}

// Verify user is manager
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user || $user['role'] != 'manager') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Get input data - convert to integers
$van_id = (int)$_POST['van_id'];
$action = $_POST['action']; // No need to escape since we validate against specific values
$amount = (int)$_POST['amount']; // Force integer conversion
$today = date('Y-m-d');

// Validate amount - must be positive integer
if ($amount <= 0) {
    die(json_encode(['success' => false, 'message' => 'Count must be a positive whole number']));
}

// Check if van exists
$van_query = "SELECT * FROM vans WHERE id = $van_id";
$van_result = mysqli_query($conn, $van_query);
if (mysqli_num_rows($van_result) === 0) {
    die(json_encode(['success' => false, 'message' => 'Van not found']));
}

$van = mysqli_fetch_assoc($van_result);

// Check if boundary is for today or needs to be reset
if (!isset($van['boundary_date']) || $van['boundary_date'] != $today) {
    // Reset for new day
    $reset_query = "UPDATE vans SET current_boundary = 0, boundary_count = 0, boundary_date = '$today' WHERE id = $van_id";
    mysqli_query($conn, $reset_query);
    $van['current_boundary'] = 0;
    $van['boundary_count'] = 0;
}

// Calculate new boundary - using integers
$new_boundary = (int)$van['current_boundary'];
$new_count = (int)$van['boundary_count'];

if ($action === 'increment') {
    $new_boundary += $amount;
    $new_count += 1;
} elseif ($action === 'decrement') {
    $new_boundary = max(0, $new_boundary - $amount);
} else {
    die(json_encode(['success' => false, 'message' => 'Invalid action']));
}

// Update van boundary
$update_query = "UPDATE vans SET 
    current_boundary = $new_boundary, 
    boundary_count = $new_count,
    boundary_date = '$today'
    WHERE id = $van_id";

if (mysqli_query($conn, $update_query)) {
    // Record boundary transaction (optional - only if you need to track history)
    $insert_query = "INSERT INTO van_boundaries (van_id, boundary_amount, passenger_count, boundary_time)
                     VALUES ($van_id, $amount, 1, NOW())";
    mysqli_query($conn, $insert_query);
    
    echo json_encode([
        'success' => true,
        'message' => 'Boundary count updated successfully',
        'new_boundary' => $new_boundary, // already an integer
        'boundary_count' => $new_count    // already an integer
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating boundary count: ' . mysqli_error($conn)
    ]);
}
?>