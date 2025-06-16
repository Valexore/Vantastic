<?php
session_start();


if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrfToken()
{
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid CSRF token";
    header("Location: manager.php#destination-management");
    exit();
  }
}
// Verify login
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

// Database connection
include 'config.php';

// Check database connection
if (!$conn) {
  die("Database connection failed: " . mysqli_connect_error());
}

// Get user data and verify role
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user || $user['role'] != 'manager') {
  session_destroy();
  header("Location: index.php");
  exit();
}

// Handle logout
if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: index.php");
  exit();
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
///$$$$$$$$ /$$$$$$  /$$$$$$  /$$   /$$ /$$$$$$$$ /$$$$$$$$       /$$$$$$$$ /$$$$$$ /$$      
//|__  $$__/|_  $$_/ /$$__  $$| $$  /$$/| $$_____/|__  $$__/      | $$_____/|_  $$_/| $$      
//   | $$     | $$  | $$  \__/| $$ /$$/ | $$         | $$         | $$        | $$  | $$      
//   | $$     | $$  | $$      | $$$$$/  | $$$$$      | $$         | $$$$$     | $$  | $$      
//   | $$     | $$  | $$      | $$  $$  | $$__/      | $$         | $$__/     | $$  | $$      
//   | $$     | $$  | $$    $$| $$\  $$ | $$         | $$         | $$        | $$  | $$      
//   | $$    /$$$$$$|  $$$$$$/| $$ \  $$| $$$$$$$$   | $$         | $$       /$$$$$$| $$$$$$$$
//   |__/   |______/ \______/ |__/  \__/|________/   |__/         |__/      |______/|________/



///////////////////////////////////////////////////////////////////////////////////////////////////////////

// Handle ticket filtering
$ticket_filter_where = "";
$ticket_filter_params = [];
$ticket_filter_types = "";

if (isset($_GET['filter_tickets'])) {
  if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $ticket_filter_where .= " AND t.status = ?";
    $ticket_filter_params[] = $_GET['status'];
    $ticket_filter_types .= 's';
  }

  if (isset($_GET['date_filter']) && $_GET['date_filter'] != 'all') {
    $today = date('Y-m-d');
    switch ($_GET['date_filter']) {
      case 'today':
        $ticket_filter_where .= " AND t.travel_date = ?";
        $ticket_filter_params[] = $today;
        $ticket_filter_types .= 's';
        break;
      case 'week':
        $ticket_filter_where .= " AND t.travel_date BETWEEN ? AND ?";
        $ticket_filter_params[] = $today;
        $ticket_filter_params[] = date('Y-m-d', strtotime('+7 days'));
        $ticket_filter_types .= 'ss';
        break;
      case 'month':
        $ticket_filter_where .= " AND t.travel_date BETWEEN ? AND ?";
        $ticket_filter_params[] = $today;
        $ticket_filter_params[] = date('Y-m-d', strtotime('+30 days'));
        $ticket_filter_types .= 'ss';
        break;
      case 'past':
        $ticket_filter_where .= " AND t.travel_date < ?";
        $ticket_filter_params[] = $today;
        $ticket_filter_types .= 's';
        break;
      case 'future':
        $ticket_filter_where .= " AND t.travel_date > ?";
        $ticket_filter_params[] = $today;
        $ticket_filter_types .= 's';
        break;
    }
  }

  if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $ticket_filter_where .= " AND (u.full_name LIKE ? OR ter.name LIKE ? OR des.name LIKE ? OR t.id LIKE ?)";
    $ticket_filter_params[] = $search;
    $ticket_filter_params[] = $search;
    $ticket_filter_params[] = $search;
    $ticket_filter_params[] = $search;
    $ticket_filter_types .= 'ssss';
  }
}


// Get all tickets for management
$tickets = [];
$ticket_query = "SELECT t.*, u.full_name as customer_name, 
                ter.name as terminal_name, des.name as destination_name
                FROM tickets t
                JOIN users u ON t.user_id = u.id
                JOIN terminals ter ON t.terminal_id = ter.id
                JOIN destinations des ON t.destination_id = des.id
                WHERE 1=1 $ticket_filter_where
                ORDER BY t.travel_date DESC, t.travel_time DESC";

if (!empty($ticket_filter_params)) {
  $stmt = mysqli_prepare($conn, $ticket_query);
  mysqli_stmt_bind_param($stmt, $ticket_filter_types, ...$ticket_filter_params);
  mysqli_stmt_execute($stmt);
  $ticket_result = mysqli_stmt_get_result($stmt);
} else {
  $ticket_result = mysqli_query($conn, $ticket_query);
}

if ($ticket_result) {
  while ($row = mysqli_fetch_assoc($ticket_result)) {
    $tickets[] = $row;
  }
} else {
  error_log("Ticket query failed: " . mysqli_error($conn));
  $_SESSION['error_message'] = "Error loading tickets. Please try again.";
}

// Handle van filtering
$van_filter_where = "";
$van_filter_params = [];
$van_filter_types = "";

if (isset($_GET['filter_tickets']) || isset($_GET['filter_vans'])) {
  $_SESSION['last_filters'] = $_GET;
  if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $van_filter_where .= " AND v.status = ?";
    $van_filter_params[] = $_GET['status'];
    $van_filter_types .= 's';
  }

  if (isset($_GET['terminal']) && $_GET['terminal'] != 'all') {
    $van_filter_where .= " AND v.terminal_id = ?";
    $van_filter_params[] = $_GET['terminal'];
    $van_filter_types .= 's';
  }

  if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $van_filter_where .= " AND (v.id LIKE ? OR v.license_plate LIKE ? OR v.model LIKE ? OR t.name LIKE ?)";
    $van_filter_params[] = $search;
    $van_filter_params[] = $search;
    $van_filter_params[] = $search;
    $van_filter_params[] = $search;
    $van_filter_types .= 'ssss';
  }
}

// Get all vans
$vans = [];
$van_query = "SELECT v.*, t.name as terminal_name 
              FROM vans v
              JOIN terminals t ON v.terminal_id = t.id
              WHERE 1=1 $van_filter_where
              ORDER BY v.status, v.id";

if (!empty($van_filter_params)) {
  $stmt = mysqli_prepare($conn, $van_query);
  mysqli_stmt_bind_param($stmt, $van_filter_types, ...$van_filter_params);
  mysqli_stmt_execute($stmt);
  $van_result = mysqli_stmt_get_result($stmt);
} else {
  $van_result = mysqli_query($conn, $van_query);
}

if ($van_result) {
  while ($row = mysqli_fetch_assoc($van_result)) {
    $vans[] = $row;
  }
} else {
  error_log("Van query failed: " . mysqli_error($conn));
  $_SESSION['error_message'] = "Error loading vans. Please try again.";
}

// Get terminals for van management
$terminals = [];
$terminals_result = mysqli_query($conn, "SELECT * FROM terminals");
if ($terminals_result) {
  while ($row = mysqli_fetch_assoc($terminals_result)) {
    $terminals[] = $row;
  }
} else {
  error_log("Terminals query failed: " . mysqli_error($conn));
}

// Handle ticket status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
  $ticket_id = (int)$_POST['ticket_id'];
  $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);

  // Validate the status value
  $allowed_statuses = ['upcoming', 'completed', 'cancelled'];
  if (!in_array($new_status, $allowed_statuses)) {
    $_SESSION['error_message'] = "Invalid status value";
    header("Location: manager.php");
    exit();
  }

  // Update ticket status
  $update_query = "UPDATE tickets SET status = ? WHERE id = ?";
  $stmt = mysqli_prepare($conn, $update_query);
  mysqli_stmt_bind_param($stmt, "si", $new_status, $ticket_id);

  if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_message'] = "Ticket status updated successfully!";
  } else {
    $_SESSION['error_message'] = "Error updating ticket status: " . mysqli_error($conn);
  }

  header("Location: manager.php");
  exit();
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
///$$$$$$$$ /$$$$$$  /$$$$$$  /$$   /$$ /$$$$$$$$ /$$$$$$$$       /$$$$$$$  /$$$$$$$$ /$$      
//|__  $$__/|_  $$_/ /$$__  $$| $$  /$$/| $$_____/|__  $$__/      | $$__  $$| $$_____/| $$      
//   | $$     | $$  | $$  \__/| $$ /$$/ | $$         | $$         | $$  \ $$| $$      | $$      
//   | $$     | $$  | $$      | $$$$$/  | $$$$$      | $$         | $$  | $$| $$$$$   | $$      
//   | $$     | $$  | $$      | $$  $$  | $$__/      | $$         | $$  | $$| $$__/   | $$      
//   | $$     | $$  | $$    $$| $$\  $$ | $$         | $$         | $$  | $$| $$      | $$      
//  | $$    /$$$$$$|  $$$$$$/| $$ \  $$| $$$$$$$$   | $$         | $$$$$$$/| $$$$$$$$| $$$$$$$$
//   |__/   |______/ \______/ |__/  \__/|________/   |__/         |_______/ |________/|________/



///////////////////////////////////////////////////////////////////////////////////////////////////////////



// Handle ticket deletion
// Handle ticket deletion (single or multiple)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['delete_ticket']) || isset($_POST['delete_tickets']))) {
  // Verify CSRF token
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid CSRF token";
    header("Location: manager.php");
    exit();
  }

  $ticket_ids = [];
  if (isset($_POST['delete_ticket'])) {
    $ticket_ids[] = (int)$_POST['ticket_id'];
  } elseif (isset($_POST['delete_tickets']) && !empty($_POST['ticket_ids'])) {
    $ticket_ids = array_map('intval', $_POST['ticket_ids']);
  }

  if (empty($ticket_ids)) {
    $_SESSION['error_message'] = "No tickets selected for deletion";
    header("Location: manager.php");
    exit();
  }

  $ticket_ids_str = implode(',', $ticket_ids);

  // First delete related records
  $delete_queries = [
    "DELETE FROM van_assignments WHERE ticket_id IN ($ticket_ids_str)",
    "DELETE FROM ratings WHERE ticket_id IN ($ticket_ids_str)",
    "DELETE FROM ticket_barcodes WHERE ticket_id IN ($ticket_ids_str)",
    "DELETE FROM tickets WHERE id IN ($ticket_ids_str)"
  ];

  $success = true;
  mysqli_begin_transaction($conn);

  foreach ($delete_queries as $query) {
    if (!mysqli_query($conn, $query)) {
      $success = false;
      $_SESSION['error_message'] = "Error deleting tickets: " . mysqli_error($conn);
      mysqli_rollback($conn);
      break;
    }
  }

  if ($success) {
    mysqli_commit($conn);
    $count = count($ticket_ids);
    $_SESSION['success_message'] = "Successfully deleted $count ticket(s)!";
  }

  header("Location: manager.php");
  exit();
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////

// Handle van status update
// In your status update handler:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_van_status'])) {
    $van_id = mysqli_real_escape_string($conn, $_POST['van_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);

    // Only update status, not boundary fields
    $update_query = "UPDATE vans SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ss", $new_status, $van_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Van status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating van status: " . mysqli_error($conn);
    }
    
    header("Location: manager.php#van-management");
    exit();
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_maintenance'])) {
  $van_id = mysqli_real_escape_string($conn, $_POST['van_id']);
  $current_date = date('Y-m-d');

  $update_query = "UPDATE vans SET last_maintenance = ? WHERE id = ?";
  $stmt = mysqli_prepare($conn, $update_query);
  mysqli_stmt_bind_param($stmt, "ss", $current_date, $van_id);

  if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_message'] = "Maintenance date updated successfully!";
  } else {
    $_SESSION['error_message'] = "Error updating maintenance date: " . mysqli_error($conn);
  }

  header("Location: manager.php#van-management");
  exit();
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////

// Handle van management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_van'])) {
  // Verify CSRF token
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid CSRF token";
    header("Location: manager.php#van-management");
    exit();
  }

  // Get and sanitize form data
  $van_id = trim($_POST['van_id']);
  $license_plate = trim($_POST['license_plate']);
  $model = trim($_POST['model']);
  $terminal_id = (int)$_POST['terminal_id'];
  $status = $_POST['status'];
  $driver_name = trim($_POST['driver_name'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $last_maintenance = date('Y-m-d');

  // Validation
  $errors = [];

  if (empty($van_id)) {
    $errors[] = "Van ID is required";
  } elseif (!preg_match('/^[a-zA-Z0-9-_]+$/', $van_id)) {
    $errors[] = "Van ID can only contain letters, numbers, dashes, and underscores";
  }

  if (empty($license_plate)) {
    $errors[] = "License plate is required";
  }

  if (empty($model)) {
    $errors[] = "Model is required";
  }

  if ($terminal_id <= 0) {
    $errors[] = "Please select a valid terminal";
  }

  // Check for duplicate van ID
  $check = $conn->prepare("SELECT id FROM vans WHERE id = ?");
  $check->bind_param("s", $van_id);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    $errors[] = "A van with this ID already exists";
  }
  $check->close();

  if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: manager.php#van-management");
    exit();
  }

  // Insert the van
  $query = "INSERT INTO vans (id, license_plate, model, terminal_id, destination_id, status, driver_name, notes, last_maintenance) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($query);

  if ($stmt) {
    $stmt->bind_param(
      "sssiissss",
      $van_id,
      $license_plate,
      $model,
      $terminal_id,
      $destination_id,
      $status,
      $driver_name,
      $notes,
      $last_maintenance
    );

    if ($stmt->execute()) {
      $_SESSION['success_message'] = "Van added successfully!";
    } else {
      $_SESSION['error_message'] = "Error adding van: " . $stmt->error;
      error_log("Van add error: " . $stmt->error);
    }
    $stmt->close();
  } else {
    $_SESSION['error_message'] = "Database error: " . $conn->error;
    error_log("Prepare statement error: " . $conn->error);
  }

  header("Location: manager.php#van-management");
  exit();
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////



// Edit existing van
if (isset($_POST['edit_van'])) {
  $van_id = mysqli_real_escape_string($conn, $_POST['van_id']);
  $license_plate = mysqli_real_escape_string($conn, $_POST['license_plate']);
  $model = mysqli_real_escape_string($conn, $_POST['model']);
  $terminal_id = (int)$_POST['terminal_id'];
  $status = mysqli_real_escape_string($conn, $_POST['status']);
  $driver_name = mysqli_real_escape_string($conn, $_POST['driver_name'] ?? '');
  $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

  // Input validation
  if (empty($van_id) || empty($license_plate) || empty($model)) {
    $_SESSION['error_message'] = "Required fields are missing";
    header("Location: manager.php#van-management");
    exit();
  }

  // Update van in database
  $update_query = "UPDATE vans SET 
                        license_plate = ?,
                        model = ?,
                        terminal_id = ?,
                        status = ?,
                        driver_name = ?,
                        notes = ?
                        WHERE id = ?";

  $stmt = mysqli_prepare($conn, $update_query);
  if (!$stmt) {
    error_log("Prepare failed: " . mysqli_error($conn));
    $_SESSION['error_message'] = "Database error. Please try again.";
    header("Location: manager.php#van-management");
    exit();
  }

  mysqli_stmt_bind_param(
    $stmt,
    "ssissss",
    $license_plate,
    $model,
    $terminal_id,
    $status,
    $driver_name,
    $notes,
    $van_id
  );

  if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_message'] = "Van updated successfully!";
  } else {
    $_SESSION['error_message'] = "Error updating van: " . mysqli_stmt_error($stmt);
  }

  header("Location: manager.php#van-management");
  exit();
}

