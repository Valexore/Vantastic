<?php
session_start();
include 'config.php';

$response = ['success' => false, 'message' => ''];

try {
    $vanId = isset($_POST['van_id']) ? $_POST['van_id'] : '';
    $passengerCount = isset($_POST['passenger_count']) ? (int)$_POST['passenger_count'] : 0;
    $boundaryAmount = isset($_POST['boundary_amount']) ? (float)$_POST['boundary_amount'] : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $boundaryId = isset($_POST['boundary_id']) ? (int)$_POST['boundary_id'] : 0;

    // Validation
    if (empty($vanId)) {
        throw new Exception('Van selection is required');
    }

    if ($passengerCount <= 0) {
        throw new Exception('Passenger count must be greater than 0');
    }

    if ($boundaryAmount <= 0) {
        throw new Exception('Boundary amount must be greater than 0');
    }

    if ($boundaryId > 0) {
        // Update existing boundary
        $query = "UPDATE van_boundaries 
                 SET van_id = ?, passenger_count = ?, boundary_amount = ?, notes = ?
                 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sidss', $vanId, $passengerCount, $boundaryAmount, $notes, $boundaryId);
    } else {
        // Create new boundary
        $query = "INSERT INTO van_boundaries (van_id, passenger_count, boundary_amount, notes, boundary_time)
                 VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sids', $vanId, $passengerCount, $boundaryAmount, $notes);
    }

    if (mysqli_stmt_execute($stmt)) {
        $response['success'] = true;
        $response['message'] = $boundaryId > 0 ? 'Boundary updated successfully' : 'Boundary recorded successfully';
    } else {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>