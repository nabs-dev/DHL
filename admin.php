<?php
// Start session
session_start();

// Include database connection
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Initialize variables
$shipments = [];
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new shipment
    if (isset($_POST['action']) && $_POST['action'] === 'add_shipment') {
        $tracking_number = $_POST['tracking_number'] ?? '';
        $sender_name = $_POST['sender_name'] ?? '';
        $sender_address = $_POST['sender_address'] ?? '';
        $recipient_name = $_POST['recipient_name'] ?? '';
        $recipient_address = $_POST['recipient_address'] ?? '';
        $package_weight = $_POST['package_weight'] ?? '';
        $estimated_delivery_date = $_POST['estimated_delivery_date'] ?? '';
        
        // Validate input
        if (empty($tracking_number) || empty($sender_name) || empty($sender_address) || 
            empty($recipient_name) || empty($recipient_address) || empty($package_weight) || 
            empty($estimated_delivery_date)) {
            $error = 'All fields are required.';
        } else {
            // Check if tracking number already exists
            $stmt = $conn->prepare("SELECT id FROM shipments WHERE tracking_number = ?");
            $stmt->bind_param("s", $tracking_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Tracking number already exists.';
            } else {
                // Insert new shipment
                $stmt = $conn->prepare("
                    INSERT INTO shipments 
                    (tracking_number, sender_name, sender_address, recipient_name, recipient_address, package_weight, estimated_delivery_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssssds", $tracking_number, $sender_name, $sender_address, $recipient_name, $recipient_address, $package_weight, $estimated_delivery_date);
                
                if ($stmt->execute()) {
                    $shipment_id = $conn->insert_id;
                    
                    // Add initial status
                    $stmt = $conn->prepare("
                        INSERT INTO shipment_status 
                        (shipment_id, status, location, description) 
                        VALUES (?, 'Shipment created', 'Origin Facility', 'Shipment information received')
                    ");
                    $stmt->bind_param("i", $shipment_id);
                    $stmt->execute();
                    
                    $message = 'Shipment added successfully.';
                } else {
                    $error = 'Error adding shipment: ' . $conn->error;
                }
            }
        }
    }
    
    // Update shipment status
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $shipment_id = $_POST['shipment_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $location = $_POST['location'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Validate input
        if (empty($shipment_id) || empty($status) || empty($location) || empty($description)) {
            $error = 'All fields are required for status update.';
        } else {
            // Insert new status
            $stmt = $conn->prepare("
                INSERT INTO shipment_status 
                (shipment_id, status, location, description) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("isss", $shipment_id, $status, $location, $description);
            
            if ($stmt->execute()) {
                $message = 'Status updated successfully.';
            } else {
                $error = 'Error updating status: ' . $conn->error;
            }
        }
    }
    
    // Delete shipment
    if (isset($_POST['action']) && $_POST['action'] === 'delete_shipment') {
        $shipment_id = $_POST['shipment_id'] ?? '';
        
        if (empty($shipment_id)) {
            $error = 'Shipment ID is required for deletion.';
        } else {
            // Delete shipment
            $stmt = $conn->prepare("DELETE FROM shipments WHERE id = ?");
            $stmt->bind_param("i", $shipment_id);
            
            if ($stmt->execute()) {
                $message = 'Shipment deleted successfully.';
            } else {
                $error = 'Error deleting shipment: ' . $conn->error;
            }
        }
    }
}

// Get all shipments
$result = $conn->query("
    SELECT s.*, 
           (SELECT ss.status FROM shipment_status ss WHERE ss.shipment_id = s.id ORDER BY ss.status_date DESC LIMIT 1) as current_status
    FROM shipments s
    ORDER BY s.created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $shipments[] = $row;
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHL - Admin Dashboard</title>
    <style>
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        /* Global Styles */
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: #d40511;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        /* Header Styles */
        header {
            background-color: #ffcc00;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #d40511;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-info {
            margin-right: 20px;
            font-weight: bold;
        }
        
        .logout-button {
            padding: 8px 15px;
            background-color: #d40511;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .logout-button:hover {
            background-color: #b00;
            text-decoration: none;
        }
        
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-title {
            margin-bottom: 30px;
            color: #333;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
        }
        
        /* Sidebar */
        .sidebar {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 10px;
            border-radius: 4px;
            color: #333;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #f5f5f5;
            color: #d40511;
            text-decoration: none;
        }
        
        /* Content Area */
        .content-area {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-button {
            padding: 10px 20px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: bold;
            color: #777;
            transition: all 0.3s;
        }
        
        .tab-button:hover {
            color: #d40511;
        }
        
        .tab-button.active {
            color: #d40511;
            border-bottom-color: #d40511;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Forms */
        .form-container {
            margin-bottom: 30px;
        }
        
        .form-title {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-submit {
            padding: 10px 20px;
            background-color: #d40511;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .form-submit:hover {
            background-color: #b00;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        
        .data-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .data-table .actions {
            display: flex;
            gap: 10px;
        }
        
        .action-button {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .view-button {
            background-color: #2196f3;
            color: white;
        }
        
        .view-button:hover {
            background-color: #0b7dda;
        }
        
        .edit-button {
            background-color: #4caf50;
            color: white;
        }
        
        .edit-button:hover {
            background-color: #3e8e41;
        }
        
        .delete-button {
            background-color: #f44336;
            color: white;
        }
        
        .delete-button:hover {
            background-color: #d32f2f;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-created {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .status-in-transit {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .status-out-for-delivery {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-delivered {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-exception {
            background-color: #ffebee;
            color: #c62828;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .close-button {
            font-size: 24px;
            font-weight: bold;
            color: #777;
            cursor: pointer;
        }
        
        .close-button:hover {
            color: #333;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table th, .data-table td {
                padding: 8px;
            }
            
            .action-button {
                padding: 4px 8px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">DHL Admin</div>
            <div class="user-menu">
                <div class="user-info">
                    Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                </div>
                <a href="admin.php?logout=1" class="logout-button">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Admin Dashboard</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message success-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <!-- Sidebar -->
            <div class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="#" class="tab-link active" data-tab="shipments">Shipments</a></li>
                    <li><a href="#" class="tab-link" data-tab="add-shipment">Add Shipment</a></li>
                    <li><a href="#" class="tab-link" data-tab="update-status">Update Status</a></li>
                    <li><a href="index.php">Back to Website</a></li>
                </ul>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
                <!-- Shipments Tab -->
                <div id="shipments" class="tab-content active">
                    <h2 class="form-title">Manage Shipments</h2>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tracking #</th>
                                    <th>Sender</th>
                                    <th>Recipient</th>
                                    <th>Weight</th>
                                    <th>Est. Delivery</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($shipments)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">No shipments found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($shipments as $shipment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($shipment['tracking_number']); ?></td>
                                            <td><?php echo htmlspecialchars($shipment['sender_name']); ?></td>
                                            <td><?php echo htmlspecialchars($shipment['recipient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($shipment['package_weight']); ?> kg</td>
                                            <td><?php echo date('M j, Y', strtotime($shipment['estimated_delivery_date'])); ?></td>
                                            <td>
                                                <?php
                                                $status = $shipment['current_status'] ?? 'Unknown';
                                                $status_class = '';
                                                
                                                switch ($status) {
                                                    case 'Shipment created':
                                                        $status_class = 'status-created';
                                                        break;
                                                    case 'In Transit':
                                                        $status_class = 'status-in-transit';
                                                        break;
                                                    case 'Out for Delivery':
                                                        $status_class = 'status-out-for-delivery';
                                                        break;
                                                    case 'Delivered':
                                                        $status_class = 'status-delivered';
                                                        break;
                                                    default:
                                                        $status_class = 'status-exception';
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <a href="track.php?tracking=<?php echo urlencode($shipment['tracking_number']); ?>" class="action-button view-button" target="_blank">View</a>
                                                <button class="action-button edit-button update-status-btn" data-id="<?php echo $shipment['id']; ?>" data-tracking="<?php echo htmlspecialchars($shipment['tracking_number']); ?>">Update</button>
                                                <form method="POST" action="admin.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this shipment?');">
                                                    <input type="hidden" name="action" value="delete_shipment">
                                                    <input type="hidden" name="shipment_id" value="<?php echo $shipment['id']; ?>">
                                                    <button type="submit" class="action-button delete-button">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Add Shipment Tab -->
                <div id="add-shipment" class="tab-content">
                    <h2 class="form-title">Add New Shipment</h2>
                    
                    <form class="form-container" method="POST" action="admin.php">
                        <input type="hidden" name="action" value="add_shipment">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="tracking_number">Tracking Number</label>
                                <input type="text" id="tracking_number" name="tracking_number" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="package_weight">Package Weight (kg)</label>
                                <input type="number" id="package_weight" name="package_weight" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="estimated_delivery_date">Estimated Delivery Date</label>
                                <input type="date" id="estimated_delivery_date" name="estimated_delivery_date" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sender_name">Sender Name</label>
                            <input type="text" id="sender_name" name="sender_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="sender_address">Sender Address</label>
                            <textarea id="sender_address" name="sender_address" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="recipient_name">Recipient Name</label>
                            <input type="text" id="recipient_name" name="recipient_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="recipient_address">Recipient Address</label>
                            <textarea id="recipient_address" name="recipient_address" required></textarea>
                        </div>
                        
                        <button type="submit" class="form-submit">Add Shipment</button>
                    </form>
                </div>
                
                <!-- Update Status Tab -->
                <div id="update-status" class="tab-content">
                    <h2 class="form-title">Update Shipment Status</h2>
                    
                    <form class="form-container" method="POST" action="admin.php">
                        <input type="hidden" name="action" value="update_status">
                        
                        <div class="form-group">
                            <label for="shipment_id">Select Shipment</label>
                            <select id="shipment_id" name="shipment_id" required>
                                <option value="">-- Select Shipment --</option>
                                <?php foreach ($shipments as $shipment): ?>
                                    <option value="<?php echo $shipment['id']; ?>">
                                        <?php echo htmlspecialchars($shipment['tracking_number'] . ' - ' . $shipment['recipient_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="Shipment created">Shipment created</option>
                                <option value="In Transit">In Transit</option>
                                <option value="Out for Delivery">Out for Delivery</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Exception">Exception</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" required></textarea>
                        </div>
                        
                        <button type="submit" class="form-submit">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="status-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Update Status for <span id="modal-tracking"></span></h2>
                <span class="close-button">&times;</span>
            </div>
            
            <form class="form-container" method="POST" action="admin.php">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" id="modal-shipment-id" name="shipment_id">
                
                <div class="form-group">
                    <label for="modal-status">Status</label>
                    <select id="modal-status" name="status" required>
                        <option value="">-- Select Status --</option>
                        <option value="Shipment created">Shipment created</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Exception">Exception</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="modal-location">Location</label>
                    <input type="text" id="modal-location" name="location" required>
                </div>
                
                <div class="form-group">
                    <label for="modal-description">Description</label>
                    <textarea id="modal-description" name="description" required></textarea>
                </div>
                
                <button type="submit" class="form-submit">Update Status</button>
            </form>
        </div>
    </div>

    <script>
        // Tab Navigation
        document.querySelectorAll('.tab-link').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and content
                document.querySelectorAll('.tab-link').forEach(function(t) {
                    t.classList.remove('active');
                });
                document.querySelectorAll('.tab-content').forEach(function(c) {
                    c.classList.remove('active');
                });
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab')).classList.add('active');
            });
        });
        
        // Modal Functionality
        const modal = document.getElementById('status-modal');
        const closeButton = document.querySelector('.close-button');
        
        // Open modal when update button is clicked
        document.querySelectorAll('.update-status-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const shipmentId = this.getAttribute('data-id');
                const trackingNumber = this.getAttribute('data-tracking');
                
                document.getElementById('modal-shipment-id').value = shipmentId;
                document.getElementById('modal-tracking').textContent = trackingNumber;
                
                modal.style.display = 'block';
            });
        });
        
        // Close modal when close button is clicked
        closeButton.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Set today's date as the default for estimated delivery date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const nextWeek = new Date(today);
            nextWeek.setDate(today.getDate() + 7);
            
            const dateInput = document.getElementById('estimated_delivery_date');
            if (dateInput) {
                dateInput.valueAsDate = nextWeek;
            }
        });
    </script>
</body>
</html>