// Delete van
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_van'])) {
  $van_id = mysqli_real_escape_string($conn, $_POST['van_id']);

  // Start transaction
  mysqli_begin_transaction($conn);

  try {
    // Delete the van
    $delete_van = "DELETE FROM vans WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_van);
    mysqli_stmt_bind_param($stmt, "s", $van_id);

    if (!mysqli_stmt_execute($stmt)) {
      throw new Exception("Failed to delete van: " . mysqli_stmt_error($stmt));
    }

    mysqli_commit($conn);
    $_SESSION['success_message'] = "Van deleted successfully!";
  } catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error_message'] = "Error deleting van: " . $e->getMessage();
  }

  header("Location: manager.php#van-management");
  exit();
}

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['generate_report'])) {
    $report_type = mysqli_real_escape_string($conn, $_POST['report_type']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    // Validate dates
    if (empty($start_date) || empty($end_date) || strtotime($start_date) > strtotime($end_date)) {
      $_SESSION['error_message'] = "Invalid date range selected";
      header("Location: manager.php#reports");
      exit();
    }

    // Generate report data
    $report_data = generateReport($conn, $report_type, $start_date, $end_date);

    // Save report to database
    $insert_query = "INSERT INTO reports (report_type, period_start, period_end, total_sales, total_tickets, completed_tickets, cancelled_tickets)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param(
      $stmt,
      "sssdiii",
      $report_type,
      $start_date,
      $end_date,
      $report_data['total_sales'],
      $report_data['total_tickets'],
      $report_data['completed_tickets'],
      $report_data['cancelled_tickets']
    );

    if (mysqli_stmt_execute($stmt)) {
      $report_id = mysqli_insert_id($conn);
      $_SESSION['current_report'] = [
        'id' => $report_id,
        'type' => $report_type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'data' => $report_data
      ];
      $_SESSION['success_message'] = "Report generated successfully!";
    } else {
      $_SESSION['error_message'] = "Error saving report: " . mysqli_error($conn);
    }

    header("Location: manager.php#reports");
    exit();
  } elseif (isset($_POST['view_report'])) {
    $report_id = (int)$_POST['report_id'];
    $report_query = "SELECT * FROM reports WHERE id = ?";
    $stmt = mysqli_prepare($conn, $report_query);
    mysqli_stmt_bind_param($stmt, "i", $report_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($report = mysqli_fetch_assoc($result)) {
      // Regenerate the report data
      $report_data = generateReport($conn, $report['report_type'], $report['period_start'], $report['period_end']);

      $_SESSION['current_report'] = [
        'id' => $report['id'],
        'type' => $report['report_type'],
        'start_date' => $report['period_start'],
        'end_date' => $report['period_end'],
        'data' => $report_data
      ];
    } else {
      $_SESSION['error_message'] = "Report not found";
    }
  }
  header("Location: manager.php#reports");
  exit();
}

// Function to generate report data
function generateReport($conn, $report_type, $start_date, $end_date)
{
  $report_data = [
    'total_sales' => 0,
    'total_tickets' => 0,
    'completed_tickets' => 0,
    'cancelled_tickets' => 0,
    'tickets_by_date' => [],
    'sales_by_date' => [],
    'popular_destinations' => [],
    'terminal_performance' => [],
    'van_utilization' => []
  ];

  // Get ticket data for the period
  $ticket_query = "SELECT 
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tickets,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tickets,
                    SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_sales,
                    travel_date
                  FROM tickets
                  WHERE travel_date BETWEEN ? AND ?
                  GROUP BY travel_date
                  ORDER BY travel_date";

  $stmt = mysqli_prepare($conn, $ticket_query);
  mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($result)) {
    $report_data['total_tickets'] += $row['total_tickets'];
    $report_data['completed_tickets'] += $row['completed_tickets'];
    $report_data['cancelled_tickets'] += $row['cancelled_tickets'];
    $report_data['total_sales'] += $row['total_sales'];

    $date = date('M j', strtotime($row['travel_date']));
    $report_data['tickets_by_date'][$date] = $row['total_tickets'];
    $report_data['sales_by_date'][$date] = $row['total_sales'];
  }

  // Get popular destinations
  $dest_query = "SELECT d.name, COUNT(t.id) as ticket_count 
                 FROM tickets t
                 JOIN destinations d ON t.destination_id = d.id
                 WHERE t.travel_date BETWEEN ? AND ?
                 GROUP BY d.name
                 ORDER BY ticket_count DESC
                 LIMIT 5";
  $stmt = mysqli_prepare($conn, $dest_query);
  mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($result)) {
    $report_data['popular_destinations'][$row['name']] = $row['ticket_count'];
  }

  // Get terminal performance
  $terminal_query = "SELECT ter.name, COUNT(t.id) as ticket_count, 
                     SUM(CASE WHEN t.status = 'completed' THEN t.total_amount ELSE 0 END) as total_sales
                     FROM tickets t
                     JOIN terminals ter ON t.terminal_id = ter.id
                     WHERE t.travel_date BETWEEN ? AND ?
                     GROUP BY ter.name
                     ORDER BY total_sales DESC";
  $stmt = mysqli_prepare($conn, $terminal_query);
  mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($result)) {
    $report_data['terminal_performance'][$row['name']] = [
      'tickets' => $row['ticket_count'],
      'sales' => $row['total_sales']
    ];
  }

  // Get van utilization
  $van_query = "SELECT v.id, v.model, COUNT(va.ticket_id) as trips,
                SUM(CASE WHEN t.status = 'completed' THEN t.total_amount ELSE 0 END) as revenue
                FROM van_assignments va
                JOIN tickets t ON va.ticket_id = t.id
                JOIN vans v ON va.van_id = v.id
                WHERE t.travel_date BETWEEN ? AND ?
                GROUP BY v.id
                ORDER BY trips DESC";
  $stmt = mysqli_prepare($conn, $van_query);
  mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($result)) {
    $report_data['van_utilization'][$row['id']] = [
      'model' => $row['model'],
      'trips' => $row['trips'],
      'revenue' => $row['revenue']
    ];
  }

  return $report_data;
}

// Get saved reports for display
$saved_reports = [];
$reports_query = "SELECT * FROM reports ORDER BY period_start DESC LIMIT 10";
$reports_result = mysqli_query($conn, $reports_query);
if ($reports_result) {
  while ($row = mysqli_fetch_assoc($reports_result)) {
    $saved_reports[] = $row;
  }
}

// Check for current report in session
$current_report = $_SESSION['current_report'] ?? null;
unset($_SESSION['current_report']);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
  foreach ($_POST['settings'] as $key => $value) {
    $value = mysqli_real_escape_string($conn, $value);
    $update_query = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ss", $value, $key);
    mysqli_stmt_execute($stmt);
  }

  $_SESSION['success_message'] = "Settings updated successfully!";
  header("Location: manager.php#settings");
  exit();
}

// Get current settings
$settings = [];
$settings_result = mysqli_query($conn, "SELECT * FROM settings");
if ($settings_result) {
  while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_key']] = $row;
  }
}

// Get statistics for dashboard
$stats = [
  'completed_tickets' => 0,
  'upcoming_tickets' => 0,
  'cancelled_tickets' => 0,
  'active_vans' => 0,
  'maintenance_vans' => 0,
  'available_vans' => 0,
  'inactive_vans' => 0
];

$stats_query = "SELECT 
                (SELECT COUNT(*) FROM tickets WHERE status = 'completed') as completed_tickets,
                (SELECT COUNT(*) FROM tickets WHERE status = 'upcoming') as upcoming_tickets,
                (SELECT COUNT(*) FROM tickets WHERE status = 'cancelled') as cancelled_tickets,
                (SELECT COUNT(*) FROM vans WHERE status = 'active') as active_vans,
                (SELECT COUNT(*) FROM vans WHERE status = 'maintenance') as maintenance_vans,
                (SELECT COUNT(*) FROM vans WHERE status = 'active') as available_vans,
                (SELECT COUNT(*) FROM vans WHERE status = 'inactive') as inactive_vans";

$stats_result = mysqli_query($conn, $stats_query);
if ($stats_result && mysqli_num_rows($stats_result) > 0) {
  $stats = mysqli_fetch_assoc($stats_result);
} else {
  error_log("Stats query failed: " . mysqli_error($conn));
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////////////
// DESTINATION MANAGEMENT FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////////////////////

// Handle destination management
// DESTINATION MANAGEMENT FUNCTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verify CSRF token for all destination actions
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Security token mismatch. Please try again.";
    header("Location: manager.php#destination-management");
    exit();
  }

  // ADD DESTINATION
  if (isset($_POST['add_destination'])) {
    $name = trim($conn->real_escape_string($_POST['name']));
    $base_fare = (float)$_POST['base_fare'];

    // Validate input
    if (empty($name) || $base_fare <= 0) {
      $_SESSION['error_message'] = "Invalid input. Name required and fare must be positive.";
      header("Location: manager.php#destination-management");
      exit();
    }

    // Check for duplicates
    $check = $conn->prepare("SELECT id FROM destinations WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
      $_SESSION['error_message'] = "Destination already exists";
      header("Location: manager.php#destination-management");
      exit();
    }

    // Insert new destination
    $stmt = $conn->prepare("INSERT INTO destinations (name, base_fare) VALUES (?, ?)");
    $stmt->bind_param("sd", $name, $base_fare);
    if ($stmt->execute()) {
      $_SESSION['success_message'] = "Destination added successfully!";
    } else {
      $_SESSION['error_message'] = "Error adding destination: " . $stmt->error;
    }
    header("Location: manager.php#destination-management");
    exit();
  }

  // EDIT DESTINATION
  if (isset($_POST['edit_destination'])) {
    $id = (int)$_POST['id'];
    $name = trim($conn->real_escape_string($_POST['name']));
    $base_fare = (float)$_POST['base_fare'];

    // Validate input
    if (empty($name) || $base_fare <= 0 || $id <= 0) {
      $_SESSION['error_message'] = "Invalid input data";
      header("Location: manager.php#destination-management");
      exit();
    }

    // Update destination
    $stmt = $conn->prepare("UPDATE destinations SET name = ?, base_fare = ? WHERE id = ?");
    $stmt->bind_param("sdi", $name, $base_fare, $id);
    if ($stmt->execute()) {
      $_SESSION['success_message'] = "Destination updated successfully!";
    } else {
      $_SESSION['error_message'] = "Error updating destination: " . $stmt->error;
    }
    header("Location: manager.php#destination-management");
    exit();
  }

  // DELETE DESTINATION
  if (isset($_POST['delete_destination'])) {
    $id = (int)$_POST['id'];

    // Check for associated tickets
    $check = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE destination_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    if ($check->get_result()->fetch_row()[0] > 0) {
      $_SESSION['error_message'] = "Cannot delete - destination has associated tickets";
      header("Location: manager.php#destination-management");
      exit();
    }

    // Delete destination
    $stmt = $conn->prepare("DELETE FROM destinations WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      $_SESSION['success_message'] = "Destination deleted successfully!";
    } else {
      $_SESSION['error_message'] = "Error deleting destination: " . $stmt->error;
    }
    header("Location: manager.php#destination-management");
    exit();
  }
}

// Fetch all destinations for display
$destinations = [];
$result = $conn->query("SELECT * FROM destinations ORDER BY name");
if ($result) {
  $destinations = $result->fetch_all(MYSQLI_ASSOC);
} else {
  $_SESSION['error_message'] = "Error loading destinations: " . $conn->error;
}

// At the top of your file (after database connection)
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
$searchParams = [];

if (!empty($searchTerm)) {
  $searchCondition = " WHERE d.name LIKE ?";
  $searchParams = ["%$searchTerm%"];
}

// Get all destinations with ticket counts (updated query)
$destinationsQuery = "SELECT d.id, d.name, d.base_fare, COUNT(t.id) as ticket_count 
                     FROM destinations d
                     LEFT JOIN tickets t ON d.id = t.destination_id
                     $searchCondition
                     GROUP BY d.id
                     ORDER BY ticket_count DESC";

$stmt = mysqli_prepare($conn, $destinationsQuery);
if (!empty($searchTerm)) {
  mysqli_stmt_bind_param($stmt, "s", $searchParams[0]);
}
mysqli_stmt_execute($stmt);
$destinationsResult = mysqli_stmt_get_result($stmt);


////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////////////
// DASHBOARD
///////////////////////////////////////////////////////////////////////////////////////////////////////////


// Get month and year from request
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get days in month
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Initialize array with 0 for each day
$revenueData = array_fill(1, $daysInMonth, 0);

// Query to get daily revenue for the month
$query = "SELECT DAY(travel_date) as day, SUM(total_amount) as daily_revenue
          FROM tickets
          WHERE MONTH(travel_date) = $month 
          AND YEAR(travel_date) = $year
          AND status = 'completed'
          GROUP BY DAY(travel_date)";

$result = mysqli_query($conn, $query);

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $day = (int)$row['day'];
    $revenueData[$day] = (float)$row['daily_revenue'];
  }
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////////////
// RATINGS
///////////////////////////////////////////////////////////////////////////////////////////////////////////

// Get ratings statistics
$ratings_stats = [
  'average_rating' => 0,
  'total_ratings' => 0,
  'rating_distribution' => [0, 0, 0, 0, 0] // 1-5 stars
];

$ratings_query = "SELECT 
    AVG(stars) as average_rating,
    COUNT(*) as total_ratings,
    SUM(CASE WHEN stars = 1 THEN 1 ELSE 0 END) as one_star,
    SUM(CASE WHEN stars = 2 THEN 1 ELSE 0 END) as two_stars,
    SUM(CASE WHEN stars = 3 THEN 1 ELSE 0 END) as three_stars,
    SUM(CASE WHEN stars = 4 THEN 1 ELSE 0 END) as four_stars,
    SUM(CASE WHEN stars = 5 THEN 1 ELSE 0 END) as five_stars
FROM ratings";

