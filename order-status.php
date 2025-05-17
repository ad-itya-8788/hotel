<?php
require_once 'assets/config/dbconnect.php';
require "popup.php";

// Initialize variables
$order_id = null;
$order = null;
$error_message = null;
$success = false;

// Check if order ID is provided
if (isset($_GET['oid']) && !empty($_GET['oid'])) {
    $order_id = (int)$_GET['oid'];
    
    // Fetch order details
    $query = "SELECT * FROM orders WHERE oid = $order_id";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) > 0) {
        $order = pg_fetch_assoc($result);
        $success = true;
        
        // Fetch order items
        $items_query = "SELECT oi.*, m.pname as menu_name 
                      FROM order_items oi 
                      JOIN menu m ON oi.mid = m.mid 
                      WHERE oi.oid = $order_id";
        $items_result = pg_query($conn, $items_query);
        $order_items = pg_fetch_all($items_result) ?: [];
    } else {
        $error_message = "Order not found. Please check your order ID.";
    }
} else {
    $error_message = "Please provide a valid order ID.";
}

// Helper function to get status label
function getStatusLabel($status) {
    switch ($status) {
        case 'pending': return "Pending";
        case 'preparing': return "Preparing";
        case 'served': return "Served";
        case 'out_for_delivery': return "Out for Delivery";
        case 'delivered': return "Delivered";
        case 'complete': return "Completed";
        default: return ucfirst($status);
    }
}

// Helper function to get status icon
function getStatusIcon($status) {
    switch ($status) {
        case 'pending': return "bi-hourglass";
        case 'preparing': return "bi-fire";
        case 'served': return "bi-check-circle";
        case 'out_for_delivery': return "bi-truck";
        case 'delivered': return "bi-house-check";
        case 'complete': return "bi-trophy";
        default: return "bi-question-circle";
    }
}

// Helper function to get status color
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return "#f7b924"; // amber
        case 'preparing': return "#3f6ad8"; // blue
        case 'served': return "#3ac47d"; // green
        case 'out_for_delivery': return "#16aaff"; // light blue
        case 'delivered': return "#3ac47d"; // green
        case 'complete': return "#444054"; // dark
        default: return "#6c757d"; // gray
    }
}

// Helper function to get status progress percentage
function getStatusProgress($status) {
    switch ($status) {
        case 'pending': return 20;
        case 'preparing': return 40;
        case 'served': 
        case 'out_for_delivery': return 60;
        case 'delivered': return 80;
        case 'complete': return 100;
        default: return 0;
    }
}

// Define the order status flow for different order types
function getOrderFlow($orderType) {
    if ($orderType == 'tableorder') {
        return [
            'pending' => ['label' => 'Order Received', 'icon' => 'bi-receipt'],
            'preparing' => ['label' => 'Preparing', 'icon' => 'bi-fire'],
            'served' => ['label' => 'Served', 'icon' => 'bi-check-circle'],
            'complete' => ['label' => 'Completed', 'icon' => 'bi-trophy']
        ];
    } else { // parcelorder
        return [
            'pending' => ['label' => 'Order Received', 'icon' => 'bi-receipt'],
            'preparing' => ['label' => 'Preparing', 'icon' => 'bi-fire'],
            'out_for_delivery' => ['label' => 'Ready for Pickup', 'icon' => 'bi-bag-check'],
            'delivered' => ['label' => 'Picked Up', 'icon' => 'bi-bag-check-fill'],
            'complete' => ['label' => 'Completed', 'icon' => 'bi-trophy']
        ];
    }
}

// Calculate the estimated time based on order status
function getEstimatedTime($order) {
    $currentTime = time();
    $orderTime = strtotime($order['order_at']);
    $elapsedMinutes = floor(($currentTime - $orderTime) / 60);
    
    switch ($order['order_status']) {
        case 'pending':
            return max(0, 5 - $elapsedMinutes) . " minutes until preparation starts";
        case 'preparing':
            return max(0, 15 - $elapsedMinutes) . " minutes until your food is ready";
        case 'out_for_delivery':
            return "Your order is ready for pickup";
        case 'served':
            return "Your order has been served. Enjoy your meal!";
        case 'delivered':
            return "Your order has been picked up. Enjoy your meal!";
        case 'complete':
            return "Order completed. Thank you for dining with us!";
        default:
            return "Estimating time...";
    }
}

// Define constants for extras pricing
define('CHAPATI_PRICE', 15);
define('ROTI_PRICE', 20);

