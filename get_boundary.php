<?php
session_start();
include 'config.php';

$boundaryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT b.*, v.license_plate, v.driver_name, t.name as terminal_name 
          FROM van_boundaries b
          JOIN vans v ON b.van_id = v.id
          JOIN terminals t ON v.terminal_id = t.id
          WHERE b.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $boundaryId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $boundary = mysqli_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'boundary' => $boundary
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Boundary record not found'
    ]);
}
?>