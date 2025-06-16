<?php
session_start();
include 'config.php';

$month = isset($_POST['month']) ? (int)$_POST['month'] : date('n');
$year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
$terminalId = isset($_POST['terminal_id']) && $_POST['terminal_id'] !== 'all' ? (int)$_POST['terminal_id'] : null;
$format = isset($_POST['format']) ? $_POST['format'] : 'pdf';

// Validate inputs
if ($month < 1 || $month > 12) {
    die('Invalid month');
}

if ($year < 2020 || $year > 2100) {
    die('Invalid year');
}

// Get report data
$startDate = "$year-$month-01";
$endDate = date('Y-m-t', strtotime($startDate));

$query = "SELECT b.*, v.license_plate, v.driver_name, t.name as terminal_name 
          FROM van_boundaries b
          JOIN vans v ON b.van_id = v.id
          JOIN terminals t ON v.terminal_id = t.id
          WHERE DATE(b.boundary_time) BETWEEN ? AND ?";

$params = [$startDate, $endDate];
$types = 'ss';

if ($terminalId) {
    $query .= " AND v.terminal_id = ?";
    $params[] = $terminalId;
    $types .= 'i';
}

$query .= " ORDER BY t.name, v.id, b.boundary_time";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$boundaries = [];
$totalAmount = 0;
$vanCount = 0;
$terminalName = 'All Terminals';

if ($result && mysqli_num_rows($result) > 0) {
    $vansProcessed = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $boundaries[] = $row;
        $totalAmount += $row['boundary_amount'];
        
        if (!in_array($row['van_id'], $vansProcessed)) {
            $vansProcessed[] = $row['van_id'];
            $vanCount++;
        }
        
        if ($terminalId && $terminalName === 'All Terminals') {
            $terminalName = $row['terminal_name'];
        }
    }
}

// Generate report based on format
if ($format === 'pdf') {
    require_once 'tcpdf/tcpdf.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('VanTastic System');
    $pdf->SetAuthor('VanTastic');
    $pdf->SetTitle('Van Boundary Report');
    $pdf->SetSubject('Monthly Van Boundary Report');
    $pdf->AddPage();
    
    // Report header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Van Boundary Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, date('F Y', strtotime($startDate)), 0, 1, 'C');
    $pdf->Cell(0, 10, 'Terminal: ' . $terminalName, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Summary
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, "Total Vans: $vanCount", 0, 1);
    $pdf->Cell(0, 10, "Total Boundary Amount: ₱" . number_format($totalAmount, 2), 0, 1);
    $pdf->Ln(10);
    
    // Detailed report
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Detailed Report', 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, 'Date', 1, 0, 'C');
    $pdf->Cell(30, 7, 'Van ID', 1, 0, 'C');
    $pdf->Cell(40, 7, 'License Plate', 1, 0, 'C');
    $pdf->Cell(30, 7, 'Passengers', 1, 0, 'C');
    $pdf->Cell(40, 7, 'Amount', 1, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    foreach ($boundaries as $boundary) {
        $pdf->Cell(30, 7, date('m/d/Y', strtotime($boundary['boundary_time'])), 1, 0, 'C');
        $pdf->Cell(30, 7, $boundary['van_id'], 1, 0, 'C');
        $pdf->Cell(40, 7, $boundary['license_plate'], 1, 0, 'C');
        $pdf->Cell(30, 7, $boundary['passenger_count'], 1, 0, 'C');
        $pdf->Cell(40, 7, '₱' . number_format($boundary['boundary_amount'], 2), 1, 1, 'R');
    }
    
    $pdf->Output('van_boundary_report_' . date('Y-m') . '.pdf', 'D');
} elseif ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="van_boundary_report_' . date('Y-m') . '.xls"');
    
    echo "Van Boundary Report\n";
    echo date('F Y', strtotime($startDate)) . "\n";
    echo "Terminal: $terminalName\n\n";
    
    echo "Summary\n";
    echo "Total Vans: $vanCount\n";
    echo "Total Boundary Amount: ₱" . number_format($totalAmount, 2) . "\n\n";
    
    echo "Date\tVan ID\tLicense Plate\tPassengers\tAmount\tNotes\n";
    foreach ($boundaries as $boundary) {
        echo date('m/d/Y', strtotime($boundary['boundary_time'])) . "\t";
        echo $boundary['van_id'] . "\t";
        echo $boundary['license_plate'] . "\t";
        echo $boundary['passenger_count'] . "\t";
        echo '₱' . number_format($boundary['boundary_amount'], 2) . "\t";
        echo $boundary['notes'] . "\n";
    }
} else { // CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="van_boundary_report_' . date('Y-m') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Van Boundary Report']);
    fputcsv($output, [date('F Y', strtotime($startDate))]);
    fputcsv($output, ["Terminal: $terminalName"]);
    fputcsv($output, []);
    
    fputcsv($output, ['Summary']);
    fputcsv($output, ["Total Vans: $vanCount"]);
    fputcsv($output, ["Total Boundary Amount: ₱" . number_format($totalAmount, 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['Date', 'Van ID', 'License Plate', 'Passengers', 'Amount', 'Notes']);
    foreach ($boundaries as $boundary) {
        fputcsv($output, [
            date('m/d/Y', strtotime($boundary['boundary_time'])),
            $boundary['van_id'],
            $boundary['license_plate'],
            $boundary['passenger_count'],
            '₱' . number_format($boundary['boundary_amount'], 2),
            $boundary['notes']
        ]);
    }
    
    fclose($output);
}
?>