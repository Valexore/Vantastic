<?php
session_start();
include 'config.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$terminal = isset($_GET['terminal']) ? $_GET['terminal'] : 'all';

$query = "SELECT b.*, v.license_plate, v.driver_name, t.name as terminal_name 
          FROM van_boundaries b
          JOIN vans v ON b.van_id = v.id
          JOIN terminals t ON v.terminal_id = t.id
          WHERE DATE(b.boundary_time) = ?";

$params = [$date];
$types = 's';

if ($terminal !== 'all') {
    $query .= " AND v.terminal_id = ?";
    $params[] = $terminal;
    $types .= 'i';
}

if (!empty($search)) {
    $query .= " AND (v.id LIKE ? OR v.license_plate LIKE ? OR v.driver_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

$query .= " ORDER BY b.boundary_time DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['van_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['license_plate']) . '</td>';
        echo '<td>' . htmlspecialchars($row['terminal_name']) . '</td>';
        echo '<td>' . date('M j, Y H:i', strtotime($row['boundary_time'])) . '</td>';
        echo '<td>' . htmlspecialchars($row['driver_name'] ?? '-') . '</td>';
        echo '<td>' . $row['passenger_count'] . '</td>';
        echo '<td>₱' . number_format($row['boundary_amount'], 2) . '</td>';
        echo '<td>';
        echo '<button class="btn btn-sm btn-primary edit-boundary" data-boundary-id="' . $row['id'] . '">';
        echo '<i class="fas fa-edit"></i> Edit';
        echo '</button>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="8">No boundary records found</td></tr>';
}
?>