<?php
/**
 * DHL Clone - Utility Functions
 * 
 * This file contains common functions used throughout the application
 */

/**
 * Generate a random DHL tracking number
 * 
 * @return string Random tracking number in DHL format
 */
function generateTrackingNumber() {
    $prefix = 'DHL';
    $number = '';
    
    // Generate 8 random digits
    for ($i = 0; $i < 8; $i++) {
        $number .= mt_rand(0, 9);
    }
    
    return $prefix . $number;
}

/**
 * Format a date in a user-friendly way
 * 
 * @param string $date Date string in any format
 * @param bool $includeTime Whether to include time in the output
 * @return string Formatted date
 */
function formatDate($date, $includeTime = false) {
    $timestamp = strtotime($date);
    
    if ($includeTime) {
        return date('F j, Y - h:i A', $timestamp);
    } else {
        return date('F j, Y', $timestamp);
    }
}

/**
 * Get status class for CSS styling
 * 
 * @param string $status Shipment status
 * @return string CSS class name
 */
function getStatusClass($status) {
    switch ($status) {
        case 'Shipment created':
            return 'status-created';
        case 'In Transit':
            return 'status-in-transit';
        case 'Out for Delivery':
            return 'status-out-for-delivery';
        case 'Delivered':
            return 'status-delivered';
        default:
            return 'status-exception';
    }
}

/**
 * Sanitize user input
 * 
 * @param string $input User input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Log activity to file
 * 
 * @param string $action Action performed
 * @param string $details Additional details
 * @param int $userId User ID (if applicable)
 * @return bool Success or failure
 */
function logActivity($action, $details = '', $userId = null) {
    $logFile = 'logs/activity.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $userId = $userId ?? ($_SESSION['admin_id'] ?? 'Not logged in');
    
    $logEntry = "[{$timestamp}] IP: {$ip} | User: {$userId} | Action: {$action} | Details: {$details} | UA: {$userAgent}\n";
    
    return file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Calculate estimated delivery date
 * 
 * @param string $origin Origin location
 * @param string $destination Destination location
 * @param float $weight Package weight
 * @return string Estimated delivery date
 */
function calculateEstimatedDelivery($origin, $destination, $weight) {
    // This is a simplified calculation
    // In a real application, this would use distance calculations and shipping rules
    
    $today = new Date();
    $daysToAdd = 3; // Default delivery time
    
    // Add more days for international shipping
    if (strpos($origin, 'USA') !== false && strpos($destination, 'USA') === false) {
        $daysToAdd += 4;
    }
    
    // Add more days for heavy packages
    if ($weight > 10) {
        $daysToAdd += 1;
    }
    
    $deliveryDate = date('Y-m-d', strtotime("+{$daysToAdd} days"));
    return $deliveryDate;
}

/**
 * Get all available shipment statuses
 * 
 * @return array Array of status options
 */
function getShipmentStatuses() {
    return [
        'Shipment created' => 'Shipment information received',
        'In Transit' => 'Package is in transit to the destination',
        'Out for Delivery' => 'Package is out for delivery today',
        'Delivered' => 'Package has been delivered',
        'Exception' => 'There is an exception with the shipment',
        'On Hold' => 'Package is on hold',
        'Returned' => 'Package is being returned to sender'
    ];
}

/**
 * Check if user is logged in as admin
 * 
 * @return bool True if logged in, false otherwise
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Redirect to a different page
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}
?>
