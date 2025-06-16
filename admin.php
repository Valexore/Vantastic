<?php
session_start();

// Verify login
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

// Database connection
include 'config.php';

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Get user data and verify role
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user || $user['role'] != 'admin') {
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


// Get statistics for dashboard
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_vans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM vans"))['count'];
$total_terminals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM terminals"))['count'];
$total_destinations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM destinations"))['count'];
$total_tickets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tickets"))['count'];

// Get recent tickets
$recent_tickets = [];
$tickets_query = "SELECT t.*, u.full_name, ter.name as terminal_name, des.name as destination_name 
                 FROM tickets t 
                 JOIN users u ON t.user_id = u.id 
                 JOIN terminals ter ON t.terminal_id = ter.id 
                 JOIN destinations des ON t.destination_id = des.id 
                 ORDER BY t.created_at DESC LIMIT 5";
$tickets_result = mysqli_query($conn, $tickets_query);
while ($row = mysqli_fetch_assoc($tickets_result)) {
  $recent_tickets[] = $row;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'add_user':
        // Add user logic
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        // Modified query to include is_verified = 1
        $query = "INSERT INTO users (full_name, email, password, role, is_verified) 
              VALUES ('$full_name', '$email', '$password', '$role', 1)";
        mysqli_query($conn, $query);
        break;

      case 'update_user':
        // Update user logic
        $id = intval($_POST['id']);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        $query = "UPDATE users SET full_name='$full_name', email='$email', role='$role' WHERE id=$id";
        mysqli_query($conn, $query);
        break;

      case 'delete_user':
        // Delete user logic
        $id = intval($_POST['id']);
        $query = "DELETE FROM users WHERE id=$id";
        mysqli_query($conn, $query);
        break;

      case 'add_van':
        // Add van logic
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $license_plate = mysqli_real_escape_string($conn, $_POST['license_plate']);
        $model = mysqli_real_escape_string($conn, $_POST['model']);
        $terminal_id = intval($_POST['terminal_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $driver_name = mysqli_real_escape_string($conn, $_POST['driver_name']);
        $last_maintenance = mysqli_real_escape_string($conn, $_POST['last_maintenance']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        $query = "INSERT INTO vans (id, license_plate, model, terminal_id, status, driver_name, last_maintenance, notes) 
                          VALUES ('$id', '$license_plate', '$model', $terminal_id, '$status', '$driver_name', '$last_maintenance', '$notes')";
        mysqli_query($conn, $query);
        break;

      case 'update_van':
        // Update van logic
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $license_plate = mysqli_real_escape_string($conn, $_POST['license_plate']);
        $model = mysqli_real_escape_string($conn, $_POST['model']);
        $terminal_id = intval($_POST['terminal_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $driver_name = mysqli_real_escape_string($conn, $_POST['driver_name']);
        $last_maintenance = mysqli_real_escape_string($conn, $_POST['last_maintenance']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        $query = "UPDATE vans SET 
                          license_plate='$license_plate', 
                          model='$model', 
                          terminal_id=$terminal_id, 
                          status='$status', 
                          driver_name='$driver_name', 
                          last_maintenance='$last_maintenance', 
                          notes='$notes' 
                          WHERE id='$id'";
        mysqli_query($conn, $query);
        break;

      case 'delete_van':
        // Delete van logic
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $query = "DELETE FROM vans WHERE id='$id'";
        mysqli_query($conn, $query);
        break;

      case 'add_terminal':
        // Add terminal logic
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);

        $query = "INSERT INTO terminals (name, location) VALUES ('$name', '$location')";
        mysqli_query($conn, $query);
        break;

      case 'update_terminal':
        // Update terminal logic
        $id = intval($_POST['id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);

        $query = "UPDATE terminals SET name='$name', location='$location' WHERE id=$id";
        mysqli_query($conn, $query);
        break;

      case 'delete_terminal':
        // Delete terminal logic
        $id = intval($_POST['id']);
        $query = "DELETE FROM terminals WHERE id=$id";
        mysqli_query($conn, $query);
        break;

      case 'add_destination':
        // Add destination logic
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $base_fare = floatval($_POST['base_fare']);

        $query = "INSERT INTO destinations (name, base_fare) VALUES ('$name', $base_fare)";
        mysqli_query($conn, $query);
        break;

      case 'update_destination':
        // Update destination logic
        $id = intval($_POST['id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $base_fare = floatval($_POST['base_fare']);

        $query = "UPDATE destinations SET name='$name', base_fare=$base_fare WHERE id=$id";
        mysqli_query($conn, $query);
        break;

      case 'delete_destination':
        // Delete destination logic
        $id = intval($_POST['id']);
        $query = "DELETE FROM destinations WHERE id=$id";
        mysqli_query($conn, $query);
        break;
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  }
}

// Get all data for display
$users = [];
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);
while ($row = mysqli_fetch_assoc($users_result)) {
  $users[] = $row;
}

$vans = [];
$vans_query = "SELECT v.*, t.name as terminal_name FROM vans v JOIN terminals t ON v.terminal_id = t.id ORDER BY v.created_at DESC";
$vans_result = mysqli_query($conn, $vans_query);
while ($row = mysqli_fetch_assoc($vans_result)) {
  $vans[] = $row;
}

$terminals = [];
$terminals_query = "SELECT * FROM terminals ORDER BY name";
$terminals_result = mysqli_query($conn, $terminals_query);
while ($row = mysqli_fetch_assoc($terminals_result)) {
  $terminals[] = $row;
}

$destinations = [];
$destinations_query = "SELECT * FROM destinations ORDER BY name";
$destinations_result = mysqli_query($conn, $destinations_query);
while ($row = mysqli_fetch_assoc($destinations_result)) {
  $destinations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="img/knorr.png" type="image/png">
  <title>Admin Dashboard | VanTastic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="adminstyle.css">
  <link rel="stylesheet" href="adminstyle.css?v=<?php echo time(); ?>">
</head>
<style>
  /* Custom Confirm Dialog Styles */
  .custom-confirm {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
  }

  .custom-confirm-content {
    background-color: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    text-align: center;
    animation: fadeIn 0.3s ease;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .custom-confirm-icon {
    font-size: 48px;
    color: #e74c3c;
    margin-bottom: 15px;
  }

  .custom-confirm-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #2c3e50;
  }

  .custom-confirm-message {
    margin-bottom: 20px;
    color: #7f8c8d;
  }

  .custom-confirm-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
  }

  .custom-confirm-btn {
    padding: 8px 20px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
    min-width: 80px;
  }

  .custom-confirm-btn-confirm {
    background-color: #e74c3c;
    color: white;
  }

  .custom-confirm-btn-confirm:hover {
    background-color: #c0392b;
  }

  .custom-confirm-btn-cancel {
    background-color: #95a5a6;
    color: white;
  }

  .custom-confirm-btn-cancel:hover {
    background-color: #7f8c8d;
  }
</style>

<body>
  <!-- Custom Confirm Dialog -->
  <div id="custom-confirm" class="custom-confirm">
    <div class="custom-confirm-content">
      <div id="confirm-icon" class="custom-confirm-icon"></div>
      <h3 id="confirm-title" class="custom-confirm-title"></h3>
      <p id="confirm-message" class="custom-confirm-message"></p>
      <div class="custom-confirm-buttons">
        <button id="confirm-cancel" class="custom-confirm-btn custom-confirm-btn-cancel">Cancel</button>
        <button id="confirm-ok" class="custom-confirm-btn custom-confirm-btn-confirm">Logout</button>
      </div>
    </div>
  </div>







  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <img src="img/VanTasticWhite.png" alt="VanTastic Logo">
      </div>

      <ul class="sidebar-menu">
        <li><a href="#" class="active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="#" data-section="user-management"><i class="fas fa-users-cog"></i> User Management</a></li>
        <li><a href="#" data-section="van-management"><i class="fas fa-car"></i> Van Management</a></li>
        <li><a href="#" data-section="terminal-management"><i class="fas fa-route"></i> Terminal</a></li>
        <li><a href="#" data-section="destination-management"><i class="fas fa-location"></i> Destination</a></li>
      </ul>

      <button class="logout-btn" id="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </button>
    </aside>

    <main class="main-content">
      <!-- Dashboard Section -->
      <section id="dashboard" class="dashboard-section active">
        <div class="dashboard-header">
          <h2>Dashboard Overview</h2>
          <div class="header-actions">
            <div class="welcome-message">
              Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!
            </div>
          </div>
        </div>

        <div class="stats-container">
          <div class="stat-card">
            <h3>Total Users</h3>
            <div class="value"><?php echo $total_users; ?></div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
          </div>
          <div class="stat-card">
            <h3>Total Vans</h3>
            <div class="value"><?php echo $total_vans; ?></div>
            <div class="stat-icon"><i class="fas fa-van-shuttle"></i></div>
          </div>
          <div class="stat-card">
            <h3>Total Terminals</h3>
            <div class="value"><?php echo $total_terminals; ?></div>
            <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
          </div>
          <div class="stat-card">
            <h3>Total Destinations</h3>
            <div class="value"><?php echo $total_destinations; ?></div>
            <div class="stat-icon"><i class="fas fa-flag"></i></div>
          </div>
        </div>

        <div class="recent-activity">
          <h3>Recent Tickets</h3>
          <div class="activity-table">
            <table>
<thead>
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Verified</th>
    <th>Last Login</th>
    <th>Login Attempts</th>
    <th>Lockout Until</th>
    <th>Created At</th>
    <th>Actions</th>
  </tr>
</thead>
             <tbody>
  <?php foreach ($users as $user): ?>
  <tr data-user-id="<?php echo $user['id']; ?>">
    <td><?php echo $user['id']; ?></td>
    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
    <td><?php echo htmlspecialchars($user['email']); ?></td>
    <td><?php echo ucfirst($user['role']); ?></td>
    <td><?php echo $user['is_verified'] ? 'Yes' : 'No'; ?></td>
    <td><?php echo $user['last_login_session'] ? date('M d, Y H:i', strtotime($user['last_login_session'])) : 'Never'; ?></td>
    <td><?php echo $user['login_attempts']; ?></td>
    <td><?php echo $user['login_lockout'] ? date('M d, Y H:i', strtotime($user['login_lockout'])) : 'Not locked'; ?></td>
    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
    <td>
      <div class="action-buttons">
        <button class="btn btn-secondary btn-sm edit-user-btn">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-danger btn-sm delete-user-btn">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
</tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- User Management Section -->
      <section id="user-management" class="dashboard-section">
        <div class="dashboard-header">
          <h2>User Management</h2>
          <div class="header-actions">
            <div class="search-bar">
              <input type="text" id="user-search" placeholder="Search users...">
              <button id="search-users"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-primary" id="add-user-btn">
              <i class="fas fa-plus"></i> Add User
            </button>
          </div>
        </div>

        <div class="filter-controls">
          <div class="filter-group">
            <label for="user-role-filter">Role:</label>
            <select id="user-role-filter">
              <option value="all">All Roles</option>
              <option value="user">User</option>
              <option value="manager">Manager</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <button class="btn btn-secondary" id="reset-user-filters">
            <i class="fas fa-sync-alt"></i> Reset
          </button>
        </div>

        <div class="data-table">
          <table id="users-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Last Login</th>
                <th>Login Attempts</th>
                <th>Lockout Until</th>
                <th>Created At</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr data-user-id="<?php echo $user['id']; ?>">
                  <td><?php echo $user['id']; ?></td>
                  <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td><?php echo ucfirst($user['role']); ?></td>
                  <td><?php echo $user['last_login_session'] ? date('M d, Y H:i', strtotime($user['last_login_session'])) : 'Never'; ?></td>
                  <td><?php echo $user['login_attempts']; ?></td>
                  <td><?php echo $user['login_lockout'] ? date('M d, Y H:i', strtotime($user['login_lockout'])) : 'Not locked'; ?></td>
                  <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-secondary btn-sm edit-user-btn">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm delete-user-btn">
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

      <!-- Van Management Section -->
      <section id="van-management" class="dashboard-section">
        <div class="dashboard-header">
          <h2>Van Management</h2>
          <div class="header-actions">
            <div class="search-bar">
              <input type="text" id="van-search" placeholder="Search vans...">
              <button id="search-vans"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-primary" id="add-van-btn">
              <i class="fas fa-plus"></i> Add Van
            </button>
          </div>
        </div>

        <div class="filter-controls">
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
            <label for="van-terminal-filter">Terminal:</label>
            <select id="van-terminal-filter">
              <option value="all">All Terminals</option>
              <?php foreach ($terminals as $terminal): ?>
                <option value="<?php echo $terminal['id']; ?>"><?php echo htmlspecialchars($terminal['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-secondary" id="reset-van-filters">
            <i class="fas fa-sync-alt"></i> Reset
          </button>
        </div>

        <div class="data-table">
          <table id="vans-table">
            <thead>
              <tr>
                <th>Van ID</th>
                <th>License Plate</th>
                <th>Model</th>
                <th>Terminal</th>
                <th>Driver</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vans as $van): ?>
                <tr data-van-id="<?php echo $van['id']; ?>">
                  <td><?php echo $van['id']; ?></td>
                  <td><?php echo htmlspecialchars($van['license_plate']); ?></td>
                  <td><?php echo htmlspecialchars($van['model']); ?></td>
                  <td><?php echo htmlspecialchars($van['terminal_name']); ?></td>
                  <td><?php echo htmlspecialchars($van['driver_name'] ?? 'N/A'); ?></td>
                  <td><span class="status-badge status-<?php echo strtolower($van['status']); ?>"><?php echo ucfirst($van['status']); ?></span></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-secondary btn-sm edit-van-btn">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm delete-van-btn">
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

      <!-- Terminal Management Section -->
      <section id="terminal-management" class="dashboard-section">
        <div class="dashboard-header">
          <h2>Terminal Management</h2>
          <div class="header-actions">
            <div class="search-bar">
              <input type="text" id="terminal-search" placeholder="Search terminals...">
              <button id="search-terminals"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-primary" id="add-terminal-btn">
              <i class="fas fa-plus"></i> Add Terminal
            </button>
          </div>
        </div>

        <div class="data-table">
          <table id="terminals-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Location</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($terminals as $terminal): ?>
                <tr data-terminal-id="<?php echo $terminal['id']; ?>">
                  <td><?php echo $terminal['id']; ?></td>
                  <td><?php echo htmlspecialchars($terminal['name']); ?></td>
                  <td><?php echo htmlspecialchars($terminal['location']); ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-secondary btn-sm edit-terminal-btn">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm delete-terminal-btn">
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

      <!-- Destination Management Section -->
      <section id="destination-management" class="dashboard-section">
        <div class="dashboard-header">
          <h2>Destination Management</h2>
          <div class="header-actions">
            <div class="search-bar">
              <input type="text" id="destination-search" placeholder="Search destinations...">
              <button id="search-destinations"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-primary" id="add-destination-btn">
              <i class="fas fa-plus"></i> Add Destination
            </button>
          </div>
        </div>

        <div class="data-table">
          <table id="destinations-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Base Fare</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($destinations as $destination): ?>
                <tr data-destination-id="<?php echo $destination['id']; ?>">
                  <td><?php echo $destination['id']; ?></td>
                  <td><?php echo htmlspecialchars($destination['name']); ?></td>
                  <td>₱<?php echo number_format($destination['base_fare'], 2); ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-secondary btn-sm edit-destination-btn">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm delete-destination-btn">
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

      <!-- Modals -->
      <!-- Add User Modal -->
      <div class="modal" id="add-user-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Add New User</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form id="add-user-form" method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="form-group">
              <label for="add-user-full-name">Full Name</label>
              <input type="text" id="add-user-full-name" name="full_name" required>
            </div>
            <div class="form-group">
              <label for="add-user-email">Email</label>
              <input type="email" id="add-user-email" name="email" required>
            </div>
            <div class="form-group">
              <label for="add-user-password">Password</label>
              <input type="password" id="add-user-password" name="password" required>
            </div>
            <div class="form-group">
              <label for="add-user-role">Role</label>
              <select id="add-user-role" name="role" required>
                <option value="user">User</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-add-user">Cancel</button>
              <button type="submit" class="btn btn-primary">Add User</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit User Modal -->
      <div class="modal" id="edit-user-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Edit User</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form id="edit-user-form" method="POST">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" id="edit-user-id" name="id">
            <div class="form-group">
              <label for="edit-user-full-name">Full Name</label>
              <input type="text" id="edit-user-full-name" name="full_name" required>
            </div>
            <div class="form-group">
              <label for="edit-user-email">Email</label>
              <input type="email" id="edit-user-email" name="email" required>
            </div>
            <div class="form-group">
              <label for="edit-user-role">Role</label>
              <select id="edit-user-role" name="role" required>
                <option value="user">User</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-edit-user">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div class="modal" id="delete-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Confirm Deletion</h3>
            <button class="close-modal">&times;</button>
          </div>
          <div class="modal-body">
            <p id="delete-modal-message">Are you sure you want to delete this item? This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <form id="delete-form" method="POST">
              <input type="hidden" name="action" id="delete-action">
              <input type="hidden" name="id" id="delete-id">
              <button type="button" class="btn btn-secondary" id="cancel-delete">Cancel</button>
              <button type="submit" class="btn btn-danger" id="confirm-delete">Delete</button>
            </form>
          </div>
        </div>
      </div>

      <!-- Add Van Modal -->
      <div class="modal" id="add-van-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Add New Van</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form id="add-van-form" method="POST">
            <input type="hidden" name="action" value="add_van">
            <div class="form-group">
              <label for="add-van-id">Van ID</label>
              <input type="text" id="add-van-id" name="id" required>
            </div>
            <div class="form-group">
              <label for="add-van-license">License Plate</label>
              <input type="text" id="add-van-license" name="license_plate" required>
            </div>
            <div class="form-group">
              <label for="add-van-model">Model</label>
              <input type="text" id="add-van-model" name="model" required>
            </div>
            <div class="form-group">
              <label for="add-van-terminal">Terminal</label>
              <select id="add-van-terminal" name="terminal_id" required>
                <?php foreach ($terminals as $terminal): ?>
                  <option value="<?php echo $terminal['id']; ?>"><?php echo htmlspecialchars($terminal['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="add-van-status">Status</label>
              <select id="add-van-status" name="status" required>
                <option value="active">Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="form-group">
              <label for="add-van-driver">Driver Name</label>
              <input type="text" id="add-van-driver" name="driver_name">
            </div>
            <div class="form-group">
              <label for="add-van-maintenance">Last Maintenance</label>
              <input type="date" id="add-van-maintenance" name="last_maintenance">
            </div>
            <div class="form-group">
              <label for="add-van-notes">Notes</label>
              <textarea id="add-van-notes" name="notes" rows="3"></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-add-van">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Van</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Van Modal -->
      <div class="modal" id="edit-van-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Edit Van</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form id="edit-van-form" method="POST">
            <input type="hidden" name="action" value="update_van">
            <input type="hidden" id="edit-van-id" name="id">
            <div class="form-group">
              <label for="edit-van-license">License Plate</label>
              <input type="text" id="edit-van-license" name="license_plate" required>
            </div>
            <div class="form-group">
              <label for="edit-van-model">Model</label>
              <input type="text" id="edit-van-model" name="model" required>
            </div>
            <div class="form-group">
              <label for="edit-van-terminal">Terminal</label>
              <select id="edit-van-terminal" name="terminal_id" required>
                <?php foreach ($terminals as $terminal): ?>
                  <option value="<?php echo $terminal['id']; ?>"><?php echo htmlspecialchars($terminal['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="edit-van-status">Status</label>
              <select id="edit-van-status" name="status" required>
                <option value="active">Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="form-group">
              <label for="edit-van-driver">Driver Name</label>
              <input type="text" id="edit-van-driver" name="driver_name">
            </div>
            <div class="form-group">
              <label for="edit-van-maintenance">Last Maintenance</label>
              <input type="date" id="edit-van-maintenance" name="last_maintenance">
            </div>
            <div class="form-group">
              <label for="edit-van-notes">Notes</label>
              <textarea id="edit-van-notes" name="notes" rows="3"></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-edit-van">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add Terminal Modal -->
      <div class="modal" id="add-terminal-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Add New Terminal</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form id="add-terminal-form" method="POST">
            <input type="hidden" name="action" value="add_terminal">
            <div class="form-group">
              <label for="add-terminal-name">Name</label>
              <input type="text" id="add-terminal-name" name="name" required>
            </div>
            <div class="form-group">
              <label for="add-terminal-location">Location</label>
              <input type="text" id="add-terminal-location" name="location" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-add-terminal">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Terminal</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Terminal Modal -->
      <div class="modal" id="edit-terminal-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Edit Terminal</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form id="edit-terminal-form" method="POST">
            <input type="hidden" name="action" value="update_terminal">
            <input type="hidden" id="edit-terminal-id" name="id">
            <div class="form-group">
              <label for="edit-terminal-name">Name</label>
              <input type="text" id="edit-terminal-name" name="name" required>
            </div>
            <div class="form-group">
              <label for="edit-terminal-location">Location</label>
              <input type="text" id="edit-terminal-location" name="location" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-edit-terminal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add Destination Modal -->
      <div class="modal" id="add-destination-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Add New Destination</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form id="add-destination-form" method="POST">
            <input type="hidden" name="action" value="add_destination">
            <div class="form-group">
              <label for="add-destination-name">Name</label>
              <input type="text" id="add-destination-name" name="name" required>
            </div>
            <div class="form-group">
              <label for="add-destination-fare">Base Fare</label>
              <input type="number" id="add-destination-fare" name="base_fare" step="0.01" min="0" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-add-destination">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Destination</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Destination Modal -->
      <div class="modal" id="edit-destination-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Edit Destination</h3>
            <button class="close-modal">&times;</button>
          </div>
          <form id="edit-destination-form" method="POST">
            <input type="hidden" name="action" value="update_destination">
            <input type="hidden" id="edit-destination-id" name="id">
            <div class="form-group">
              <label for="edit-destination-name">Name</label>
              <input type="text" id="edit-destination-name" name="name" required>
            </div>
            <div class="form-group">
              <label for="edit-destination-fare">Base Fare</label>
              <input type="number" id="edit-destination-fare" name="base_fare" step="0.01" min="0" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-edit-destination">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
  <script src="disableclick.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      initLogoutConfirmation();
      // Sidebar navigation
      const menuLinks = document.querySelectorAll('.sidebar-menu a');
      const sections = document.querySelectorAll('.dashboard-section');

      menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();

          // Remove active class from all links and sections
          menuLinks.forEach(l => l.classList.remove('active'));
          sections.forEach(s => s.classList.remove('active'));

          // Add active class to clicked link
          this.classList.add('active');

          // Show corresponding section
          const sectionId = this.getAttribute('data-section');
          document.getElementById(sectionId).classList.add('active');
        });
      });



      // Modal functionality
      const modals = document.querySelectorAll('.modal');
      const closeModalButtons = document.querySelectorAll('.close-modal, .cancel-btn');

      function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
      }

      function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
      }

      // Add User Modal
      document.getElementById('add-user-btn')?.addEventListener('click', () => openModal('add-user-modal'));
      document.getElementById('cancel-add-user')?.addEventListener('click', () => closeModal('add-user-modal'));

      // Edit User Modal
      const editUserButtons = document.querySelectorAll('.edit-user-btn');
      editUserButtons.forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('tr');
          const userId = row.getAttribute('data-user-id');
          const fullName = row.cells[1].textContent;
          const email = row.cells[2].textContent;
          const role = row.cells[3].textContent.toLowerCase();

          document.getElementById('edit-user-id').value = userId;
          document.getElementById('edit-user-full-name').value = fullName;
          document.getElementById('edit-user-email').value = email;
          document.getElementById('edit-user-role').value = role;

          openModal('edit-user-modal');
        });
      });

      document.getElementById('cancel-edit-user')?.addEventListener('click', () => closeModal('edit-user-modal'));

      // Delete Confirmation Modal
      const deleteButtons = document.querySelectorAll('.delete-user-btn, .delete-van-btn, .delete-terminal-btn, .delete-destination-btn');
      deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
          let itemType, itemId;

          if (this.classList.contains('delete-user-btn')) {
            itemType = 'user';
            itemId = this.closest('tr').getAttribute('data-user-id');
            document.getElementById('delete-action').value = 'delete_user';
          } else if (this.classList.contains('delete-van-btn')) {
            itemType = 'van';
            itemId = this.closest('tr').getAttribute('data-van-id');
            document.getElementById('delete-action').value = 'delete_van';
          } else if (this.classList.contains('delete-terminal-btn')) {
            itemType = 'terminal';
            itemId = this.closest('tr').getAttribute('data-terminal-id');
            document.getElementById('delete-action').value = 'delete_terminal';
          } else if (this.classList.contains('delete-destination-btn')) {
            itemType = 'destination';
            itemId = this.closest('tr').getAttribute('data-destination-id');
            document.getElementById('delete-action').value = 'delete_destination';
          }

          document.getElementById('delete-id').value = itemId;
          document.getElementById('delete-modal-message').textContent =
            `Are you sure you want to delete this ${itemType} (ID: ${itemId})? This action cannot be undone.`;

          openModal('delete-modal');
        });
      });

      document.getElementById('cancel-delete')?.addEventListener('click', () => closeModal('delete-modal'));

      // Add Van Modal
      document.getElementById('add-van-btn')?.addEventListener('click', () => openModal('add-van-modal'));
      document.getElementById('cancel-add-van')?.addEventListener('click', () => closeModal('add-van-modal'));

      // Edit Van Modal
      const editVanButtons = document.querySelectorAll('.edit-van-btn');
      editVanButtons.forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('tr');
          const vanId = row.getAttribute('data-van-id');
          const licensePlate = row.cells[1].textContent;
          const model = row.cells[2].textContent;
          const terminalId = row.cells[3].getAttribute('data-terminal-id');
          const driverName = row.cells[4].textContent;
          const status = row.cells[5].querySelector('.status-badge').textContent.toLowerCase();

          document.getElementById('edit-van-id').value = vanId;
          document.getElementById('edit-van-license').value = licensePlate;
          document.getElementById('edit-van-model').value = model;
          document.getElementById('edit-van-terminal').value = terminalId;
          document.getElementById('edit-van-status').value = status;
          document.getElementById('edit-van-driver').value = driverName === 'N/A' ? '' : driverName;

          openModal('edit-van-modal');
        });
      });

      document.getElementById('cancel-edit-van')?.addEventListener('click', () => closeModal('edit-van-modal'));

      // Add Terminal Modal
      document.getElementById('add-terminal-btn')?.addEventListener('click', () => openModal('add-terminal-modal'));
      document.getElementById('cancel-add-terminal')?.addEventListener('click', () => closeModal('add-terminal-modal'));

      // Edit Terminal Modal
      const editTerminalButtons = document.querySelectorAll('.edit-terminal-btn');
      editTerminalButtons.forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('tr');
          const terminalId = row.getAttribute('data-terminal-id');
          const name = row.cells[1].textContent;
          const location = row.cells[2].textContent;

          document.getElementById('edit-terminal-id').value = terminalId;
          document.getElementById('edit-terminal-name').value = name;
          document.getElementById('edit-terminal-location').value = location;

          openModal('edit-terminal-modal');
        });
      });

      document.getElementById('cancel-edit-terminal')?.addEventListener('click', () => closeModal('edit-terminal-modal'));

      // Add Destination Modal
      document.getElementById('add-destination-btn')?.addEventListener('click', () => openModal('add-destination-modal'));
      document.getElementById('cancel-add-destination')?.addEventListener('click', () => closeModal('add-destination-modal'));

      // Edit Destination Modal
      const editDestinationButtons = document.querySelectorAll('.edit-destination-btn');
      editDestinationButtons.forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('tr');
          const destinationId = row.getAttribute('data-destination-id');
          const name = row.cells[1].textContent;
          const baseFare = parseFloat(row.cells[2].textContent.replace('₱', ''));

          document.getElementById('edit-destination-id').value = destinationId;
          document.getElementById('edit-destination-name').value = name;
          document.getElementById('edit-destination-fare').value = baseFare;

          openModal('edit-destination-modal');
        });
      });

      document.getElementById('cancel-edit-destination')?.addEventListener('click', () => closeModal('edit-destination-modal'));

      // Close modal when clicking outside
      modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
          if (e.target === this) {
            closeModal(this.id);
          }
        });
      });

      // Reset filters buttons
      document.getElementById('reset-user-filters')?.addEventListener('click', function() {
        document.getElementById('user-role-filter').value = 'all';
      });

      document.getElementById('reset-van-filters')?.addEventListener('click', function() {
        document.getElementById('van-status-filter').value = 'all';
        document.getElementById('van-terminal-filter').value = 'all';
      });

      // Search functionality
      function setupTableSearch(inputId, tableId) {
        const searchInput = document.getElementById(inputId);
        if (searchInput) {
          searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
              const rowText = row.textContent.toLowerCase();
              row.style.display = rowText.includes(searchTerm) ? '' : 'none';
            });
          });
        }
      }

      setupTableSearch('user-search', 'users-table');
      setupTableSearch('van-search', 'vans-table');
      setupTableSearch('terminal-search', 'terminals-table');
      setupTableSearch('destination-search', 'destinations-table');

      // Filter functionality
      function setupTableFilter(filterId, tableId, columnIndex) {
        const filter = document.getElementById(filterId);
        if (filter) {
          filter.addEventListener('change', function() {
            const filterValue = this.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
              if (filterValue === 'all') {
                row.style.display = '';
              } else {
                const cellValue = row.cells[columnIndex].textContent.toLowerCase();
                row.style.display = cellValue.includes(filterValue) ? '' : 'none';
              }
            });
          });
        }
      }

      setupTableFilter('user-role-filter', 'users-table', 3);
      setupTableFilter('van-status-filter', 'vans-table', 5);
      setupTableFilter('van-terminal-filter', 'vans-table', 3);
    });



    // Custom confirm dialog function
    function showCustomConfirm(options) {
      const confirmDialog = document.getElementById('custom-confirm');
      const confirmIcon = document.getElementById('confirm-icon');
      const confirmTitle = document.getElementById('confirm-title');
      const confirmMessage = document.getElementById('confirm-message');
      const confirmOk = document.getElementById('confirm-ok');
      const confirmCancel = document.getElementById('confirm-cancel');

      // Set dialog content
      confirmIcon.className = options.icon || 'fas fa-question-circle custom-confirm-icon';
      confirmTitle.textContent = options.title || 'Confirm';
      confirmMessage.textContent = options.message || 'Are you sure?';

      // Show dialog
      confirmDialog.style.display = 'flex';

      // Set up event listeners
      confirmOk.onclick = function() {
        confirmDialog.style.display = 'none';
        if (typeof options.onConfirm === 'function') {
          options.onConfirm();
        }
      };

      confirmCancel.onclick = function() {
        confirmDialog.style.display = 'none';
        if (typeof options.onCancel === 'function') {
          options.onCancel();
        }
      };

      // Close when clicking outside
      confirmDialog.onclick = function(e) {
        if (e.target === confirmDialog) {
          confirmDialog.style.display = 'none';
        }
      };
    }

    // Initialize logout confirmation
    function initLogoutConfirmation() {
      document.getElementById('logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        showCustomConfirm({
          title: 'Confirm Logout',
          message: 'Are you sure you want to logout?',
          icon: 'fas fa-sign-out-alt custom-confirm-icon',
          onConfirm: () => {
            window.location.href = 'logout.php';
          }
        });
      });
    }

    // Call this function when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      initLogoutConfirmation();

      // Your other existing DOMContentLoaded code...
    });
  </script>
</body>

</html>