// Calculate order total
function calculateOrderTotal($items) {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'];
        
        // Add extras if present
        if (isset($item['chapati']) && $item['chapati'] > 0) {
            $total += $item['chapati'] * CHAPATI_PRICE;
        }
        if (isset($item['roti']) && $item['roti'] > 0) {
            $total += $item['roti'] * ROTI_PRICE;
        }
    }
    return $total;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking - Hotel Aditya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #e63946;
            --secondary-color: #457b9d;
            --light-color: #f1faee;
            --dark-color: #1d3557;
            --success-color: #2a9d8f;
            --warning-color: #e9c46a;
            --danger-color: #e76f51;
            --info-color: #4cc9f0;
            --card-border-radius: 12px;
            --card-shadow: 0 8px 30px rgba(0,0,0,0.1);
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background: linear-gradient(90deg, #1a0d0d, #050d0f, #000000);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-bottom: 2px solid #28a745;
            padding: 12px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }
        
        .navbar-brand i {
            margin-right: 8px;
            font-size: 1.8rem;
        }
        
        .tracking-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 15px;
            flex: 1;
        }
        
        .tracking-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .tracking-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .tracking-subtitle {
            font-size: 1.1rem;
            color: #666;
        }
        
        .tracking-card {
            background-color: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            transition: all var(--transition-speed);
        }
        
        .tracking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .tracking-card-header {
            background: linear-gradient(135deg, var(--primary-color), #d62b39);
            color: white;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .tracking-card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
            pointer-events: none;
        }
        
        .order-id {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .order-date {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .tracking-card-body {
            padding: 25px;
        }
        
        .customer-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #eee;
        }
        
        .customer-info-item {
            flex: 1;
            min-width: 200px;
        }
        
        .customer-info-label {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 5px;
        }
        
        .customer-info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .order-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 5px;
        }
        
        .order-type-badge.table {
            background-color: rgba(69, 123, 157, 0.15);
            color: var(--secondary-color);
        }
        
        .order-type-badge.parcel {
            background-color: rgba(231, 111, 81, 0.15);
            color: var(--danger-color);
        }
        
        .status-container {
            margin-bottom: 30px;
        }
        
        .current-status {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .status-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .status-details {
            flex: 1;
        }
        
        .status-label {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .status-message {
            font-size: 0.95rem;
            color: #666;
        }
        
        .status-progress {
            margin-bottom: 30px;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #f0f0f0;
            margin-bottom: 15px;
            overflow: visible;
        }
        
        .progress-bar {
            position: relative;
            border-radius: 4px;
            transition: width 1s ease;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translate(50%, -50%);
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: inherit;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .status-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .status-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            flex: 1;
        }
        
        .status-step::before {
            content: '';
            position: absolute;
            top: 15px;
            left: calc(-50% + 15px);
            width: 100%;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        
        .status-step:first-child::before {
            display: none;
        }
        
        .status-step.active::before {
            background-color: var(--success-color);
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: white;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .status-step.active .step-icon {
            background-color: var(--success-color);
            transform: scale(1.2);
        }
        
        .status-step.current .step-icon {
            animation: pulse 2s infinite;
        }
        
        .step-label {
            font-size: 0.8rem;
            color: #777;
            max-width: 80px;
            transition: all 0.3s ease;
        }
        
        .status-step.active .step-label {
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .order-items {
            margin-bottom: 30px;
        }
        
        .order-items-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .order-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px dashed #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-quantity {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--light-color);
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 3px;
        }
        
        .item-extras {
            font-size: 0.85rem;
            color: #777;
        }
        
        .item-price {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 2px solid #eee;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .estimated-time {
            background-color: rgba(42, 157, 143, 0.1);
            border-left: 4px solid var(--success-color);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        
        .estimated-time-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .estimated-time-value {
            color: #666;
        }
        
        .message-box {
            background-color: rgba(0,0,0,0.02);
            border-radius: 6px;
            padding: 15px;
            margin-top: 8px;
            border-left: 3px solid var(--secondary-color);
            font-size: 0.95rem;
        }
        
        .refresh-info {
            text-align: center;
            font-size: 0.9rem;
            color: #777;
            margin-top: 20px;
        }
        
        .refresh-countdown {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-top: auto;
        }
        
        .footer p {
            margin-bottom: 0;
        }
        
        /* Animations */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(42, 157, 143, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(42, 157, 143, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(42, 157, 143, 0);
            }
        }
        
        .animate-fade-in {
            animation: fadeIn 1s;
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s;
        }
        
        .animate-slide-right {
            animation: slideRight 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes slideRight {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .tracking-title {
                font-size: 1.5rem;
            }
            
            .tracking-subtitle {
                font-size: 1rem;
            }
            
            .order-id {
                font-size: 1.2rem;
            }
            
            .customer-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .customer-info-item {
                min-width: 100%;
            }
            
            .status-steps {
                overflow-x: auto;
                padding-bottom: 10px;
            }
            
            .status-step {
                min-width: 80px;
            }
        }
        
        /* Loading animation */
        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(230, 57, 70, 0.2);
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Error state */
        .error-container {
            text-align: center;
            padding: 50px 20px;
        }
        
        .error-icon {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .error-message {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Search form */
        .search-form {
            max-width: 500px;
            margin: 0 auto 30px;
        }
        
        .search-form .form-control {
            border-radius: 50px 0 0 50px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            box-shadow: none;
        }
        
        .search-form .btn {
            border-radius: 0 50px 50px 0;
            padding: 12px 25px;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .search-form .btn:hover {
            background-color: #d62b39;
            border-color: #d62b39;
        }
        
        /* Auto-refresh animation */
        .refresh-animation {
            display: inline-block;
            animation: rotate 1s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-utensils" style="color:red;"></i>
                Hotel Aditya
            </a>
        </div>
    </nav>

    <div class="tracking-container">
        <div class="tracking-header animate__animated animate__fadeIn">
            <h1 class="tracking-title">Order Tracking</h1>
            <p class="tracking-subtitle">Track your order status in real-time</p>
        </div>
        
        <?php if (!isset($_GET['oid']) || empty($_GET['oid'])): ?>
            <!-- Search Form -->
            <div class="search-form animate__animated animate__fadeInUp">
                <form action="" method="GET" class="d-flex">
                    <input type="number" name="oid" class="form-control" placeholder="Enter your order number" required>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Track
                    </button>
                </form>
            </div>
            
            <div class="text-center mt-5 animate__animated animate__fadeIn">
                <img src="assets/images/order-tracking.svg" alt="Order Tracking" style="max-width: 300px; opacity: 0.7;">
                <p class="mt-4 text-muted">Enter your order number to track your order status</p>
            </div>
        <?php elseif ($error_message): ?>
            <!-- Error State -->
            <div class="error-container animate__animated animate__fadeIn">
                <div class="error-icon">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <h2 class="error-title">Oops! Something went wrong</h2>
                <p class="error-message"><?= $error_message ?></p>
                <a href="order-tracking.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>
        <?php elseif ($success): ?>
            <!-- Order Tracking Details -->
            <div id="tracking-content">
                <div class="tracking-card animate__animated animate__fadeInUp">
                    <div class="tracking-card-header">
                        <div class="order-id">Order #<?= $order['oid'] ?></div>
                        <div class="order-date"><?= date('F j, Y, g:i a', strtotime($order['order_at'])) ?></div>
                    </div>
                    <div class="tracking-card-body">
                        <div class="customer-info animate__animated animate__fadeIn" style="animation-delay: 0.2s;">
                            <div class="customer-info-item">
                                <div class="customer-info-label">Customer Name</div>
                                <div class="customer-info-value"><?= htmlspecialchars($order['cust_name']) ?></div>
                            </div>
                            
                            <div class="customer-info-item">
                                <div class="customer-info-label">Phone Number</div>
                                <div class="customer-info-value"><?= htmlspecialchars($order['cust_no']) ?></div>
                            </div>
                            
                            <div class="customer-info-item">
                                <div class="customer-info-label">Order Type</div>
                                <div class="customer-info-value">
                                    <?php if ($order['order_type'] == 'tableorder'): ?>
                                        <span class="order-type-badge table">
                                            <i class="bi bi-cup-hot"></i> Table #<?= $order['table_no'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="order-type-badge parcel">
                                            <i class="bi bi-bag"></i> Parcel Order
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($order['order_type'] == 'parcelorder' && !empty($order['delivery_address'])): ?>
                            <div class="animate__animated animate__fadeIn" style="animation-delay: 0.3s;">
                                <div class="customer-info-label">Message to Cook</div>
                                <div class="message-box">
                                    <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="status-container animate__animated animate__fadeIn" style="animation-delay: 0.4s;">
                            <div class="current-status">
                                <div class="status-icon" style="background-color: <?= getStatusColor($order['order_status']) ?>;">
                                    <i class="bi <?= getStatusIcon($order['order_status']) ?>"></i>
                                </div>
                                <div class="status-details">
                                    <div class="status-label"><?= getStatusLabel($order['order_status']) ?></div>
                                    <div class="status-message">
                                        <?php
                                        switch ($order['order_status']) {
                                            case 'pending':
                                                echo "Your order has been received and is waiting to be processed.";
                                                break;
                                            case 'preparing':
                                                echo "Our chefs are preparing your delicious meal.";
                                                break;
                                            case 'served':
                                                echo "Your order has been served. Enjoy your meal!";
                                                break;
                                            case 'out_for_delivery':
                                                echo "Your order is ready for pickup.";
                                                break;
                                            case 'delivered':
                                                echo "Your order has been picked up. Enjoy your meal!";
                                                break;
                                            case 'complete':
                                                echo "Your order has been completed. Thank you for dining with us!";
                                                break;
                                            default:
                                                echo "Your order is being processed.";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="status-progress">
                                <div class="progress">
                                    <div class="progress-bar" 
                                         role="progressbar" 
                                         style="width: <?= getStatusProgress($order['order_status']) ?>%; background-color: <?= getStatusColor($order['order_status']) ?>;" 
                                         aria-valuenow="<?= getStatusProgress($order['order_status']) ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="status-steps">
                                <?php
                                $orderFlow = getOrderFlow($order['order_type']);
                                $currentStatus = $order['order_status'];
                                $passedStatuses = [];
                                
                                // Determine which statuses have been passed
                                $statusPassed = false;
                                foreach ($orderFlow as $status => $details) {
                                    if ($status == $currentStatus) {
                                        $statusPassed = true;
                                    }
                                    
                                    if ($statusPassed) {
                                        break;
                                    }
                                    
                                    $passedStatuses[] = $status;
                                }
                                
                                // Output status steps
                                foreach ($orderFlow as $status => $details):
                                    $isActive = in_array($status, $passedStatuses) || $status == $currentStatus;
                                    $isCurrent = $status == $currentStatus;
                                ?>
                                    <div class="status-step <?= $isActive ? 'active' : '' ?> <?= $isCurrent ? 'current' : '' ?>">
                                        <div class="step-icon">
                                            <i class="bi <?= $details['icon'] ?>"></i>
                                        </div>
                                        <div class="step-label"><?= $details['label'] ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="estimated-time animate__animated animate__fadeIn" style="animation-delay: 0.5s;">
                            <div class="estimated-time-label">
                                <i class="bi bi-clock"></i> Estimated Time
                            </div>
                            <div class="estimated-time-value">
                                <?= getEstimatedTime($order) ?>
                            </div>
                        </div>
                        
                        <div class="order-items animate__animated animate__fadeIn" style="animation-delay: 0.6s;">
                            <div class="order-items-title">
                                <i class="bi bi-list-check"></i> Order Items
                            </div>
                            
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-quantity"><?= $item['quantity'] ?></div>
                                    <div class="item-details">
                                        <div class="item-name"><?= htmlspecialchars($item['menu_name']) ?></div>
                                        <div class="item-extras">
                                            <?php
                                            $extras = [];
                                            if (!empty($item['chapati'])) {
                                                $extras[] = "Chapati x" . $item['chapati'] . " (₹" . ($item['chapati'] * CHAPATI_PRICE) . ")";
                                            }
                                            if (!empty($item['roti'])) {
                                                $extras[] = "Roti x" . $item['roti'] . " (₹" . ($item['roti'] * ROTI_PRICE) . ")";
                                            }
                                            echo !empty($extras) ? implode(", ", $extras) : "No extras";
                                            ?>
                                        </div>
                                    </div>
                                    <div class="item-price">₹<?= number_format($item['price'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="order-total">
                                <span>Total</span>
                                <span>₹<?= number_format($order['ordercost'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="refresh-info animate__animated animate__fadeIn" style="animation-delay: 0.7s;">
                    <p>
                        <i class="bi bi-arrow-repeat refresh-animation"></i>
                        This page will automatically refresh in <span id="countdown" class="refresh-countdown">30</span> seconds
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container text-center">
            <p>© <?= date('Y') ?> Hotel Aditya. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh functionality
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success): ?>
                let countdown = 30;
                const countdownElement = document.getElementById('countdown');
                
                function updateCountdown() {
                    countdown--;
                    if (countdownElement) {
                        countdownElement.textContent = countdown;
                    }
                    
                    if (countdown <= 0) {
                        // Reload the page
                        window.location.reload();
                    } else {
                        setTimeout(updateCountdown, 1000);
                    }
                }
                
                // Start the countdown
                setTimeout(updateCountdown, 1000);
                
                // Animate progress bar
                const progressBar = document.querySelector('.progress-bar');
                if (progressBar) {
                    setTimeout(() => {
                        progressBar.style.width = progressBar.getAttribute('aria-valuenow') + '%';
                    }, 500);
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>