$ratings_result = mysqli_query($conn, $ratings_query);
if ($ratings_result && mysqli_num_rows($ratings_result) > 0) {
  $ratings_data = mysqli_fetch_assoc($ratings_result);

  // Handle NULL values
  $average_rating = $ratings_data['average_rating'] ?? 0;
  $ratings_stats['average_rating'] = round($average_rating, 1);

  $ratings_stats['total_ratings'] = $ratings_data['total_ratings'] ?? 0;

  $ratings_stats['rating_distribution'] = [
    $ratings_data['one_star'] ?? 0,
    $ratings_data['two_stars'] ?? 0,
    $ratings_data['three_stars'] ?? 0,
    $ratings_data['four_stars'] ?? 0,
    $ratings_data['five_stars'] ?? 0
  ];
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VAN BOUNDARY MANAGEMENT
////////////////////////////////////////////////////////////////////////////////////////////////////////////



?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="img/knorr.png" type="image/png">
  <title>Manager Dashboard | VanTastic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="manager.css">
  <link rel="stylesheet" href="manager.css?v=<?php echo time(); ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <img src="img/VanTasticWhite.png" alt="VanTastic Logo">
      </div>

      <ul class="sidebar-menu">
        <li><a href="#dashboardv2" class="active" data-section="dashboardv2"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

        <li><a href="#dashboard" class="active" data-section="dashboard"><i class="fas fa-ticket"></i> Tickets</a></li>
        <li><a href="#tickets-pd" data-section="tickets-pd"><i class="fas fa-barcode"></i> Ticket Barcodes</a></li>
        <li><a href="#van-management" data-section="van-management"><i class="fas fa-van-shuttle"></i> Van Management</a></li>
        <li><a href="#boundary-management" data-section="boundary-management"><i class="fas fa-route"></i> Van Boundaries</a></li>
        <li><a href="#destination-management" data-section="destination-management"><i class="fas fa-map-marked-alt"></i> Destinations</a></li>
        <li><a href="#reports" data-section="reports"><i class="fas fa-chart-line"></i> Reports</a></li>
        <li><a href="#settings" data-section="settings"><i class="fas fa-cog"></i> Settings</a></li>
      </ul>




      <button class="logout-btn" id="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </button>
    </aside>

    <main class="main-content">
      <!-- Display success/error messages -->
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
          <?php echo $_SESSION['success_message'];
          unset($_SESSION['success_message']); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
          <?php echo $_SESSION['error_message'];
          unset($_SESSION['error_message']); ?>
        </div>
      <?php endif; ?>

      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->



      <section id="dashboardv2" class="dashboard-section active">
        <div class="dashboard-header">
          <h2>Manager Dashboard</h2>
          <div class="welcome-message">
            <h3>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h3>
            <p>Here's what's happening today.</p>
          </div>
        </div>

        <div class="stats-container">
          <!-- Booked Tickets Card -->
          <div class="stat-card2">

            <h3>Booked Tickets</h3>
            <div class="card-icon2">
              <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="card-content">

              <div class="value"><?php
                                  // Query for total booked tickets (both upcoming and completed)
                                  $totalTicketsQuery = "SELECT COUNT(*) as total_tickets FROM tickets";
                                  $totalTicketsResult = mysqli_query($conn, $totalTicketsQuery);
                                  $totalTickets = mysqli_fetch_assoc($totalTicketsResult);
                                  echo number_format($totalTickets['total_tickets'] ?? 0);
                                  ?></div>
              <div class="subtext">Total booked tickets</div>
            </div>
            <svg class="mini-graph" viewBox="0 0 100 40" preserveAspectRatio="none">
              <!-- Example line graph - replace with your actual data points -->
              <path d="M0,40 L20,25 L40,30 L60,15 L80,20 L100,10"
                stroke="var(--card-color)"
                stroke-width="2"
                fill="none"
                stroke-linecap="round"
                stroke-dasharray="1000"
                stroke-dashoffset="1000"
                style="animation: drawLine 15s ease-out forwards" />
            </svg>
          </div>

          <!-- Active Vans Card -->
          <div class="stat-card2">
            <h3>Active Vans</h3>
            <div class="card-icon2">
              <i class="fas fa-van-shuttle"></i>
            </div>
            <div class="card-content">

              <div class="value"><?php echo $stats['active_vans']; ?></div>
              <div class="subtext">Currently in service</div>
            </div>
            <svg class="mini-graph" viewBox="0 0 100 40" preserveAspectRatio="none">
              <!-- Area fill -->
              <defs>
                <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stop-color="var(--card-color)" stop-opacity="0.2" />
                  <stop offset="100%" stop-color="var(--card-color)" stop-opacity="0.02" />
                </linearGradient>
              </defs>
              <path d="M0,40 L0,30 C10,35 15,25 25,20 S40,10 50,15 S65,5 75,10 S90,20 100,15 L100,40 Z"
                fill="url(#areaGradient)"
                stroke="none" />

              <!-- Line with animated drawing -->
              <path d="M0,30 C10,35 15,25 25,20 S40,10 50,15 S65,5 75,10 S90,20 100,15"
                stroke="var(--card-color)"
                stroke-width="2"
                fill="none"
                stroke-linecap="round"
                stroke-dasharray="1000"
                stroke-dashoffset="1000"
                style="animation: drawLine 15s ease-out forwards;" />

              <!-- Data points -->
              <circle cx="0" cy="30" r="2.5" fill="white" stroke="var(--card-color)" stroke-width="1.5">
                <animate attributeName="r" values="2.5;3.5;2.5" dur="1s" begin="1.8s" repeatCount="1" />
              </circle>
              <circle cx="25" cy="20" r="2.5" fill="white" stroke="var(--card-color)" stroke-width="1.5">
                <animate attributeName="r" values="2.5;3.5;2.5" dur="1s" begin="2s" repeatCount="1" />
              </circle>
              <circle cx="50" cy="15" r="2.5" fill="white" stroke="var(--card-color)" stroke-width="1.5">
                <animate attributeName="r" values="2.5;3.5;2.5" dur="1s" begin="2.2s" repeatCount="1" />
              </circle>
              <circle cx="75" cy="10" r="2.5" fill="white" stroke="var(--card-color)" stroke-width="1.5">
                <animate attributeName="r" values="2.5;3.5;2.5" dur="1s" begin="2.4s" repeatCount="1" />
              </circle>
              <circle cx="100" cy="15" r="2.5" fill="white" stroke="var(--card-color)" stroke-width="1.5">
                <animate attributeName="r" values="2.5;3.5;2.5" dur="1s" begin="2.6s" repeatCount="1" />
              </circle>
            </svg>
          </div>


          <!-- Total Sales Card -->
          <div class="stat-card2">
            <h3>Total Sales</h3>
            <div class="card-icon2">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="card-content">

              <div class="value">₱<?php
                                  // Query to get total sales for current month
                                  $currentMonth = date('Y-m');
                                  $salesQuery = "SELECT SUM(total_amount) as monthly_sales FROM tickets 
                        WHERE status = 'completed' AND DATE_FORMAT(travel_date, '%Y-%m') = '$currentMonth'";
                                  $salesResult = mysqli_query($conn, $salesQuery);
                                  $salesData = mysqli_fetch_assoc($salesResult);
                                  echo number_format($salesData['monthly_sales'] ?? 0, 2);
                                  ?></div>
              <div class="subtext">This month's revenue</div>
            </div>
            <svg class="mini-graph" viewBox="0 0 100 40" preserveAspectRatio="none">
              <!-- Animated bars -->
              <rect x="5" y="25" width="10" height="15" fill="var(--card-color)" opacity="0.8">
                <animate attributeName="height" from="0" to="15" dur="0.2s" fill="freeze" />
                <animate attributeName="y" from="40" to="25" dur="0.6s" fill="freeze" />
              </rect>
              <rect x="25" y="15" width="10" height="25" fill="var(--card-color)" opacity="0.8">
                <animate attributeName="height" from="0" to="25" dur="0.6s" begin="0.2s" fill="freeze" />
                <animate attributeName="y" from="40" to="15" dur="0.6s" begin="0.2s" fill="freeze" />
              </rect>
              <rect x="45" y="20" width="10" height="20" fill="var(--card-color)" opacity="0.8">
                <animate attributeName="height" from="0" to="20" dur="0.6s" begin="0.4s" fill="freeze" />
                <animate attributeName="y" from="40" to="20" dur="0.6s" begin="0.4s" fill="freeze" />
              </rect>
              <rect x="65" y="10" width="10" height="30" fill="var(--card-color)" opacity="0.8">
                <animate attributeName="height" from="0" to="30" dur="0.6s" begin="0.6s" fill="freeze" />
                <animate attributeName="y" from="40" to="10" dur="0.6s" begin="0.6s" fill="freeze" />
              </rect>
              <rect x="85" y="18" width="10" height="22" fill="var(--card-color)" opacity="0.8">
                <animate attributeName="height" from="0" to="22" dur="0.6s" begin="0.8s" fill="freeze" />
                <animate attributeName="y" from="40" to="18" dur="0.6s" begin="0.8s" fill="freeze" />
              </rect>
            </svg>
          </div>

          <!-- Top Destination Card -->
          <div class="stat-card2">
            <h3>Top Destination</h3>
            <div class="card-icon2">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="card-content">

              <div class="value">
                <?php
                // Query to get top destination for current month
                $topDestQuery = "SELECT d.name, COUNT(t.id) as ticket_count 
                            FROM tickets t
                            JOIN destinations d ON t.destination_id = d.id
                            WHERE DATE_FORMAT(t.travel_date, '%Y-%m') = '$currentMonth'
                            GROUP BY d.name
                            ORDER BY ticket_count DESC
                            LIMIT 1";
                $topDestResult = mysqli_query($conn, $topDestQuery);
                $topDest = mysqli_fetch_assoc($topDestResult);
                echo $topDest ? htmlspecialchars($topDest['name']) : 'N/A';
                ?>
              </div>
              <div class="subtext">
                <?php echo $topDest ? $topDest['ticket_count'] . ' tickets' : ''; ?>
              </div>
            </div>
            <svg class="mini-graph" viewBox="0 0 100 40" preserveAspectRatio="none">
              <!-- Stepped line with right angles -->
              <path d="M0,25 L15,25 L15,15 L35,15 L35,30 L50,30 L50,10 L70,10 L70,20 L85,20 L85,5 L100,5"
                stroke="var(--card-color)"
                stroke-width="2"
                fill="none"
                stroke-linecap="square"
                stroke-dasharray="1000"
                stroke-dashoffset="1000"
                style="animation: drawLine 15s ease-out forwards;" />

              <!-- Connection dots -->
              <circle cx="15" cy="25" r="1.5" fill="var(--card-color)">
                <animate attributeName="r" values="1.5;2.5;1.5" dur="0.8s" begin="1s" />
              </circle>
              <circle cx="35" cy="15" r="1.5" fill="var(--card-color)">
                <animate attributeName="r" values="1.5;2.5;1.5" dur="0.8s" begin="2s" />
              </circle>
              <circle cx="50" cy="30" r="1.5" fill="var(--card-color)">
                <animate attributeName="r" values="1.5;2.5;1.5" dur="0.8s" begin="3s" />
              </circle>
              <circle cx="70" cy="10" r="1.5" fill="var(--card-color)">
                <animate attributeName="r" values="1.5;2.5;1.5" dur="0.8s" begin="4s" />
              </circle>
            </svg>
          </div>

        </div>

        <div class="dashboard-row">
          <!-- Revenue Graph -->
          <div class="dashboard-card wide">
            <div class="card-header">
              <h3>Monthly Revenue</h3>
              <div class="card-actions">
                <button class="chart-action-btn" data-chart="revenueChartv2" data-type="line">
                  <i class="fas fa-chart-line"></i>
                </button>
                <button class="chart-action-btn" data-chart="revenueChartv2" data-type="bar">
                  <i class="fas fa-chart-bar"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <canvas id="revenueChartv2"></canvas>
            </div>
          </div>

          <!-- Recent Tickets -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3>Recent Tickets</h3>
            </div>
            <div class="card-body">
              <div class="recent-tickets">
                <?php
                // Get recent tickets
                $recentTicketsQuery = "SELECT t.id, t.travel_date, t.total_amount, 
                      u.full_name as customer_name, d.name as destination_name
                      FROM tickets t
                      JOIN users u ON t.user_id = u.id
                      JOIN destinations d ON t.destination_id = d.id
                      ORDER BY t.id DESC
                      LIMIT 5";
                $recentTicketsResult = mysqli_query($conn, $recentTicketsQuery);

                if (mysqli_num_rows($recentTicketsResult)) {
                  while ($ticket = mysqli_fetch_assoc($recentTicketsResult)) {
                    echo '<div class="ticket-item">';
                    echo '<div class="ticket-id">VT' . str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) . '</div>';
                    echo '<div class="ticket-details">';
                    echo '<div class="ticket-customer">' . htmlspecialchars(substr($ticket['customer_name'], 0, 15)) . (strlen($ticket['customer_name']) > 15 ? '...' : '') . '</div>';
                    echo '<div class="ticket-destination">' . htmlspecialchars(substr($ticket['destination_name'], 0, 20)) . (strlen($ticket['destination_name']) > 20 ? '...' : '') . '</div>';
                    echo '</div>';
                    echo '<div class="ticket-amount">₱' . number_format($ticket['total_amount'], 2) . '</div>';
                    echo '</div>';
                  }
                } else {
                  echo '<p>No recent tickets found</p>';
                }
                ?>
              </div>
            </div>

          </div>
          <div class="stat-card3">
            <div class="card-icon3">
              <i class="fas fa-star"></i>
            </div>
            <div class="card-content3">
              <h3>Customer Ratings</h3>
              <div class="value"><?php echo $ratings_stats['average_rating']; ?>/5</div>
              <div class="subtext">from <?php echo $ratings_stats['total_ratings']; ?> reviews</div>

              <div class="stars-container">
                <?php
                $avg_rating = $ratings_stats['average_rating'];
                for ($i = 1; $i <= 5; $i++) {
                  if ($i <= floor($avg_rating)) {
                    echo '<i class="fas fa-star star"></i>';
                  } elseif ($i == ceil($avg_rating) && ($avg_rating - floor($avg_rating)) >= 0.5) {
                    echo '<i class="fas fa-star-half-alt star"></i>';
                  } else {
                    echo '<i class="far fa-star star"></i>';
                  }
                }
                ?>
              </div>

              <div class="rating-distribution">
                <?php for ($i = 5; $i >= 1; $i--):
                  $count = $ratings_stats['rating_distribution'][$i - 1];
                  $percentage = $ratings_stats['total_ratings'] > 0 ?
                    round(($count / $ratings_stats['total_ratings']) * 100) : 0;
                ?>
                  <div class="rating-bar">
                    <div class="star-count"><?php echo $i; ?>★</div>
                    <div class="bar-container">
                      <div class="bar" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="percentage"><?php echo $percentage; ?>%</div>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>


        </div>
      </section>



      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->


      <!-- Dashboard Section buut inside a ticket section -->
      <section id="dashboard" class="dashboard-section">
        <div class="dashboard-header">
          <h2>Ticket Management</h2>
          <div class="filter-controls">
            <div class="search-bar">
              <input type="text" id="ticket-search" placeholder="Search tickets...">
              <button id="search-tickets"><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-group">
              <label for="status-filter">Status:</label>
              <select id="status-filter">
                <option value="all">All Statuses</option>
                <option value="completed">Completed</option>
                <option value="upcoming">Upcoming</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="filter-group">
              <label for="date-filter">Date Range:</label>
              <select id="date-filter">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="past">Past Tickets</option>
                <option value="future">Future Tickets</option>
              </select>
            </div>
            <button class="btn btn-secondary" id="reset-filters">
              <i class="fas fa-sync-alt"></i> Reset
            </button>
            <button class="btn btn-danger" id="delete-selected" style="margin-left: auto;">
              <i class="fas fa-trash"></i> Delete Selected
            </button>
          </div>
        </div>

        <div class="stats-container">
          <div class="stat-cardtik">
            <h3>Completed Tickets</h3>
            <div class="value"><?php echo $stats['completed_tickets']; ?></div>
          </div>
          <div class="stat-cardtik">
            <h3>Upcoming Tickets</h3>
            <div class="value"><?php echo $stats['upcoming_tickets']; ?></div>
          </div>
          <div class="stat-cardtik">
            <h3>Cancelled Tickets</h3>
            <div class="value"><?php echo $stats['cancelled_tickets']; ?></div>
          </div>
        </div>

        <div class="data-table">
          <table>
            <thead>
              <tr>

                <th>Ticket #</th>
                <th>Customer</th>
                <th>From</th>
                <th>To</th>
                <th>Date</th>
                <th>Passengers</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
                <th><input type="checkbox" id="select-all"></th>
              </tr>
            </thead>


            <tbody>
              <?php foreach ($tickets as $ticket): ?>
                <tr>
                  <td>VT<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></td>
                  <td><?php echo htmlspecialchars($ticket['customer_name']); ?></td>
                  <td><?php echo htmlspecialchars($ticket['terminal_name']); ?></td>
                  <td><?php echo htmlspecialchars($ticket['destination_name']); ?></td>
                  <td><?php echo date('M j, Y', strtotime($ticket['travel_date'])); ?></td>
                  <td><?php echo $ticket['passenger_count']; ?></td>
                  <td>₱<?php echo number_format($ticket['total_amount'], 2); ?></td>
                  <td>
                    <span class="status-badge status-<?php echo $ticket['status']; ?>">
                      <?php echo ucfirst($ticket['status']); ?>
                    </span>
                  </td>
                  <td>
                    <!-- Status Change Form -->
                    <form method="post" class="ticket-action-form">
                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                      <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                      <select name="new_status" class="action-dropdown"
                        onchange="confirmStatusChange(this)"
                        <?php echo $ticket['status'] === 'completed' ? 'enable' : ''; ?>>
                        <option value="upcoming" <?php echo $ticket['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="completed" <?php echo $ticket['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $ticket['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                      </select>
                    </form>

                    <form method="post" class="ticket-delete-form" action="manager.php">
                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                      <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                      <button type="submit" name="delete_ticket" class="btn btn-danger btn-sm"
                        onclick="return confirm('Are you sure you want to delete this ticket?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                  <td><input type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['id']; ?>" class="ticket-checkbox"></td>

                </tr>
              <?php endforeach; ?>
            </tbody>


          </table>
        </div>




        <script>
          // Select all checkbox functionality
          document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.ticket-checkbox');
            checkboxes.forEach(checkbox => {
              checkbox.checked = this.checked;
            });
          });

          // Mass delete functionality
          document.getElementById('delete-selected').addEventListener('click', function() {
            const selectedTickets = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.value);

            if (selectedTickets.length === 0) {
              alert('Please select at least one ticket to delete.');
              return;
            }

            if (confirm(`Are you sure you want to delete ${selectedTickets.length} selected ticket(s)?`)) {
              const form = document.createElement('form');
              form.method = 'post';
              form.action = 'manager.php';

              const csrfInput = document.createElement('input');
              csrfInput.type = 'hidden';
              csrfInput.name = 'csrf_token';
              csrfInput.value = '<?php echo $_SESSION['csrf_token']; ?>';
              form.appendChild(csrfInput);

              selectedTickets.forEach(ticketId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ticket_ids[]';
                input.value = ticketId;
                form.appendChild(input);
              });

              const deleteInput = document.createElement('input');
              deleteInput.type = 'hidden';
              deleteInput.name = 'delete_tickets';
              form.appendChild(deleteInput);

              document.body.appendChild(form);
              form.submit();
            }
          });




          document.addEventListener('change', function(e) {
            if (e.target.classList.contains('ticket-checkbox') || e.target.id === 'select-all') {
              const selectedCount = document.querySelectorAll('.ticket-checkbox:checked').length;
              const deleteBtn = document.getElementById('delete-selected');

              if (selectedCount > 0) {
                deleteBtn.classList.add('show');
                deleteBtn.innerHTML = `<i class="fas fa-trash"></i> Delete Selected (${selectedCount})`;
              } else {
                deleteBtn.classList.remove('show');
              }
            }
          });
        </script>
      </section>

      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->

      <!-- Tickets PD Section -->
      <section id="tickets-pd" class="dashboard-section">
        <div class="dashboard-header">
          <h2>Ticket Barcode Management</h2>
          <div class="filter-controls">
            <div class="search-bar">
              <input type="text" id="barcode-search" placeholder="Search barcodes...">
              <button id="search-barcodes"><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-group">
              <label for="scan-status-filter">Scan Status:</label>
              <select id="scan-status-filter">
                <option value="all">All Statuses</option>
                <option value="scanned">Scanned</option>
                <option value="unscanned">Unscanned</option>
              </select>
            </div>
            <button class="btn btn-secondary" id="reset-barcode-filters">
              <i class="fas fa-sync-alt"></i> Reset
            </button>
          </div>
        </div>

        <div class="stats-container">
          <div class="stat-card">
            <h3>Total Barcodes</h3>
            <div class="value">
              <?php
              $totalBarcodesQuery = "SELECT COUNT(*) as total FROM ticket_barcodes";
              $totalBarcodesResult = mysqli_query($conn, $totalBarcodesQuery);
              $totalBarcodes = mysqli_fetch_assoc($totalBarcodesResult);
              echo number_format($totalBarcodes['total'] ?? 0);
              ?>
            </div>
          </div>
          <div class="stat-card">
            <h3>Scanned</h3>
            <div class="value">
              <?php
              $scannedBarcodesQuery = "SELECT COUNT(*) as scanned FROM ticket_barcodes WHERE scan_status = 'scanned'";
              $scannedBarcodesResult = mysqli_query($conn, $scannedBarcodesQuery);
              $scannedBarcodes = mysqli_fetch_assoc($scannedBarcodesResult);
              echo number_format($scannedBarcodes['scanned'] ?? 0);
              ?>
            </div>
          </div>
          <div class="stat-card">
            <h3>Unscanned</h3>
            <div class="value">
              <?php
              $unscannedBarcodesQuery = "SELECT COUNT(*) as unscanned FROM ticket_barcodes WHERE scan_status = 'unscanned'";
              $unscannedBarcodesResult = mysqli_query($conn, $unscannedBarcodesQuery);
              $unscannedBarcodes = mysqli_fetch_assoc($unscannedBarcodesResult);
              echo number_format($unscannedBarcodes['unscanned'] ?? 0);
              ?>
            </div>
          </div>
        </div>

        <div class="data-table">
          <table>
            <thead>
              <tr>
                <th>Barcode ID</th>
                <th>Ticket ID</th>
                <th>Barcode Value</th>
                <th>Scan Status</th>
                <th>Scan Time</th>
                <th>Scanned By</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Handle barcode filtering
              $barcode_filter_where = "";
              $barcode_filter_params = [];
              $barcode_filter_types = "";

              if (isset($_GET['filter_barcodes'])) {
                if (isset($_GET['scan_status']) && $_GET['scan_status'] != 'all') {
                  $barcode_filter_where .= " AND scan_status = ?";
                  $barcode_filter_params[] = $_GET['scan_status'];
                  $barcode_filter_types .= 's';
                }

                if (isset($_GET['search']) && !empty($_GET['search'])) {
                  $search = "%" . $_GET['search'] . "%";
                  $barcode_filter_where .= " AND (barcode_value LIKE ? OR ticket_id LIKE ?)";
                  $barcode_filter_params[] = $search;
                  $barcode_filter_params[] = $search;
                  $barcode_filter_types .= 'ss';
                }
              }

              // Get all barcodes
              $barcodes_query = "SELECT tb.*, u.full_name as scanned_by_name 
                                 FROM ticket_barcodes tb
                                 LEFT JOIN users u ON tb.scanned_by = u.id
                                 WHERE 1=1 $barcode_filter_where
                                 ORDER BY tb.id DESC";

              if (!empty($barcode_filter_params)) {
                $stmt = mysqli_prepare($conn, $barcodes_query);
                mysqli_stmt_bind_param($stmt, $barcode_filter_types, ...$barcode_filter_params);
                mysqli_stmt_execute($stmt);
                $barcodes_result = mysqli_stmt_get_result($stmt);
              } else {
                $barcodes_result = mysqli_query($conn, $barcodes_query);
              }

              if ($barcodes_result && mysqli_num_rows($barcodes_result) > 0) {
                while ($barcode = mysqli_fetch_assoc($barcodes_result)) {
                  echo '<tr>';
                  echo '<td>' . $barcode['id'] . '</td>';
                  echo '<td>VT' . str_pad($barcode['ticket_id'], 4, '0', STR_PAD_LEFT) . '</td>';
                  echo '<td>' . htmlspecialchars($barcode['barcode_value']) . '</td>';
                  echo '<td><span class="status-badge status-' . $barcode['scan_status'] . '">' .
                    ucfirst($barcode['scan_status']) . '</span></td>';
                  echo '<td>' . ($barcode['scan_time'] ? date('M j, Y H:i', strtotime($barcode['scan_time'])) : '-') . '</td>';
                  echo '<td>' . ($barcode['scanned_by_name'] ? htmlspecialchars($barcode['scanned_by_name']) : '-') . '</td>';
                  echo '</tr>';
                }
              } else {
                echo '<tr><td colspan="6">No barcode records found</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>


        <style>
          /* Barcode Status Badges */
          .status-badge.status-scanned {
            background-color: #28a745;
            color: white;
          }

          .status-badge.status-unscanned {
            background-color: #6c757d;
            color: white;
          }

          /* Barcode Table Styles */
          #tickets-pd table td:nth-child(3) {
            font-family: monospace;
            font-size: 0.9em;
          }

          #tickets-pd table td:nth-child(4) {
            text-align: center;
          }
        </style>



        <script>
          // Add to your existing JavaScript
          function initBarcodeFilters() {
            const barcodeSearch = document.getElementById('barcode-search');
            const scanStatusFilter = document.getElementById('scan-status-filter');
            const resetFiltersBtn = document.getElementById('reset-barcode-filters');

            barcodeSearch.addEventListener('input', debounce(filterBarcodes, 300));
            scanStatusFilter.addEventListener('change', filterBarcodes);
            resetFiltersBtn.addEventListener('click', resetBarcodeFilters);
          }

          function filterBarcodes() {
            const searchTerm = document.getElementById('barcode-search').value;
            const scanStatusFilter = document.getElementById('scan-status-filter').value;

            const params = new URLSearchParams();
            if (searchTerm) params.append('search', searchTerm);
            if (scanStatusFilter !== 'all') params.append('scan_status', scanStatusFilter);
            params.append('filter_barcodes', '1');

            fetch(`manager.php?${params.toString()}`)
              .then(response => response.text())
              .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('#tickets-pd tbody');
                if (newTable) {
                  document.querySelector('#tickets-pd tbody').innerHTML = newTable.innerHTML;
                }
              })
              .catch(error => console.error('Error:', error));
          }

          function resetBarcodeFilters() {
            document.getElementById('barcode-search').value = '';
            document.getElementById('scan-status-filter').value = 'all';
            filterBarcodes();
          }

          // Add initBarcodeFilters to your DOMContentLoaded event listener
          document.addEventListener('DOMContentLoaded', function() {
            // ... existing code ...
            initBarcodeFilters();
          });
        </script>

      </section>

      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->



      <!-- Van Management Section -->
    <section id="van-management" class="dashboard-section">
        <div class="dashboard-header">
          <h2>Van Management</h2>
          <div class="filter-controls">
            <div class="search-bar">
              <input type="text" id="van-search" placeholder="Search vans...">
              <button id="search-vans"><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-group">
              <label for="van-status-filter">Status:</label>
              <select id="van-status-filter">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="filter-group">
              <label for="terminal-filter">Terminal:</label>
              <select id="terminal-filter">
                <option value="all">All Terminals</option>
                <?php foreach ($terminals as $terminal): ?>
                  <option value="<?php echo $terminal['id']; ?>">
                    <?php echo htmlspecialchars($terminal['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <button class="btn btn-secondary" id="reset-van-filters">
              <i class="fas fa-sync-alt"></i> Reset
            </button>
          </div>
          <button class="btn btn-primary" id="add-van-btn">
            <i class="fas fa-plus"></i> Add New Van
          </button>
        </div>

        <div class="stats-container">
          <div class="stat-cardvan">
            <h3>Active Vans</h3>
            <div class="value"><?php echo $stats['active_vans']; ?></div>
          </div>
          <div class="stat-cardvan">
            <h3>In Maintenance</h3>
            <div class="value"><?php echo $stats['maintenance_vans']; ?></div>
          </div>
          <div class="stat-cardvan">
            <h3>Available Today</h3>
            <div class="value"><?php echo $stats['available_vans']; ?></div>
          </div>
          <div class="stat-cardvan">
            <h3>Inactive</h3>
            <div class="value"><?php echo $stats['inactive_vans']; ?></div>
          </div>
        </div>

        <div class="data-table">
          <table id="vans-table">
            <thead>
              <tr>
                <th>Van ID</th>
                <th>License Plate</th>
                <th>Model</th>
                <th>Terminal</th>
                <th>Status</th>
                <th>Counts</th>
                <th>Driver</th>
                <th>Date Added</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vans as $van): ?>
                <tr data-van-id="<?php echo htmlspecialchars($van['id']); ?>">
                  <td><?php echo htmlspecialchars($van['id']); ?></td>
                  <td><?php echo htmlspecialchars($van['license_plate']); ?></td>
                  <td><?php echo htmlspecialchars($van['model']); ?></td>
                  <td><?php echo htmlspecialchars($van['terminal_name']); ?></td>




<td>
    <!-- Status dropdown (unchanged) -->
    <form method="post" class="van-status-form">
        <input type="hidden" name="van_id" value="<?php echo htmlspecialchars($van['id']); ?>">
        <div class="status-badge-container">
            <span class="status-badge status-<?php echo $van['status']; ?>"
                onclick="showStatusOptions(this)"
                data-current-status="<?php echo $van['status']; ?>">
                <?php echo ucfirst($van['status']); ?>
            </span>
            <div class="status-options">
                <span class="status-option status-active" onclick="changeVanStatus(this, 'active')">Active</span>
                <span class="status-option status-maintenance" onclick="changeVanStatus(this, 'maintenance')">Maintenance</span>
                <span class="status-option status-inactive" onclick="changeVanStatus(this, 'inactive')">Inactive</span>
            </div>
        </div>
    </form>
</td>

<td>
    <!-- Boundary controls - now always visible but conditionally enabled -->
    <div class="boundary-controls">
        <button class="btn btn-sm btn-success increment-boundary" 
                data-van-id="<?php echo htmlspecialchars($van['id']); ?>"
                title="Increment Boundary"
                <?php echo $van['status'] !== 'active' ? 'disabled' : ''; ?>>
            <i class="fas fa-plus"></i>
        </button>
        <span class="boundary-amount">
            <?php echo $van['current_boundary'] ?? 0; ?> counts
        </span>
        <button class="btn btn-sm btn-danger decrement-boundary" 
                data-van-id="<?php echo htmlspecialchars($van['id']); ?>"
                title="Decrement Boundary"
                <?php echo $van['status'] !== 'active' ? 'disabled' : ''; ?>>
            <i class="fas fa-minus"></i>
        </button>
    </div>
    <div class="boundary-info">
        <small class="<?php echo $van['status'] !== 'active' ? 'text-muted' : ''; ?>">
            <?php 
            $isToday = (isset($van['boundary_date']) && $van['boundary_date'] == date('Y-m-d'));
            echo $isToday ? ($van['boundary_count'] ?? 0) . ' today' : 'No payments today';
            ?>
            <?php if($van['status'] !== 'active'): ?>
                <br><span class="text-warning">(Van not active)</span>
            <?php endif; ?>
        </small>
    </div>
</td>




                  <td><?php echo htmlspecialchars($van['driver_name'] ?? '-'); ?></td>
                  <td><?php echo $van['last_maintenance'] ? date('Y-m-d', strtotime($van['last_maintenance'])) : '-'; ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-danger btn-sm delete-van-btn"
                        data-van-id="<?php echo htmlspecialchars($van['id']); ?>">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->


<!-- Add this section to your manager.php file, typically after the Van Management section -->
<section id="boundary-management" class="dashboard-section">
    <div class="dashboard-header">
        <h2>Van Boundary Management</h2>
        <div class="filter-controls">
            <div class="search-bar">
                <input type="text" id="boundary-search" placeholder="Search boundaries...">
                <button id="search-boundaries"><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-group">
                <label for="boundary-date-filter">Date:</label>
                <input type="date" id="boundary-date-filter" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="filter-group">
                <label for="boundary-terminal-filter">Terminal:</label>
                <select id="boundary-terminal-filter">
                    <option value="all">All Terminals</option>
                    <?php foreach ($terminals as $terminal): ?>
                        <option value="<?php echo $terminal['id']; ?>">
                            <?php echo htmlspecialchars($terminal['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-secondary" id="reset-boundary-filters">
                <i class="fas fa-sync-alt"></i> Reset
            </button>
            <button class="btn btn-primary" id="generate-boundary-report">
                <i class="fas fa-file-pdf"></i> Generate Report
            </button>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <h3>Today's Boarded Vans</h3>
            <div class="value">
                <?php 
                $today = date('Y-m-d');
                $query = "SELECT COUNT(DISTINCT van_id) as count 
                          FROM van_boundaries 
                          WHERE DATE(boundary_time) = '$today'";
                $result = mysqli_query($conn, $query);
                $count = mysqli_fetch_assoc($result);
                echo $count['count'] ?? 0;
                ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>This Month's Boarded Vans</h3>
            <div class="value">
                <?php 
                $monthStart = date('Y-m-01');
                $query = "SELECT COUNT(DISTINCT van_id) as count 
                          FROM van_boundaries 
                          WHERE DATE(boundary_time) >= '$monthStart'";
                $result = mysqli_query($conn, $query);
                $count = mysqli_fetch_assoc($result);
                echo $count['count'] ?? 0;
                ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>Last Month's Total</h3>
            <div class="value">
                <?php 
                $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
                $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
                $query = "SELECT COUNT(DISTINCT van_id) as count 
                          FROM van_boundaries 
                          WHERE DATE(boundary_time) BETWEEN '$lastMonthStart' AND '$lastMonthEnd'";
                $result = mysqli_query($conn, $query);
                $count = mysqli_fetch_assoc($result);
                echo $count['count'] ?? 0;
                ?>
            </div>
        </div>
    </div>

    <div class="active-vans-boundary">
    <h3>Active Vans Boundary Status</h3>


    <div class="van-boundary-cards">
        <?php
        $query = "SELECT v.id, v.license_plate, v.driver_name, v.current_boundary, 
                         v.boundary_count, t.name as terminal_name
                  FROM vans v
                  JOIN terminals t ON v.terminal_id = t.id
                  WHERE v.status = 'active'
                  ORDER BY t.name, v.id";
        $result = mysqli_query($conn, $query);
        
        while ($van = mysqli_fetch_assoc($result)) {
$isToday = (isset($van['boundary_date']) && $van['boundary_date'] !== null && $van['boundary_date'] == date('Y-m-d'));
            $boundaryClass = $isToday ? 'has-boundary' : 'no-boundary';
            ?>


          <div class="van-boundary-card <?php echo $isToday ? 'has-boundary' : 'no-boundary'; ?>">
    <div class="van-info">
        <span class="van-id"><?php echo htmlspecialchars($van['id']); ?></span>
        <span class="license-plate"><?php echo htmlspecialchars($van['license_plate']); ?></span>
        <span class="terminal"><?php echo htmlspecialchars($van['terminal_name']); ?></span>
    </div>
    <div class="boundary-status">
        <div class="boundary-amount">
            <?php echo $van['current_boundary'] ?? 0; ?> counts
        </div>
        <div class="boundary-count">
            <?php echo $isToday ? ($van['boundary_count'] ?? 0) . ' trips' : 'No trips today'; ?>
        </div>
    </div>
    <?php if (!empty($van['driver_name'])): ?>
        <div class="driver-name">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($van['driver_name']); ?>
        </div>
    <?php endif; ?>
</div>

            
        <?php } ?>
    </div>
</div>



    <!-- Boundary Report Modal -->
    <div class="modal" id="boundary-report-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Generate Boundary Report</h3>
                <button class="close-modal">&times;</button>
            </div>
            <form id="boundary-report-form" method="post" action="generate_boundary_report.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="report-month">Month:</label>
                        <select id="report-month" name="month" required>
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                $monthName = date('F', mktime(0, 0, 0, $i, 1));
                                $selected = ($i == date('n')) ? 'selected' : '';
                                echo "<option value='$i' $selected>$monthName</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="report-year">Year:</label>
                        <select id="report-year" name="year" required>
                            <?php
                            $currentYear = date('Y');
                            for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                $selected = ($i == $currentYear) ? 'selected' : '';
                                echo "<option value='$i' $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="report-terminal">Terminal (optional):</label>
                        <select id="report-terminal" name="terminal_id">
                            <option value="all">All Terminals</option>
                            <?php foreach ($terminals as $terminal): ?>
                                <option value="<?php echo $terminal['id']; ?>">
                                    <?php echo htmlspecialchars($terminal['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="report-format">Format:</label>
                        <select id="report-format" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-boundary-report">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Boundary Modal -->
    <div class="modal" id="boundary-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="boundary-modal-title">Record Van Boundary</h3>
                <button class="close-modal">&times;</button>
            </div>
            <form id="boundary-form" method="post" action="save_boundary.php">
                <input type="hidden" name="boundary_id" id="boundary-id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="boundary-van-id">Van:</label>
                        <select id="boundary-van-id" name="van_id" required>
                            <option value="">-- Select Van --</option>
                            <?php 
                            $vansQuery = "SELECT v.id, v.license_plate, v.driver_name, t.name as terminal_name 
                                         FROM vans v 
                                         JOIN terminals t ON v.terminal_id = t.id 
                                         WHERE v.status = 'active' 
                                         ORDER BY t.name, v.id";
                            $vansResult = mysqli_query($conn, $vansQuery);
                            while ($van = mysqli_fetch_assoc($vansResult)) {
                                echo '<option value="' . $van['id'] . '">';
                                echo htmlspecialchars($van['id'] . ' - ' . $van['license_plate'] . ' (' . $van['terminal_name'] . ')');
                                if ($van['driver_name']) {
                                    echo ' - ' . htmlspecialchars($van['driver_name']);
                                }
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="boundary-passengers">Passenger Count:</label>
                            <input type="number" id="boundary-passengers" name="passenger_count" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="boundary-amount">Boundary Amount (₱):</label>
                            <input type="number" id="boundary-amount" name="boundary_amount" step="0" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="boundary-notes">Notes:</label>
                        <textarea id="boundary-notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-boundary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Boundary</button>
                </div>
            </form>
        </div>
    </div>
</section>


<script>

// Add this to your existing JavaScript
function initBoundaryControls() {
    // Increment boundary
    document.addEventListener('click', function(e) {
        if (e.target.closest('.increment-boundary')) {
            const vanId = e.target.closest('.increment-boundary').dataset.vanId;
            adjustBoundary(vanId, 'increment');
        }
        
        if (e.target.closest('.decrement-boundary')) {
            const vanId = e.target.closest('.decrement-boundary').dataset.vanId;
            adjustBoundary(vanId, 'decrement');
        }
    });
}

function adjustBoundary(vanId, action) {
    const amount = prompt(`Enter count to ${action}:`, '1');
    if (amount === null || isNaN(amount) || parseInt(amount) <= 0) {
        alert('Please enter a valid whole number (1 or more)');
        return;
    }

    // Add CSRF token to the request
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    fetch('adjust_boundary.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `van_id=${vanId}&action=${action}&amount=${parseInt(amount)}&csrf_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the UI
            const row = document.querySelector(`tr[data-van-id="${vanId}"]`);
            if (row) {
                const boundaryAmount = row.querySelector('.boundary-amount');
                const boundaryInfo = row.querySelector('.boundary-info small');
                
                // Changed to display whole numbers only
                boundaryAmount.textContent = `${parseInt(data.new_boundary)} counts`;
                boundaryInfo.textContent = `${parseInt(data.boundary_count)} today`;
                
                showSuccessMessage(data.message);
            }
        } else {
            showErrorMessage(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Error updating boundary count');
    });
}













    // Initialize boundary management
    function initBoundaryManagement() {
        // Boundary filters
        const boundarySearch = document.getElementById('boundary-search');
        const boundaryDateFilter = document.getElementById('boundary-date-filter');
        const boundaryTerminalFilter = document.getElementById('boundary-terminal-filter');
        const resetBoundaryFiltersBtn = document.getElementById('reset-boundary-filters');

        boundarySearch.addEventListener('input', debounce(filterBoundaries, 300));
        boundaryDateFilter.addEventListener('change', filterBoundaries);
        boundaryTerminalFilter.addEventListener('change', filterBoundaries);
        resetBoundaryFiltersBtn.addEventListener('click', resetBoundaryFilters);

        // Report generation
        document.getElementById('generate-boundary-report').addEventListener('click', function() {
            showModal('boundary-report-modal');
        });

        // Boundary modal
        document.getElementById('cancel-boundary-report').addEventListener('click', function() {
            hideModal('boundary-report-modal');
        });

        // Add new boundary
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-boundary')) {
                const boundaryId = e.target.getAttribute('data-boundary-id');
                showEditBoundaryModal(boundaryId);
            }
        });

        // Boundary form submission
        document.getElementById('boundary-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitBoundaryForm(this);
        });
    }

    // Filter boundaries
    function filterBoundaries() {
        const searchTerm = document.getElementById('boundary-search').value;
        const dateFilter = document.getElementById('boundary-date-filter').value;
        const terminalFilter = document.getElementById('boundary-terminal-filter').value;

        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (dateFilter) params.append('date', dateFilter);
        if (terminalFilter !== 'all') params.append('terminal', terminalFilter);

        fetch(`get_boundaries.php?${params.toString()}`)
            .then(response => response.text())
            .then(html => {
                document.querySelector('#boundaries-table tbody').innerHTML = html;
            })
            .catch(error => console.error('Error:', error));
    }

    // Reset boundary filters
    function resetBoundaryFilters() {
        document.getElementById('boundary-search').value = '';
        document.getElementById('boundary-date-filter').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('boundary-terminal-filter').value = 'all';
        filterBoundaries();
    }

    // Show edit boundary modal
    function showEditBoundaryModal(boundaryId) {
        fetch(`get_boundary.php?id=${boundaryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('boundary-modal-title').textContent = 'Edit Boundary Record';
                    document.getElementById('boundary-id').value = data.boundary.id;
                    document.getElementById('boundary-van-id').value = data.boundary.van_id;
                    document.getElementById('boundary-passengers').value = data.boundary.passenger_count;
                    document.getElementById('boundary-amount').value = data.boundary.boundary_amount;
                    document.getElementById('boundary-notes').value = data.boundary.notes || '';
                    showModal('boundary-modal');
                } else {
                    showErrorMessage(data.message || 'Error loading boundary data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Error loading boundary data');
            });
    }

    // Submit boundary form
    function submitBoundaryForm(form) {
        const formData = new FormData(form);
        const boundaryId = formData.get('boundary_id');
        const isEdit = !!boundaryId;

        fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message);
                    hideModal('boundary-modal');
                    filterBoundaries(); // Refresh the table
                } else {
                    showErrorMessage(data.message || 'Error saving boundary');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Error saving boundary');
            });
    }

    // Call this in your main DOMContentLoaded event listener
    document.addEventListener('DOMContentLoaded', function() {
        initBoundaryManagement();
    });
</script>


      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->


      <!-- Destination Management Section -->
      <section id="destination-management" class="dashboard-section">
        <div class="dashboard-header">
          <h2>Destinations</h2>
          <div class="filter-controls">
            <form method="GET" id="destination-search-form">
              <div class="search-bar">
                <input type="text" id="destination-search" name="search" placeholder="Search destinations..."
                  value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" id="search-destinations"><i class="fas fa-search"></i></button>
              </div>

            </form>
          </div>
        </div>

        <div class="stats-container">
          <?php
          // Get top destination
          $topDestinationQuery = "SELECT d.name, COUNT(t.id) as ticket_count 
                          FROM destinations d
                          LEFT JOIN tickets t ON d.id = t.destination_id
                          GROUP BY d.id
                          ORDER BY ticket_count DESC
                          LIMIT 1";
          $topDestinationResult = mysqli_query($conn, $topDestinationQuery);
          $topDestination = mysqli_fetch_assoc($topDestinationResult);

          // Get total destinations
          $totalDestinationsQuery = "SELECT COUNT(*) as total FROM destinations";
          $totalDestinationsResult = mysqli_query($conn, $totalDestinationsQuery);
          $totalDestinations = mysqli_fetch_assoc($totalDestinationsResult);

          // Get total tickets sold across all destinations
          $totalTicketsQuery = "SELECT COUNT(*) as total FROM tickets";
          $totalTicketsResult = mysqli_query($conn, $totalTicketsQuery);
          $totalTickets = mysqli_fetch_assoc($totalTicketsResult);
          ?>

          <div class="stat-carddes">
            <h3>Total Destinations</h3>
            <div class="value"><?php echo $totalDestinations['total']; ?></div>
          </div>
          <div class="stat-carddes">
            <h3>Total Tickets Sold</h3>
            <div class="value"><?php echo $totalTickets['total']; ?></div>
          </div>
          <div class="stat-carddes">
            <h3>Top Destination</h3>
            <div class="value"><?php echo $topDestination ? htmlspecialchars($topDestination['name']) : 'N/A'; ?></div>
            <div class="subtext"><?php echo $topDestination ? $topDestination['ticket_count'] . ' tickets' : ''; ?></div>
          </div>
        </div>

        <div class="data-table">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Destination Name</th>
                <th>Base Fare (₱)</th>
                <th>Tickets Sold</th>

              </tr>
            </thead>
            <tbody>
              <?php while ($destination = mysqli_fetch_assoc($destinationsResult)): ?>
                <tr>
                  <td><?php echo $destination['id']; ?></td>
                  <td><?php echo htmlspecialchars($destination['name']); ?></td>
                  <td>₱<?php echo number_format($destination['base_fare'], 2); ?></td>
                  <td><?php echo $destination['ticket_count']; ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </section>



      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->

      <!-- Financial Reports Section -->
      <section id="reports" class="dashboard-section">
        <div class="dashboard-header">
          <h2>Financial Reports</h2>
          <div class="report-actions">
            <button class="btn btn-secondary" id="print-report-btn">
              <i class="fas fa-print"></i> Print Report
            </button>
            <button class="btn btn-secondary" id="export-report-btn">
              <i class="fas fa-file-export"></i> Export
            </button>
          </div>
        </div>

        <div class="report-controls">
          <form method="post" id="report-form">
            <div class="form-row">
              <div class="form-group">
                <label for="report-type">Report Type</label>
                <select id="report-type" name="report_type" required>
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </div>
              <div class="form-group">
                <label for="start-date">Start Date</label>
                <input type="date" id="start-date" name="start_date" required
                  value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
              </div>
              <div class="form-group">
                <label for="end-date">End Date</label>
                <input type="date" id="end-date" name="end_date" required
                  value="<?php echo date('Y-m-d'); ?>">
              </div>
              <button type="submit" class="btn btn-primary" name="generate_report">
                <i class="fas fa-chart-bar"></i> Generate Report
              </button>
            </div>
          </form>
        </div>

        <div class="saved-reports">
          <div class="saved-reports-header">
            <h3>Recent Reports</h3>


            <div class="report-filters-container">
              <div class="report-filters">
                <!-- Search Bar -->

                <!-- Type Filter -->
                <div class="filter-group">
                  <div class="custom-select">
                    <select id="report-type-filter" aria-label="Filter by report type">
                      <option value="all">All Types</option>
                      <option value="daily">Daily</option>
                      <option value="weekly">Weekly</option>
                      <option value="monthly">Monthly</option>
                      <option value="yearly">Yearly</option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
                  </div>
                </div>

                <!-- Reset Button -->
                <div class="filter-group">
                  <button class="btn btn-secondary reset-btn" id="reset-report-filters">
                    <i class="fas fa-sync-alt"></i>
                    <span>Reset Filters</span>
                  </button>
                </div>
              </div>

              <!-- Active Filters Display -->
              <div class="active-filters" id="active-filters"></div>
            </div>



          </div>

          <div class="reports-grid">
            <?php foreach ($saved_reports as $report): ?>
              <div class="report-card">
                <div class="report-card-header">
                  <span class="report-type-badge"><?php echo ucfirst($report['report_type']); ?></span>
                  <span class="report-date"><?php echo date('M j, Y', strtotime($report['created_at'])); ?></span>
                </div>
                <div class="report-card-body">
                  <div class="report-period">
                    <?php echo date('M j', strtotime($report['period_start'])); ?> -
                    <?php echo date('M j, Y', strtotime($report['period_end'])); ?>
                  </div>
                  <div class="report-stats">
                    <div class="stat-item">
                      <span class="stat-value">₱<?php echo number_format($report['total_sales'], 2); ?></span>
                      <span class="stat-label">Revenue</span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-value"><?php echo $report['total_tickets']; ?></span>
                      <span class="stat-label">Tickets</span>
                    </div>
                  </div>
                </div>
                <div class="report-card-footer">
                  <form method="post">
                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                    <button type="submit" name="view_report" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye"></i> View
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <br>
        <?php if ($current_report): ?>

          <div class="report-results" id="report-results">
            <div class="report-header">
              <h3>Performance Report</h3>
              <div class="report-meta">
                <span class="report-period">
                  <?php echo date('F j, Y', strtotime($current_report['start_date'])); ?> -
                  <?php echo date('F j, Y', strtotime($current_report['end_date'])); ?>
                </span>
                <span class="report-type">
                  <?php echo ucfirst($current_report['type']); ?> Report
                </span>
              </div>
            </div>

            <div class="detailed-stats">
              <div class="stats-section">
                <h4>Terminal Breakdown</h4>
                <table class="stats-table">
                  <thead>
                    <tr>
                      <th>Terminal</th>
                      <th>Tickets</th>
                      <th>Revenue</th>
                      <th>Avg. Fare</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($current_report['data']['terminal_performance'])): ?>
                      <?php foreach ($current_report['data']['terminal_performance'] as $terminal => $data): ?>
                        <tr>
                          <td><?php echo $terminal; ?></td>
                          <td><?php echo $data['tickets']; ?></td>
                          <td>₱<?php echo number_format($data['sales'], 2); ?></td>
                          <td>₱<?php echo number_format($data['sales'] / max(1, $data['tickets']), 2); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="4">No terminal data available</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="summary-cards">
              <div class="summary-card revenue-card">
                <div class="card-icon">
                  <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-content">
                  <h4>Total Revenue</h4>
                  <div class="value">₱<?php echo number_format($current_report['data']['total_sales'], 2); ?></div>
                  <div class="trend">
                    <i class="fas fa-arrow-up"></i> 12% from last period
                  </div>
                </div>
              </div>

              <div class="summary-card tickets-card">
                <div class="card-icon">
                  <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="card-content">
                  <h4>Total Tickets</h4>
                  <div class="value"><?php echo $current_report['data']['total_tickets']; ?></div>
                  <div class="breakdown">
                    <span class="completed"><?php echo $current_report['data']['completed_tickets']; ?> completed</span>
                    <span class="cancelled"><?php echo $current_report['data']['cancelled_tickets']; ?> cancelled</span>
                  </div>
                </div>
              </div>

              <div class="summary-card destinations-card">
                <div class="card-icon">
                  <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="card-content">
                  <h4>Top Destination</h4>
                  <?php if (!empty($current_report['data']['popular_destinations'])): ?>
                    <?php $top_dest = array_key_first($current_report['data']['popular_destinations']); ?>
                    <div class="value"><?php echo $top_dest; ?></div>
                    <div class="subtext">
                      <?php echo $current_report['data']['popular_destinations'][$top_dest]; ?> tickets
                    </div>
                  <?php else: ?>
                    <div class="value">N/A</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="report-charts">
              <div class="chart-row">
                <!-- Revenue Trend Chart -->
                <div class="chart-container" data-chart-type="revenue">
                  <div class="chart-header">
                    <h4>Revenue Trend</h4>

                  </div>
                  <div class="chart-scroll-container">
                    <canvas id="salesChart"></canvas>
                  </div>
                </div>

                <!-- Ticket Status Chart -->
                <div class="chart-container" data-chart-type="status">
                  <div class="chart-header">
                    <h4>Ticket Status</h4>

                  </div>
                  <canvas id="statusChart"></canvas>
                </div>
              </div>

              <div class="chart-row">
                <!-- Daily Tickets Trend Chart -->
                <div class="chart-container" data-chart-type="trend">
                  <div class="chart-header">
                    <h4>Daily Tickets Trend</h4>

                  </div>
                  <div class="chart-scroll-container">
                    <canvas id="dailyTrendChart"></canvas>
                  </div>
                </div>

                <!-- Terminal Performance Chart -->
                <div class="chart-container" data-chart-type="terminal">
                  <div class="chart-header">
                    <h4>Terminal Performance</h4>

                  </div>
                  <canvas id="terminalChart"></canvas>
                </div>
              </div>

              <!-- Top Destinations Chart (full width) -->
              <div class="chart-container full-width" data-chart-type="destination">
                <div class="chart-header">
                  <h4>Top Destinations</h4>

                </div>
                <div class="chart-scroll-container">
                  <canvas id="destinationsChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->


      <!-- Settings Section -->
      <section id="settings" class="dashboard-section">
        <div class="dashboard-header">
          <h2>System Settings</h2>
        </div>

        <div class="settings-group">
          <h3>Quick Actions</h3>
          <div class="form-group">
            <a href="scanner.php" class="btn btn-primary" style="display: inline-block; margin-top: 10px;">
              <i class="fas fa-qrcode"></i> Open Ticket Scanner
            </a>
          </div>
        </div>

        <form method="post" id="settings-form">
          <div class="settings-group">
            <h3>Company Information</h3>
            <div class="form-group">
              <label for="company_name">Company Name</label>
              <input type="text" id="company_name" name="settings[company_name]"
                value="<?php echo htmlspecialchars($settings['company_name']['setting_value']); ?>" required>
            </div>
            <div class="form-group">
              <label for="admin_email">Admin Email</label>
              <input type="email" id="admin_email" name="settings[admin_email]"
                value="<?php echo htmlspecialchars($settings['admin_email']['setting_value']); ?>" required>
            </div>
          </div>

          <div class="settings-group">
            <h3>Fare Settings</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="base_fare">Base Fare (₱)</label>
                <input type="number" step="0.01" id="base_fare" name="settings[base_fare]"
                  value="<?php echo htmlspecialchars($settings['base_fare']['setting_value']); ?>" required>
              </div>
              <div class="form-group">
                <label for="fare_per_km">Fare per Kilometer (₱)</label>
                <input type="number" step="0.01" id="fare_per_km" name="settings[fare_per_km]"
                  value="<?php echo htmlspecialchars($settings['fare_per_km']['setting_value']); ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label for="cancellation_fee">Cancellation Fee (₱)</label>
              <input type="number" step="0.01" id="cancellation_fee" name="settings[cancellation_fee]"
                value="<?php echo htmlspecialchars($settings['cancellation_fee']['setting_value']); ?>" required>
            </div>
          </div>

          <div class="settings-group">
            <h3>Vehicle Settings</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="max_passengers">Max Passengers per Van</label>
                <input type="number" id="max_passengers" name="settings[max_passengers]"
                  value="<?php echo htmlspecialchars($settings['max_passengers']['setting_value']); ?>" required>
              </div>
              <div class="form-group">
                <label for="maintenance_interval">Maintenance Interval (days)</label>
                <input type="number" id="maintenance_interval" name="settings[maintenance_interval]"
                  value="<?php echo htmlspecialchars($settings['maintenance_interval']['setting_value']); ?>" required>
              </div>
            </div>
          </div>



          <div class="form-footer">
            <button type="submit" class="btn btn-primary" name="update_settings">
              <i class="fas fa-save"></i> Save Settings
            </button>
          </div>
        </form>
      </section>

      <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////////////////////////// -->

      <!-- Van Assignment Modal -->
      <div class="modal" id="assign-van-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Assign Van to Ticket</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form method="post" id="assign-van-form">
            <input type="hidden" name="ticket_id" id="assign-ticket-id">
            <div class="modal-body">
              <div class="form-group">
                <label for="assign-van-select">Select Van:</label>
                <select name="van_id" id="assign-van-select" class="form-control" required>
                  <option value="">-- Select a van --</option>
                  <?php foreach ($vans as $van): ?>
                    <?php if ($van['status'] === 'active'): ?>
                      <option value="<?php echo htmlspecialchars($van['id']); ?>">
                        <?php echo htmlspecialchars($van['id']); ?> -
                        <?php echo htmlspecialchars($van['model']); ?> -
                        <?php echo htmlspecialchars($van['terminal_name']); ?>
                        <?php if ($van['driver_name']): ?>
                          (Driver: <?php echo htmlspecialchars($van['driver_name']); ?>)
                        <?php endif; ?>
                      </option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-assign-van">Cancel</button>
              <button type="submit" class="btn btn-primary" name="assign_van">Assign Van</button>
            </div>
          </form>
        </div>
      </div>


      <div class="modal" id="van-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title" id="van-modal-title">Add New Van</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form method="post" id="van-form" action="manager.php#van-management">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="van_id" id="modal-van-id">
            <div class="modal-body">
              <div class="form-row">
                <div class="form-group">
                  <label for="modal-van-id-input">Van ID*</label>
                  <input type="text" id="modal-van-id-input" name="van_id" required>
                </div>
                <div class="form-group">
                  <label for="modal-license-plate">License Plate*</label>
                  <input type="text" id="modal-license-plate" name="license_plate" required>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="modal-model">Model*</label>
                  <input type="text" id="modal-model" name="model" required>
                </div>
                <div class="form-group">
                  <label for="modal-terminal-id">Terminal*</label>
                  <select id="modal-terminal-id" name="terminal_id" required>
                    <?php foreach ($terminals as $terminal): ?>
                      <option value="<?php echo $terminal['id']; ?>">
                        <?php echo htmlspecialchars($terminal['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="modal-status">Status*</label>
                  <select id="modal-status" name="status" required>
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="modal-driver-name">Driver Name</label>
                  <input type="text" id="modal-driver-name" name="driver_name">
                </div>
              </div>
              <div class="form-group">
                <label for="modal-notes">Notes</label>
                <textarea id="modal-notes" name="notes" rows="3"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary" id="van-submit-btn" name="add_van">
                Save Van
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Delete Van Modal -->
      <div class="modal" id="delete-van-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Confirm Van Deletion</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form method="post" id="delete-van-form">
            <input type="hidden" name="van_id" id="delete-van-id">
            <div class="modal-body">
              <p>Are you sure you want to delete this van? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-delete-van">Cancel</button>
              <button type="submit" class="btn btn-danger" name="delete_van">Delete Van</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="disableclick.js"></script>


  <script>
    document.addEventListener('DOMContentLoaded', function() {
      initNavigation();
      initTicketFilters();
      initVanFilters();
      initVanManagement();
      initLogoutConfirmation();
      initReportDatePickers();
      initSettingsForm();
      initReportFilters();
      initDestinationManagement();
      generateReport();


      // Apply initial filters
      filterTickets();
      filterVans();
      filterReports();

      // Initialize report features
      initReportFeatures();

      // Restore last viewed section
      restoreReportState();



      document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
          hideModal(this.closest('.modal').id);
        });
      });
      // Initialize charts if report data exists
      <?php if (isset($current_report)): ?>
        // Initialize charts if report data exists
        document.addEventListener('DOMContentLoaded', function() {
          renderCharts();
        });
      <?php endif; ?>
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Add this to your JavaScript section
    document.addEventListener('DOMContentLoaded', function() {
      // Add hover effect to stars in the ratings card
      const stars = document.querySelectorAll('.stat-card3 .star');
      stars.forEach(star => {
        star.addEventListener('mouseover', function() {
          const rating = parseInt(this.getAttribute('data-rating') || this.parentElement.getAttribute('data-rating') || 0);
          highlightStars(rating);
        });

        star.addEventListener('mouseout', function() {
          resetStarColors();
        });
      });

      function highlightStars(rating) {
        stars.forEach((star, index) => {
          if (index < rating) {
            star.classList.add('fas');
            star.classList.remove('far');
          } else {
            star.classList.add('far');
            star.classList.remove('fas');
          }
        });
      }

      function resetStarColors() {
        const avgRating = <?php echo $ratings_stats['average_rating']; ?>;
        stars.forEach((star, index) => {
          if (index < Math.floor(avgRating)) {
            star.classList.add('fas');
            star.classList.remove('far');
          } else if (index === Math.floor(avgRating) && (avgRating - Math.floor(avgRating)) >= 0.5) {
            star.classList.add('fas', 'fa-star-half-alt');
            star.classList.remove('far', 'fa-star');
          } else {
            star.classList.add('far');
            star.classList.remove('fas', 'fa-star-half-alt');
          }
        });
      }
    });



    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    // In your initNavigation function
    function initNavigation() {
      document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();


          // Update active state
          document.querySelectorAll('.sidebar-menu a').forEach(item => {
            item.classList.remove('active');
          });
          this.classList.add('active');

          // Show corresponding section
          const sectionId = this.getAttribute('data-section');
          document.querySelectorAll('.dashboard-section').forEach(section => {
            section.classList.remove('active');
          });
          document.getElementById(sectionId).classList.add('active');

          // Update URL without reload
          history.pushState(null, null, `#${sectionId}`);
        });
      });

      // Handle browser back/forward buttons
      window.addEventListener('popstate', function() {
        const hash = window.location.hash.substring(1);
        if (hash) {
          document.querySelectorAll('.sidebar-menu a').forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('data-section') === hash) {
              item.classList.add('active');
            }
          });

          document.querySelectorAll('.dashboard-section').forEach(section => {
            section.classList.remove('active');
            if (section.id === hash) {
              section.classList.add('active');
            }
          });
        }
      });

      // Check initial hash on page load
      const initialHash = window.location.hash.substring(1);
      if (initialHash) {
        const matchingLink = document.querySelector(`.sidebar-menu a[data-section="${initialHash}"]`);
        if (matchingLink) {
          document.querySelectorAll('.sidebar-menu a').forEach(item => {
            item.classList.remove('active');
          });
          matchingLink.classList.add('active');

          document.querySelectorAll('.dashboard-section').forEach(section => {
            section.classList.remove('active');
            if (section.id === initialHash) {
              section.classList.add('active');
            }
          });
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Initialize ticket filters
    function initTicketFilters() {
      const ticketSearch = document.getElementById('ticket-search');
      const statusFilter = document.getElementById('status-filter');
      const dateFilter = document.getElementById('date-filter');
      const resetFiltersBtn = document.getElementById('reset-filters');

      ticketSearch.addEventListener('input', debounce(filterTickets, 300));
      statusFilter.addEventListener('change', filterTickets);
      dateFilter.addEventListener('change', filterTickets);
      resetFiltersBtn.addEventListener('click', resetTicketFilters);
    }

    // Initialize van filters
    function initVanFilters() {
      const vanSearch = document.getElementById('van-search');
      const vanStatusFilter = document.getElementById('van-status-filter');
      const terminalFilter = document.getElementById('terminal-filter');
      const resetVanFiltersBtn = document.getElementById('reset-van-filters');

      vanSearch.addEventListener('input', debounce(filterVans, 300));
      vanStatusFilter.addEventListener('change', filterVans);
      terminalFilter.addEventListener('change', filterVans);
      resetVanFiltersBtn.addEventListener('click', resetVanFilters);
    }

    // Initialize report filters
    function initReportFilters() {
      const reportSearch = document.getElementById('report-search');
      const reportTypeFilter = document.getElementById('report-type-filter');
      const reportDateFilter = document.getElementById('report-date-filter');
      const resetReportFiltersBtn = document.getElementById('reset-report-filters');

      reportSearch.addEventListener('input', debounce(filterReports, 300));
      reportTypeFilter.addEventListener('change', filterReports);
      reportDateFilter.addEventListener('change', filterReports);
      resetReportFiltersBtn.addEventListener('click', resetReportFilters);
    }

    // Initialize van management
    function initVanManagement() {
      // Add van button
      document.getElementById('add-van-btn').addEventListener('click', showAddVanModal);

      // Event delegation for delete and maintenance buttons
      document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-van-btn')) {
          const btn = e.target.closest('.delete-van-btn');
          showDeleteVanModal(btn);
        }

        if (e.target.closest('.btn-maintenance')) {
          const btn = e.target.closest('.btn-maintenance');
          const vanId = btn.dataset.vanId;
          updateMaintenanceDate(vanId);
        }
      });

      ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////

      document.querySelectorAll('.delete-van-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const vanId = this.getAttribute('data-van-id');

          // Show your custom modal instead of using confirm()
          document.getElementById('delete-van-id').value = vanId;
          showModal('delete-van-modal');
        });
      });

      // Handle the modal form submission
      document.getElementById('delete-van-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const submitBtn = form.querySelector('[type="submit"]');
        const vanId = form.querySelector('input[name="van_id"]').value;

        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        submitBtn.disabled = true;

        // Also update the original delete button if needed
        const originalBtn = document.querySelector(`.delete-van-btn[data-van-id="${vanId}"]`);
        if (originalBtn) {
          originalBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
          originalBtn.disabled = true;
        }

        // Add CSRF token for security
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = document.querySelector('input[name="csrf_token"]').value;

        const vanIdInput = document.createElement('input');
        vanIdInput.type = 'hidden';
        vanIdInput.name = 'van_id';
        vanIdInput.value = vanId;

        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_van';
        deleteInput.value = '1';

        form.appendChild(csrfInput);
        form.appendChild(vanIdInput);
        form.appendChild(deleteInput);
        document.body.appendChild(form);

        // Submit the form
        form.submit();
      });

      ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////

      //modals function for van  deletion

      // Cancel delete button
      document.getElementById('cancel-delete-van').addEventListener('click', function() {
        hideModal('delete-van-modal');
      });

      // Modal close handlers
      document.querySelectorAll('.close-modal, .cancel-modal').forEach(btn => {
        btn.addEventListener('click', function() {
          hideModal(this.closest('.modal').id);
        });
      });

      // Close modal when clicking outside
      document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
          if (e.target === this) {
            hideModal(this.id);
          }
        });
      });
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////


    // Initialize report date pickers
    function initReportDatePickers() {
      const reportType = document.getElementById('report-type');
      const startDate = document.getElementById('start-date');
      const endDate = document.getElementById('end-date');

      // Set default dates based on report type
      function setDefaultDates() {
        const endDateValue = new Date(endDate.value || new Date());
        let startDateValue = new Date(endDateValue);

        switch (reportType.value) {
          case 'daily':
            startDateValue = endDateValue;
            break;
          case 'weekly':
            startDateValue.setDate(startDateValue.getDate() - 6);
            break;
          case 'monthly':
            startDateValue.setDate(1);
            break;
          case 'yearly':
            startDateValue.setMonth(0);
            startDateValue.setDate(1);
            break;
        }

        startDate.valueAsDate = startDateValue;
      }

      reportType.addEventListener('change', setDefaultDates);
      endDate.addEventListener('change', setDefaultDates);

      // Trigger change to set initial values
      reportType.dispatchEvent(new Event('change'));
    }

    // Initialize report features
    function initReportFeatures() {
      // Print report button
      document.getElementById('print-report-btn').addEventListener('click', function() {
        if (document.getElementById('report-results')) {
          window.print();
        } else {
          alert('Please generate a report first.');
        }
      });

      // Export report button
      document.getElementById('export-report-btn').addEventListener('click', function() {
        if (!document.getElementById('report-results')) {
          alert('Please generate a report first.');
          return;
        }

        // Create CSV content
        let csvContent = "data:text/csv;charset=utf-8,";

        // Add report header
        const reportHeader = document.querySelector('.report-header');
        csvContent += "VanTastic Report," + reportHeader.querySelector('.report-period').textContent + "\n";
        csvContent += reportHeader.querySelector('.report-type').textContent + "\n\n";

        // Add summary cards
        const summaryCards = document.querySelectorAll('.summary-card');
        summaryCards.forEach(card => {
          const title = card.querySelector('h4').textContent;
          const value = card.querySelector('.value').textContent;
          csvContent += title + "," + value + "\n";
        });
        csvContent += "\n";

        // Add detailed stats
        const statsTables = document.querySelectorAll('.stats-table');
        statsTables.forEach(table => {
          const title = table.previousElementSibling.textContent;
          csvContent += title + "\n";

          // Add headers
          const headers = [];
          table.querySelectorAll('thead th').forEach(th => {
            headers.push(th.textContent);
          });
          csvContent += headers.join(",") + "\n";

          // Add rows
          table.querySelectorAll('tbody tr').forEach(tr => {
            const row = [];
            tr.querySelectorAll('td').forEach(td => {
              row.push(td.textContent.replace(/,/g, '').trim());
            });
            csvContent += row.join(",") + "\n";
          });
          csvContent += "\n";
        });

        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "vantastic_report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      });

      // Chart type toggle buttons
      document.querySelectorAll('.chart-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const chartId = this.getAttribute('data-chart');
          const chartType = this.getAttribute('data-type');

          if (window[chartId]) {
            window[chartId].config.type = chartType;
            window[chartId].update();
          }
        });
      });
    }

    // Save report state to localStorage
    function saveReportState() {
      const reportSection = document.getElementById('reports');
      if (reportSection) {
        localStorage.setItem('lastViewedSection', 'reports');
      }
    }

    // Restore report state from localStorage
    function restoreReportState() {
      const lastSection = localStorage.getItem('lastViewedSection');
      if (lastSection === 'reports') {
        document.querySelector('.sidebar-menu a[data-section="reports"]').click();
      }
    }

    // Initialize settings form
    function initSettingsForm() {
      document.getElementById('settings-form').addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to update these settings?')) {
          e.preventDefault();
        }
      });
    }

    // Initialize logout confirmation with custom dialog
    function initLogoutConfirmation() {
      document.getElementById('logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        showCustomConfirm({
          title: 'Confirm Logout',
          message: 'Are you sure you want to logout?',
          icon: 'fas fa-sign-out-alt logout-confirm-icon',
          onConfirm: () => {
            window.location.href = 'manager.php?logout=1';
          }
        });
      });
    }

    // Filter tickets
    function filterTickets() {
      const searchTerm = document.getElementById('ticket-search').value;
      const statusFilter = document.getElementById('status-filter').value;
      const dateFilter = document.getElementById('date-filter').value;

      const params = new URLSearchParams();
      if (searchTerm) params.append('search', searchTerm);
      if (statusFilter !== 'all') params.append('status', statusFilter);
      if (dateFilter !== 'all') params.append('date_filter', dateFilter);
      params.append('filter_tickets', '1');

      fetch(`manager.php?${params.toString()}`)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newTable = doc.querySelector('#dashboard tbody');
          if (newTable) {
            document.querySelector('#dashboard tbody').innerHTML = newTable.innerHTML;
          }
        })
        .catch(error => console.error('Error:', error));
    }

    // Filter vans
    function filterVans() {
      const searchTerm = document.getElementById('van-search').value;
      const statusFilter = document.getElementById('van-status-filter').value;
      const terminalFilter = document.getElementById('terminal-filter').value;

      const params = new URLSearchParams();
      if (searchTerm) params.append('search', searchTerm);
      if (statusFilter !== 'all') params.append('status', statusFilter);
      if (terminalFilter !== 'all') params.append('terminal', terminalFilter);
      params.append('filter_vans', '1');

      fetch(`manager.php?${params.toString()}`)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newTable = doc.querySelector('#vans-table tbody');
          if (newTable) {
            document.querySelector('#vans-table tbody').innerHTML = newTable.innerHTML;
          }
        })
        .catch(error => console.error('Error:', error));
    }

    // Filter reports
    function filterReports() {
      const searchTerm = document.getElementById('report-search').value.toLowerCase();
      const typeFilter = document.getElementById('report-type-filter').value;
      const dateFilter = document.getElementById('report-date-filter').value;
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      document.querySelectorAll('.report-card').forEach(card => {
        const reportText = card.textContent.toLowerCase();
        const reportType = card.querySelector('.report-type-badge').textContent.toLowerCase();
        const reportDate = new Date(card.querySelector('.report-date').textContent);

        // Check filters
        const matchesSearch = searchTerm === '' || reportText.includes(searchTerm);
        const matchesType = typeFilter === 'all' || reportType === typeFilter.toLowerCase();
        let matchesDate = true;

        if (dateFilter !== 'all') {
          const diffTime = Math.abs(today - reportDate);
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

          switch (dateFilter) {
            case 'today':
              matchesDate = diffDays === 0;
              break;
            case 'week':
              matchesDate = diffDays <= 7;
              break;
            case 'month':
              matchesDate = diffDays <= 30;
              break;
            case 'year':
              matchesDate = diffDays <= 365;
              break;
          }
        }

        // Show/hide card
        card.style.display = matchesSearch && matchesType && matchesDate ? '' : 'none';
      });
    }

    // Reset ticket filters
    function resetTicketFilters() {
      document.getElementById('ticket-search').value = '';
      document.getElementById('status-filter').value = 'all';
      document.getElementById('date-filter').value = 'all';
      filterTickets();
    }

    // Reset van filters
    function resetVanFilters() {
      document.getElementById('van-search').value = '';
      document.getElementById('van-status-filter').value = 'all';
      document.getElementById('terminal-filter').value = 'all';
      filterVans();
    }

    // Reset report filters
    function resetReportFilters() {
      document.getElementById('report-search').value = '';
      document.getElementById('report-type-filter').value = 'all';
      document.getElementById('report-date-filter').value = 'all';
      filterReports();
    }

    // Show add van modal
    function showAddVanModal() {
      document.getElementById('van-modal-title').textContent = 'Add New Van';
      document.getElementById('van-form').reset();
      document.getElementById('modal-van-id-input').removeAttribute('readonly');
      document.getElementById('modal-van-id').value = '';
      document.getElementById('van-form').setAttribute('data-action', 'add');
      showModal('van-modal');
    }

    // Show edit van modal
    function showEditVanModal(button) {
      document.getElementById('van-modal-title').textContent = 'Edit Van';

      // Fill form with van data
      document.getElementById('modal-van-id').value = button.dataset.vanId;
      document.getElementById('modal-van-id-input').value = button.dataset.vanId;
      document.getElementById('modal-license-plate').value = button.dataset.licensePlate;
      document.getElementById('modal-model').value = button.dataset.model;
      document.getElementById('modal-terminal-id').value = button.dataset.terminalId;
      document.getElementById('modal-status').value = button.dataset.status;
      document.getElementById('modal-driver-name').value = button.dataset.driverName || '';
      document.getElementById('modal-notes').value = button.dataset.notes || '';

      document.getElementById('modal-van-id-input').setAttribute('readonly', 'readonly');
      document.getElementById('van-form').setAttribute('data-action', 'edit');
      showModal('van-modal');
    }

    // Show delete van modal
    function showDeleteVanModal(button) {
      document.getElementById('delete-van-id').value = button.dataset.vanId;
      showModal('delete-van-modal');
    }

    function handleVanFormSubmit(form) {
      const isEdit = form.getAttribute('data-action') === 'edit';
      const action = isEdit ? 'update' : 'add';

      // Validate form first
      const validationResult = validateVanForm(form);
      if (!validationResult.isValid) {
        showCustomErrorMessage(validationResult.message);
        return false;
      }

      function showCustomErrorMessage(message) {
        // Remove any existing error messages
        const existingErrors = document.querySelectorAll('.custom-form-error');
        existingErrors.forEach(error => error.remove());

        // Create and show new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'custom-form-error';
        errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;

        // Insert before the form
        form.parentNode.insertBefore(errorDiv, form);

        // Auto-hide after 5 seconds
        setTimeout(() => {
          errorDiv.remove();
        }, 5000);

        // Scroll to the error
        errorDiv.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }
      // Show custom confirmation dialog
      showCustomConfirm({
        title: `Confirm Van ${action.charAt(0).toUpperCase() + action.slice(1)}`,
        message: `Are you sure you want to ${action} this van?`,
        icon: 'fas fa-van-shuttle',
        onConfirm: () => {
          // Show loading state
          const submitBtn = form.querySelector('[type="submit"]');
          const originalText = submitBtn.innerHTML;
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
          submitBtn.disabled = true;

          // Submit the form
          form.submit();
        },
        onCancel: () => {
          // Reset form if needed
          if (isEdit) {
            // For edit, keep the current values
            return;
          }
          // For add, optionally clear the form
          form.reset();
        }
      });

      // Prevent default form submission (we'll handle it in the confirmation)
      return false;
    }




    // Reusable custom confirmation dialog function
    function showCustomConfirm({
      title,
      message,
      icon,
      onConfirm,
      onCancel
    }) {
      // Create overlay if it doesn't exist
      let overlay = document.querySelector('.custom-confirm-overlay');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'custom-confirm-overlay';
        overlay.innerHTML = `
                    <div class="custom-confirm-dialog">
                        <div class="custom-confirm-header">
                            <i class="${icon || 'fas fa-question-circle'}"></i>
                            <span>${title || 'Confirm Action'}</span>
                        </div>
                        <div class="custom-confirm-body">
                            ${message || 'Are you sure you want to perform this action?'}
                        </div>
                        <div class="custom-confirm-footer">
                            <button class="custom-confirm-btn custom-confirm-btn-secondary" id="custom-confirm-cancel">
                                Cancel
                            </button>
                            <button class="custom-confirm-btn custom-confirm-btn-primary" id="custom-confirm-ok">
                                OK
                            </button>
                        </div>
                    </div>
                `;
        document.body.appendChild(overlay);
      } else {
        // Update existing dialog content
        overlay.querySelector('.custom-confirm-header i').className = icon || 'fas fa-question-circle';
        overlay.querySelector('.custom-confirm-header span').textContent = title || 'Confirm Action';
        overlay.querySelector('.custom-confirm-body').innerHTML = message || 'Are you sure you want to perform this action?';
      }

      // Show the dialog
      overlay.classList.add('active');

      // Set up event listeners
      const confirmBtn = overlay.querySelector('#custom-confirm-ok');
      const cancelBtn = overlay.querySelector('#custom-confirm-cancel');

      const cleanup = () => {
        confirmBtn.removeEventListener('click', confirmHandler);
        cancelBtn.removeEventListener('click', cancelHandler);
        overlay.classList.remove('active');
      };

      const confirmHandler = () => {
        cleanup();
        if (typeof onConfirm === 'function') onConfirm();
      };

      const cancelHandler = () => {
        cleanup();
        if (typeof onCancel === 'function') onCancel();
      };

      confirmBtn.addEventListener('click', confirmHandler);
      cancelBtn.addEventListener('click', cancelHandler);

      // Close when clicking outside
      overlay.addEventListener('click', function(e) {
        if (e.target === this) {
          cancelHandler();
        }
      });
    }

    // Validate van form
    function validateVanForm(form) {
      const vanId = form.querySelector('#modal-van-id-input').value.trim();
      const licensePlate = form.querySelector('#modal-license-plate').value.trim();
      const model = form.querySelector('#modal-model').value.trim();
      const terminalId = form.querySelector('#modal-terminal-id').value;

      if (!vanId) {
        return {
          isValid: false,
          message: 'Van ID is required'
        };
      }

      if (!licensePlate) {
        return {
          isValid: false,
          message: 'License plate is required'
        };
      }

      if (!model) {
        return {
          isValid: false,
          message: 'Model is required'
        };
      }

      if (!terminalId || terminalId === '0') {
        return {
          isValid: false,
          message: 'Please select a terminal'
        };
      }

      return {
        isValid: true,
        message: ''
      };
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////


    // Confirm ticket status change
    function confirmStatusChange(select) {
      const newStatus = select.value;
      const ticketId = select.form.querySelector('input[name="ticket_id"]').value;
      const currentStatus = select.closest('tr').querySelector('.status-badge').textContent.toLowerCase();

      if (newStatus === currentStatus) return;

      showCustomConfirm({
        title: 'Confirm Status Change',
        message: `Are you sure you want to change this ticket from ${currentStatus} to ${newStatus}?`,
        icon: 'fas fa-ticket-alt ticket-confirm-icon',
        onConfirm: () => {
          select.form.submit();
        },
        onCancel: () => {
          // Reset to original value
          select.value = currentStatus;
        }
      });
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////


// Update your changeVanStatus function to refresh the row after status change
function changeVanStatus(element, newStatus) {
    const form = element.closest('.van-status-form');
    const vanId = form.querySelector('input[name="van_id"]').value;
    const currentStatus = element.closest('.status-badge-container').querySelector('.status-badge').dataset.currentStatus;

    if (newStatus === currentStatus) return;

    if (confirm(`Are you sure you want to change this van's status to ${newStatus}?`)) {
        // Create a form and submit it
        const statusForm = document.createElement('form');
        statusForm.method = 'POST';
        statusForm.action = 'manager.php';

        const vanInput = document.createElement('input');
        vanInput.type = 'hidden';
        vanInput.name = 'van_id';
        vanInput.value = vanId;

        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = newStatus;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'update_van_status';
        actionInput.value = '1';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = document.querySelector('input[name="csrf_token"]').value;

        statusForm.appendChild(vanInput);
        statusForm.appendChild(statusInput);
        statusForm.appendChild(actionInput);
        statusForm.appendChild(csrfInput);
        document.body.appendChild(statusForm);
        statusForm.submit();
    }
}

// Update boundary controls when status changes (via event delegation)
document.addEventListener('DOMContentLoaded', function() {
    // This will work even if the table is reloaded via AJAX
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('van-status-select')) {
            const row = e.target.closest('tr');
            const vanId = row.dataset.vanId;
            const newStatus = e.target.value;
            
            // Enable/disable boundary buttons based on status
            const incBtn = row.querySelector('.increment-boundary');
            const decBtn = row.querySelector('.decrement-boundary');
            
            if (newStatus === 'active') {
                incBtn.removeAttribute('disabled');
                decBtn.removeAttribute('disabled');
            } else {
                incBtn.setAttribute('disabled', 'disabled');
                decBtn.setAttribute('disabled', 'disabled');
            }
        }
    });
});


