<?php
// Start session
session_start();

// Include database connection
require_once 'db.php';

// Get tracking number from URL
$tracking_number = isset($_GET['tracking']) ? $_GET['tracking'] : '';
$shipment = null;
$status_updates = [];
$error_message = '';

// If tracking number is provided, fetch shipment details
if (!empty($tracking_number)) {
    // Prepare and execute query to get shipment details
    $stmt = $conn->prepare("SELECT * FROM shipments WHERE tracking_number = ?");
    $stmt->bind_param("s", $tracking_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $shipment = $result->fetch_assoc();
        
        // Get shipment status updates
        $stmt = $conn->prepare("
            SELECT ss.* 
            FROM shipment_status ss
            JOIN shipments s ON ss.shipment_id = s.id
            WHERE s.tracking_number = ?
            ORDER BY ss.status_date DESC
        ");
        $stmt->bind_param("s", $tracking_number);
        $stmt->execute();
        $status_result = $stmt->get_result();
        
        while ($status = $status_result->fetch_assoc()) {
            $status_updates[] = $status;
        }
    } else {
        $error_message = 'No shipment found with the provided tracking number.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHL - Track Your Shipment</title>
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
        
        .logo span {
            color: #d40511;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: #333;
            font-weight: bold;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: #d40511;
            text-decoration: none;
        }
        
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        /* Tracking Form */
        .tracking-container {
            max-width: 800px;
            margin: 0 auto 50px;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .tracking-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tracking-form input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .tracking-form button {
            padding: 12px 20px;
            background-color: #d40511;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            white-space: nowrap;
        }
        
        .tracking-form button:hover {
            background-color: #b00;
        }
        
        /* Error Message */
        .error-message {
            background-color: #ffebee;
            color: #d40511;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Shipment Details */
        .shipment-details {
            margin-top: 30px;
        }
        
        .shipment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .shipment-header h2 {
            color: #333;
        }
        
        .shipment-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-align: center;
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
        
        .shipment-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .info-card h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .info-card p {
            margin-bottom: 10px;
        }
        
        .info-card strong {
            font-weight: bold;
            color: #555;
        }
        
        /* Tracking Timeline */
        .tracking-timeline {
            margin-top: 40px;
        }
        
        .timeline-header {
            margin-bottom: 20px;
        }
        
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 4px;
            background-color: #ddd;
            top: 0;
            bottom: 0;
            left: 20px;
            margin-left: -2px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            background-color: inherit;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            left: 20px;
            background-color: white;
            border: 4px solid #d40511;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
            transform: translateX(-50%);
        }
        
        .timeline-content {
            padding: 20px;
            background-color: white;
            position: relative;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .timeline-date {
            color: #777;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .timeline-status {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .timeline-location {
            color: #555;
            margin-bottom: 10px;
        }
        
        .timeline-description {
            color: #666;
        }
        
        /* No Shipment Found */
        .no-shipment {
            text-align: center;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .no-shipment h3 {
            color: #d40511;
            margin-bottom: 15px;
        }
        
        .no-shipment p {
            margin-bottom: 20px;
        }
        
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #d40511;
            color: white;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .back-button:hover {
            background-color: #b00;
            text-decoration: none;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            color: #ffcc00;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section ul li a {
            color: #ddd;
            transition: color 0.3s;
        }
        
        .footer-section ul li a:hover {
            color: #ffcc00;
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 30px auto 0;
            padding: 20px 20px 0;
            border-top: 1px solid #444;
            text-align: center;
            font-size: 14px;
            color: #ddd;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 15px;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0 10px;
            }
            
            .tracking-form {
                flex-direction: column;
            }
            
            .shipment-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">DHL</div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#">Services</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="admin_login.php">Admin</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Track Your Shipment</h1>
        
        <!-- Tracking Form -->
        <div class="tracking-container">
            <form class="tracking-form" action="track.php" method="GET">
                <input type="text" name="tracking" placeholder="Enter your tracking number" value="<?php echo htmlspecialchars($tracking_number); ?>" required>
                <button type="submit">Track</button>
            </form>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
                <div class="no-shipment">
                    <h3>Tracking Number Not Found</h3>
                    <p>We couldn't find any shipment with the tracking number you provided. Please check the number and try again.</p>
                    <a href="index.php" class="back-button">Back to Home</a>
                </div>
            <?php endif; ?>
            
            <?php if ($shipment): ?>
                <!-- Shipment Details -->
                <div class="shipment-details">
                    <div class="shipment-header">
                        <h2>Shipment #<?php echo htmlspecialchars($shipment['tracking_number']); ?></h2>
                        <?php
                        $latest_status = !empty($status_updates) ? $status_updates[0]['status'] : 'Unknown';
                        $status_class = '';
                        
                        switch ($latest_status) {
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
                        <div class="shipment-status <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($latest_status); ?>
                        </div>
                    </div>
                    
                    <div class="shipment-info">
                        <div class="info-card">
                            <h3>Shipment Information</h3>
                            <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($shipment['tracking_number']); ?></p>
                            <p><strong>Weight:</strong> <?php echo htmlspecialchars($shipment['package_weight']); ?> kg</p>
                            <p><strong>Estimated Delivery:</strong> <?php echo date('F j, Y', strtotime($shipment['estimated_delivery_date'])); ?></p>
                            <p><strong>Shipment Date:</strong> <?php echo date('F j, Y', strtotime($shipment['created_at'])); ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h3>Sender Information</h3>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($shipment['sender_name']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($shipment['sender_address']); ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h3>Recipient Information</h3>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($shipment['recipient_name']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($shipment['recipient_address']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Tracking Timeline -->
                    <div class="tracking-timeline">
                        <div class="timeline-header">
                            <h3>Tracking History</h3>
                        </div>
                        
                        <div class="timeline">
                            <?php if (empty($status_updates)): ?>
                                <p>No tracking updates available for this shipment.</p>
                            <?php else: ?>
                                <?php foreach ($status_updates as $update): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-content">
                                            <div class="timeline-date"><?php echo date('F j, Y - h:i A', strtotime($update['status_date'])); ?></div>
                                            <div class="timeline-status"><?php echo htmlspecialchars($update['status']); ?></div>
                                            <div class="timeline-location"><?php echo htmlspecialchars($update['location']); ?></div>
                                            <div class="timeline-description"><?php echo htmlspecialchars($update['description']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>About DHL</h3>
                <ul>
                    <li><a href="#">Company Information</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Press Center</a></li>
                    <li><a href="#">Sustainability</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Track a Shipment</a></li>
                    <li><a href="#">Get a Quote</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Legal</h3>
                <ul>
                    <li><a href="#">Terms of Use</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                    <li><a href="#">Fraud Awareness</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <ul>
                    <li><a href="#">Facebook</a></li>
                    <li><a href="#">Twitter</a></li>
                    <li><a href="#">LinkedIn</a></li>
                    <li><a href="#">Instagram</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> DHL International GmbH. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
