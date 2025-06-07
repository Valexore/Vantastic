<?php
$conn = mysqli_connect('localhost', 'root', '', 'van');
//$conn = mysqli_connect('ssql107.infinityfree.com', 'if0_38995784', 'llPfMC2NHw', 'if0_38995784_van'); 


// Always check connection and enable error reporting
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}



// Set charset to avoid encoding issues
mysqli_set_charset($conn, 'utf8mb4');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>