// Update boundary controls when status changes (via event delegation)
document.addEventListener('DOMContentLoaded', function() {
    // This will work even if the table is reloaded via AJAX
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('van-status-select')) {
            const row = e.target.closest('tr');
            const vanId = row.dataset.vanId;
            const newStatus = e.target.value;
            
            // Enable/disable boundary buttons based on status
            const incBtn = row.querySelector('.increment-boundary');
            const decBtn = row.querySelector('.decrement-boundary');
            
            if (newStatus === 'active') {
                incBtn.removeAttribute('disabled');
                decBtn.removeAttribute('disabled');
            } else {
                incBtn.setAttribute('disabled', 'disabled');
                decBtn.setAttribute('disabled', 'disabled');
            }
        }
    });
});





    // Show status options
    function showStatusOptions(element) {
      const container = element.closest('.status-badge-container');
      const options = container.querySelector('.status-options');

      // Hide all other open options first
      document.querySelectorAll('.status-options').forEach(opt => {
        if (opt !== options) {
          opt.style.display = 'none';
        }
      });

      // Toggle current options
      if (options.style.display === 'block') {
        options.style.display = 'none';
      } else {
        options.style.display = 'block';
      }
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Show modal
    function showModal(modalId) {
      document.getElementById(modalId).style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    // Hide modal
    function hideModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = '';
    }

    // Show error message
    function showErrorMessage(message) {
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-danger';
      alertDiv.textContent = message;

      const mainContent = document.querySelector('.main-content');
      mainContent.insertBefore(alertDiv, mainContent.firstChild);

      // Auto-hide after 5 seconds
      setTimeout(() => {
        alertDiv.remove();
      }, 5000);
    }

    // Debounce function
    function debounce(func, wait) {
      let timeout;
      return function() {
        const context = this,
          args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          func.apply(context, args);
        }, wait);
      };
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Initialize destination management
    function initDestinationManagement() {
      // Add destination button
      const searchForm = document.getElementById('destination-search-form');
      if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const searchTerm = document.getElementById('destination-search').value.trim();
          filterDestinations(searchTerm);
        });
      }

      // Live search as you type (with debounce)
      const searchInput = document.getElementById('destination-search');
      if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
          const searchTerm = this.value.trim();
          filterDestinations(searchTerm);
        }, 300));
      }

      // Reset filters
      const resetBtn = document.getElementById('reset-destination-filters');
      if (resetBtn) {
        resetBtn.addEventListener('click', function() {
          document.getElementById('destination-search').value = '';
          filterDestinations('');
        });
      }
    }

    // Function to filter destinations
    function filterDestinations(searchTerm) {
      const params = new URLSearchParams();
      if (searchTerm) params.append('search', searchTerm);

      // Update URL without reloading the page
      history.pushState(null, '', '?' + params.toString());

      fetch(`manager.php?${params.toString()}&destination_search=1`)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newTable = doc.querySelector('#destination-management tbody');
          if (newTable) {
            document.querySelector('#destination-management tbody').innerHTML = newTable.innerHTML;
          }
        })
        .catch(error => console.error('Error:', error));
    }

    // Show add destination modal
    function showAddDestinationModal() {
      const form = document.getElementById('destination-form');
      if (form) {
        form.reset();
        document.getElementById('modal-destination-id').value = '';
        form.setAttribute('data-action', 'add');
      }
      document.getElementById('destination-modal-title').textContent = 'Add New Destination';
      showModal('destination-modal');
    }

    // Show edit destination modal
    function showEditDestinationModal(button) {
      const form = document.getElementById('destination-form');
      if (form) {
        form.reset();
        document.getElementById('modal-destination-id').value = button.dataset.id;
        document.getElementById('modal-destination-name').value = button.dataset.name;
        document.getElementById('modal-base-fare').value = button.dataset.baseFare;
        form.setAttribute('data-action', 'edit');
      }
      document.getElementById('destination-modal-title').textContent = 'Edit Destination';
      showModal('destination-modal');
    }

    // Show delete destination modal
    function showDeleteDestinationModal(button) {
      document.getElementById('delete-destination-id').value = button.dataset.id;
      document.getElementById('delete-destination-name').textContent = button.dataset.name;
      showModal('delete-destination-modal');
    }

    // Handle destination form submission
    function handleDestinationFormSubmit(form) {
      const isEdit = form.getAttribute('data-action') === 'edit';
      const action = isEdit ? 'update' : 'add';

      // Get form values
      const name = document.getElementById('modal-destination-name').value.trim();
      const baseFare = parseFloat(document.getElementById('modal-base-fare').value);
      const id = document.getElementById('modal-destination-id').value;

      // Validation
      if (!name) {
        alert('Destination name is required');
        return false;
      }

      if (isNaN(baseFare)) {
        alert('Please enter a valid base fare');
        return false;
      }

      // Create hidden input for action
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = isEdit ? 'edit_destination' : 'add_destination';
      actionInput.value = '1';
      form.appendChild(actionInput);

      // Submit the form
      form.submit();

      return false;
    }




    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////


    document.getElementById('van-form').addEventListener('submit', function(e) {
      const vanId = document.getElementById('modal-van-id-input').value.trim();
      const licensePlate = document.getElementById('modal-license-plate').value.trim();
      const model = document.getElementById('modal-model').value.trim();

      if (!vanId || !licensePlate || !model) {
        e.preventDefault();
        alert('Please fill all required fields');
        return false;
      }
      return true;
    });


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    function confirmDeleteTicket(event, ticketId) {
      event.preventDefault();
      showCustomConfirm({
        title: 'Confirm Ticket Deletion',
        message: `Are you sure you want to delete ticket #VT${String(ticketId).padStart(4, '0')}?`,
        icon: 'fas fa-trash',
        onConfirm: () => {
          const form = event.target.closest('form');
          fetch(form.action, {
              method: 'POST',
              body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                // Remove the table row
                form.closest('tr').remove();
                showSuccessMessage(data.message);
              } else {
                showErrorMessage(data.message);
              }
            })
            .catch(error => {
              showErrorMessage('Error deleting ticket');
            });
        }
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Initialize the revenue chart
      renderRevenueChartv2();

      // Add event listeners for chart type toggle buttons
      document.querySelectorAll('[data-chart="revenueChartv2"]').forEach(btn => {
        btn.addEventListener('click', function() {
          const chartType = this.getAttribute('data-type');
          if (window.revenueChartv2) {
            window.revenueChartv2.config.type = chartType;
            window.revenueChartv2.update();
          }
        });
      });
    });

    function renderRevenueChartv2() {
      // Get current date
      const currentDate = new Date();
      const currentYear = currentDate.getFullYear();

      // Get monthly revenue data via AJAX
      fetch('get_monthly_revenue.php?year=' + currentYear)
        .then(response => response.json())
        .then(data => {
          const ctx = document.getElementById('revenueChartv2').getContext('2d');

          // Month names for labels
          const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
          ];

          // Prepare data for current year
          const currentYearData = Array(12).fill(0);
          data.currentYear.forEach(item => {
            currentYearData[item.month - 1] = item.revenue;
          });

          // Prepare data for previous year if available
          let previousYearData = null;
          if (data.previousYear) {
            previousYearData = Array(12).fill(0);
            data.previousYear.forEach(item => {
              previousYearData[item.month - 1] = item.revenue;
            });
          }

          // Create datasets
          const datasets = [{
            label: currentYear + ' Revenue',
            data: currentYearData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgb(36, 24, 92)',
            borderWidth: 2,
            tension: 0.1,
            fill: true
          }];

          // Add previous year data if available
          if (previousYearData) {
            datasets.push({
              label: (currentYear - 1) + ' Revenue',
              data: previousYearData,
              backgroundColor: 'rgba(160, 162, 167, 0.2)',
              borderColor: 'rgba(201, 203, 207, 1)',
              borderWidth: 2,
              tension: 0.1,
              fill: false,
              borderDash: [5, 5]
            });
          }

          // Create the chart
          window.revenueChartv2 = new Chart(ctx, {
            type: 'line',
            data: {
              labels: monthNames,
              datasets: datasets
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'top',
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      return context.dataset.label + ': ₱' + context.raw.toLocaleString();
                    }
                  }
                },
                title: {
                  display: true,
                  text: 'Monthly Revenue Comparison',
                  font: {
                    size: 16
                  }
                }
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    callback: function(value) {
                      return '₱' + value.toLocaleString();
                    }
                  }
                }
              }
            }
          });
        })
        .catch(error => console.error('Error loading monthly revenue data:', error));
    }
















    function renderCharts() {
      // Get the date range from the report
      const startDate = '<?php echo $current_report["start_date"] ?? date("Y-m-d", strtotime("-30 days")); ?>';
      const endDate = '<?php echo $current_report["end_date"] ?? date("Y-m-d"); ?>';

      // Function to fetch and render a chart
      function renderChart(chartId, chartType, chartOptions = {}) {
        fetch(`charts.php?chart=${chartType}&start_date=${startDate}&end_date=${endDate}`)
          .then(response => response.json())
          .then(data => {
            const ctx = document.getElementById(chartId).getContext('2d');

            // Default options
            const defaults = {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'top',
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      let label = context.dataset.label || '';
                      if (label) label += ': ';
                      if (chartType === 'salesChart' || chartType === 'terminalChart') {
                        label += '₱' + context.raw.toLocaleString();
                      } else {
                        label += context.raw;
                      }
                      return label;
                    }
                  }
                }
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    callback: function(value) {
                      if (chartType === 'salesChart' || chartType === 'terminalChart') {
                        return '₱' + value.toLocaleString();
                      }
                      return value;
                    }
                  }
                }
              }
            };

            // Merge with custom options
            const options = {
              ...defaults,
              ...chartOptions
            };

            // Create chart
            window[chartId] = new Chart(ctx, {
              type: chartType.includes('Chart') ? 'bar' : 'doughnut', // Default types
              data: {
                labels: data.labels || [],
                datasets: [{
                  label: chartType.replace('Chart', ''),
                  data: data.values || [],
                  backgroundColor: data.colors || [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                  ],
                  borderColor: data.colors || [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(153, 102, 255, 1)'
                  ],
                  borderWidth: 1
                }]
              },
              options: options
            });
          })
          .catch(error => console.error(`Error loading ${chartId} data:`, error));
      }

      // Render each chart with specific options
      renderChart('destinationsChart', 'destinationsChart', {
        plugins: {
          title: {
            display: true,
            text: 'Top Destinations by Ticket Count',
            font: {
              size: 16
            }
          }
        }
      });

      renderChart('terminalChart', 'terminalChart', {
        plugins: {
          title: {
            display: true,
            text: 'Terminal Performance by Revenue',
            font: {
              size: 16
            }
          }
        }
      });

      renderChart('dailyTrendChart', 'dailyTrendChart', {
        plugins: {
          title: {
            display: true,
            text: 'Daily Ticket Sales Trend',
            font: {
              size: 16
            }
          }
        }
      });

      renderChart('statusChart', 'statusChart', {
        type: 'doughnut',
        plugins: {
          title: {
            display: true,
            text: 'Ticket Status Distribution',
            font: {
              size: 16
            }
          }
        }
      });

      renderChart('salesChart', 'salesChart', {
        plugins: {
          title: {
            display: true,
            text: 'Daily Revenue Trend',
            font: {
              size: 16
            }
          }
        }
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      // ... existing code ...

      // Initialize charts if report data exists
      <?php if (isset($current_report)): ?>
        renderCharts();
      <?php endif; ?>
    });
  </script>
</body>

</html>