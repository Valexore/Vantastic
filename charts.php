<?php
session_start();

// Verify login and manager role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: index.php");
    exit();
}

// Database connection
include 'config.php';

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to get chart data
function getChartData($conn, $chartType, $startDate = null, $endDate = null) {
    $data = [];
    
    // Set default date range if not provided
    if (!$startDate) $startDate = date('Y-m-d', strtotime('-30 days'));
    if (!$endDate) $endDate = date('Y-m-d');
    
    switch ($chartType) {
        case 'destinationsChart':
            // Top destinations by ticket count
            $query = "SELECT d.name, COUNT(t.id) as ticket_count 
                      FROM tickets t
                      JOIN destinations d ON t.destination_id = d.id
                      WHERE t.travel_date BETWEEN ? AND ?
                      GROUP BY d.name
                      ORDER BY ticket_count DESC
                      LIMIT 10";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $data['labels'][] = $row['name'];
                $data['values'][] = (int)$row['ticket_count'];
            }
            break;
            
        case 'terminalChart':
            // Terminal performance by revenue
            $query = "SELECT ter.name, SUM(t.total_amount) as revenue
                      FROM tickets t
                      JOIN terminals ter ON t.terminal_id = ter.id
                      WHERE t.status = 'completed' AND t.travel_date BETWEEN ? AND ?
                      GROUP BY ter.name
                      ORDER BY revenue DESC";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $data['labels'][] = $row['name'];
                $data['values'][] = (float)$row['revenue'];
            }
            break;
            
        case 'dailyTrendChart':
            // Daily ticket sales trend
            $query = "SELECT DATE(travel_date) as day, COUNT(id) as ticket_count
                      FROM tickets
                      WHERE travel_date BETWEEN ? AND ?
                      GROUP BY day
                      ORDER BY day";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $data['labels'][] = date('M j', strtotime($row['day']));
                $data['values'][] = (int)$row['ticket_count'];
            }
            break;
            
        case 'statusChart':
            // Ticket status distribution
            $query = "SELECT status, COUNT(id) as count
                      FROM tickets
                      WHERE travel_date BETWEEN ? AND ?
                      GROUP BY status";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $statusLabels = ['completed', 'upcoming', 'cancelled'];
            $statusColors = ['#4BC0C0', '#36A2EB', '#FF6384'];
            
            foreach ($statusLabels as $label) {
                $data['labels'][] = ucfirst($label);
                $data['colors'][] = $statusColors[array_search($label, $statusLabels)];
                $data['values'][] = 0; // Initialize with 0
            }
            
            while ($row = mysqli_fetch_assoc($result)) {
                $index = array_search($row['status'], $statusLabels);
                if ($index !== false) {
                    $data['values'][$index] = (int)$row['count'];
                }
            }
            break;
            
        case 'salesChart':
            // Daily revenue trend
            $query = "SELECT DATE(travel_date) as day, SUM(total_amount) as revenue
                      FROM tickets
                      WHERE status = 'completed' AND travel_date BETWEEN ? AND ?
                      GROUP BY day
                      ORDER BY day";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $data['labels'][] = date('M j', strtotime($row['day']));
                $data['values'][] = (float)$row['revenue'];
            }
            break;
    }
    
    return $data;
}

// Get chart data based on request
if (isset($_GET['chart'])) {
    $chartType = $_GET['chart'];
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    $data = getChartData($conn, $chartType, $startDate, $endDate);
    
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>