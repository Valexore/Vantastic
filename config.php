<?php
$conn = mysqli_connect('localhost', 'root', '', 'van');
//$conn = mysqli_connect('ssql107.infinityfree.com', 'if0_38995784', 'llPfMC2NHw', 'if0_38995784_van'); 


// Always check connection and enable error reporting
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}




// Function to generate a unique barcode
function generateBarcode($ticket_id) {
    // Generate a unique barcode value (you can use any barcode format you prefer)
    $prefix = 'VT'; // VanTastic prefix
    $unique_id = uniqid();
    $hash = substr(md5($ticket_id . $unique_id), 0, 8);
    return $prefix . strtoupper($hash);
}



// Set charset to avoid encoding issues
mysqli_set_charset($conn, 'utf8mb4');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>




