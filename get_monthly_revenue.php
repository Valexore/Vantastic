<?php
include 'config.php';

// Get current year
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$previousYear = $currentYear - 1;

// Query to get monthly revenue for current year
$queryCurrentYear = "SELECT 
    MONTH(travel_date) as month, 
    SUM(total_amount) as revenue
FROM tickets
WHERE YEAR(travel_date) = $currentYear
    AND status = 'completed'
GROUP BY MONTH(travel_date)
ORDER BY month";

// Query to get monthly revenue for previous year
$queryPreviousYear = "SELECT 
    MONTH(travel_date) as month, 
    SUM(total_amount) as revenue
FROM tickets
WHERE YEAR(travel_date) = $previousYear
    AND status = 'completed'
GROUP BY MONTH(travel_date)
ORDER BY month";

$resultCurrentYear = mysqli_query($conn, $queryCurrentYear);
$resultPreviousYear = mysqli_query($conn, $queryPreviousYear);

$data = [
    'currentYear' => [],
    'previousYear' => []
];

if ($resultCurrentYear) {
    while ($row = mysqli_fetch_assoc($resultCurrentYear)) {
        $data['currentYear'][] = [
            'month' => (int)$row['month'],
            'revenue' => (float)$row['revenue']
        ];
    }
}

if ($resultPreviousYear) {
    while ($row = mysqli_fetch_assoc($resultPreviousYear)) {
        $data['previousYear'][] = [
            'month' => (int)$row['month'],
            'revenue' => (float)$row['revenue']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>