<?php
require_once 'assets/config/dbconnect.php';
require_once 'active.php';

// Define constants
define('CHAPATI_PRICE', 15);
define('ROTI_PRICE', 20);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_order':
                // Delete order
                $oid = (int)$_POST['oid'];
                $query = "DELETE FROM orders WHERE oid = $oid";
                pg_query($conn, $query);
                
                // Set success message
                $_SESSION['message'] = "Order #$oid has been deleted successfully!";
                $_SESSION['message_type'] = "success";
                break;
                
            case 'complete_order':
                // Mark order as complete
                $oid = (int)$_POST['oid'];
                $query = "UPDATE orders SET order_status = 'complete' WHERE oid = $oid";
                pg_query($conn, $query);
                
                // Set success message
                $_SESSION['message'] = "Order #$oid has been marked as complete!";
                $_SESSION['message_type'] = "success";
                break;
                
            case 'update_status':
                // Update order status
                $oid = (int)$_POST['oid'];
                $status = pg_escape_string($conn, $_POST['status']);
                
                $query = "UPDATE orders SET order_status = '$status' WHERE oid = $oid";
                pg_query($conn, $query);
                
                // Set success message
                $_SESSION['message'] = "Order #$oid status has been updated to " . getStatusLabel($status) . "!";
                $_SESSION['message_type'] = "success";
                break;
        }
        
        // Redirect to prevent form resubmission
        header("Location: ".$_SERVER['PHP_SELF'] . (isset($_GET['page']) ? "?page=".$_GET['page'] : ""));
        exit();
    }
}

// Helper function to clean phone number for WhatsApp API
function cleanPhoneNumber($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

// Helper function to get status message for WhatsApp
function getStatusMessage($status, $oid, $cost) {
    switch ($status) {
        case 'pending':
            return "Your order #$oid at Hotel Aditya has been received and is pending. Total amount: ₹$cost";
        case 'preparing':
            return "Good news! Your order #$oid at Hotel Aditya is now being prepared. It will be ready soon. Total amount: ₹$cost";
        case 'served':
            return "Your order #$oid at Hotel Aditya has been served. Enjoy your meal! Total amount: ₹$cost";
        case 'out_for_delivery':
            return "Your order #$oid at Hotel Aditya is out for delivery. It will arrive shortly. Total amount: ₹$cost";
        case 'delivered':
            return "Your order #$oid at Hotel Aditya has been delivered. Enjoy your meal! Total amount: ₹$cost";
        case 'complete':
            return "Your order #$oid at Hotel Aditya has been completed. Thank you for your business! Total amount: ₹$cost";
        default:
            return "Your order #$oid at Hotel Aditya has been updated to: $status. Total amount: ₹$cost";
    }
}

// Helper function to get order items for display
function getOrderItems($oid) {
    global $conn;
    
    $query = "SELECT oi.*, m.pname as menu_name 
              FROM order_items oi 
              JOIN menu m ON oi.mid = m.mid 
              WHERE oi.oid = $oid";
    $result = pg_query($conn, $query);
    return pg_fetch_all($result) ?: [];
}

// Update the generateBillMessage function to include order items with prices
function generateBillMessage($order) {
    global $conn;
    
    // Get order items
    $query = "SELECT oi.*, m.pname as menu_name 
              FROM order_items oi 
              JOIN menu m ON oi.mid = m.mid 
              WHERE oi.oid = " . $order['oid'];
    $result = pg_query($conn, $query);
    $items = pg_fetch_all($result) ?: [];
    
    $message = "🧾 *HOTEL ADITYA - BILL RECEIPT* 🧾\n\n";
    $message .= "📋 *Order #" . $order['oid'] . "*\n";
    $message .= "👤 Customer: " . $order['cust_name'] . "\n";
    $message .= "📱 Phone: " . $order['cust_no'] . "\n";
    $message .= "📅 Date: " . date('d-m-Y h:i A', strtotime($order['order_at'])) . "\n";
    
    if ($order['order_type'] == 'tableorder') {
        $message .= "🍽️ Type: Table #" . $order['table_no'] . "\n";
    } else {
        $message .= "🏠 Type: Home Delivery\n";
        if (!empty($order['delivery_address'])) {
            $message .= "📍 Address: " . $order['delivery_address'] . "\n";
        }
    }
    
    $message .= "📊 Status: " . getStatusLabel($order['order_status']) . "\n\n";
    
    // Add order items
    $message .= "📝 *ORDER DETAILS:*\n";
    $message .= "---------------------------\n";
    
    $total = 0;
    foreach ($items as $index => $item) {
        $itemPrice = $item['price']; // Base price
        $total += $itemPrice;

        $message .= ($index + 1) . ". " . $item['menu_name'] . " x " . $item['quantity'] . " - ₹" . number_format($itemPrice, 2) . "\n";
        
        // Add chapati with price
        if (isset($item['chapati']) && $item['chapati'] > 0) {
            $chapatiTotal = $item['chapati'] * CHAPATI_PRICE;
            $message .= "   - Chapati x" . $item['chapati'] . " - ₹" . number_format($chapatiTotal, 2) . "\n";
            $total += $chapatiTotal;
        }

        // Add roti with price
        if (isset($item['roti']) && $item['roti'] > 0) {
            $rotiTotal = $item['roti'] * ROTI_PRICE;
            $message .= "   - Roti x" . $item['roti'] . " - ₹" . number_format($rotiTotal, 2) . "\n";
            $total += $rotiTotal;
        }
    }
    
    $message .= "---------------------------\n";
    $message .= "💰 *TOTAL AMOUNT: ₹" . number_format($total, 2) . "*\n\n";
    $message .= "Thank you for dining with Hotel Aditya! 🙏\n";
    $message .= "Visit us again soon!";
    
    return $message;
}

// Helper function to get formatted status label
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

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return "bg-warning";
        case 'preparing': return "bg-primary";
        case 'served': return "bg-success";
        case 'out_for_delivery': return "bg-info";
        case 'delivered': return "bg-success";
        case 'complete': return "bg-dark";
        default: return "bg-secondary";
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

// Helper function to get table information
function getTableInfo($tableNo) {
    global $conn;
    
    $today = date('Y-m-d');
    $query = "SELECT * FROM orders 
              WHERE table_no = $tableNo 
              AND order_at::date = '$today' 
              AND order_status NOT IN ('complete') 
              ORDER BY order_at DESC 
              LIMIT 1";
    
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) > 0) {
        return pg_fetch_assoc($result);
    }
    
    return null;
}

// Handle GET actions (like generating bill)
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'generate_bill':
            $oid = (int)$_GET['id'];
            // Get order details
            $query = "SELECT * FROM orders WHERE oid = $oid";
            $result = pg_query($conn, $query);
            $order = pg_fetch_assoc($result);
            
            if ($order) {
                // We'll show a nicely formatted bill
                $bill_html = true;
            } else {
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            }
            break;
            
        case 'whatsapp_status':
            $oid = (int)$_GET['id'];
            $status = pg_escape_string($conn, $_GET['status']);
            
            // Get order details
            $query = "SELECT * FROM orders WHERE oid = $oid";
            $result = pg_query($conn, $query);
            $order = pg_fetch_assoc($result);
            
            if ($order) {
                // Update status
                $query = "UPDATE orders SET order_status = '$status' WHERE oid = $oid";
                pg_query($conn, $query);
                
                // Redirect to WhatsApp
                $phone = cleanPhoneNumber($order['cust_no']);
                $status_message = urlencode(getStatusMessage($status, $oid, $order['ordercost']));
                header("Location: https://wa.me/$phone?text=$status_message");
                exit();
            }
            break;
            
        case 'whatsapp_bill':
            $oid = (int)$_GET['id'];
            
            // Get order details
            $query = "SELECT * FROM orders WHERE oid = $oid";
            $result = pg_query($conn, $query);
            $order = pg_fetch_assoc($result);
            
            if ($order) {
                // Redirect to WhatsApp
                $phone = cleanPhoneNumber($order['cust_no']);
                $bill_message = urlencode(generateBillMessage($order));
                header("Location: https://wa.me/$phone?text=$bill_message");
                exit();
            }
            break;
    }
}

// Get today's statistics
$today = date('Y-m-d');
$today_start = $today . ' 00:00:00';
$today_end = $today . ' 23:59:59';

// Today's orders count
$query = "SELECT COUNT(*) FROM orders WHERE order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$today_orders_count = pg_fetch_result($result, 0, 0);

// Today's earnings
$query = "SELECT SUM(ordercost) FROM orders WHERE order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$today_earnings = pg_fetch_result($result, 0, 0) ?: 0;

// Pending orders count (only today's pending)
$query = "SELECT COUNT(*) FROM orders WHERE order_status = 'pending' AND order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$pending_orders = pg_fetch_result($result, 0, 0);

// Served orders count (only today's served)
$query = "SELECT COUNT(*) FROM orders WHERE order_status = 'served' AND order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$serv_order = pg_fetch_result($result, 0, 0);

// Preparing orders count (only today's preparing)
$query = "SELECT COUNT(*) FROM orders WHERE order_status = 'preparing' AND order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$preparing_orders = pg_fetch_result($result, 0, 0);

// Complete orders count (only today's complete)
$query = "SELECT COUNT(*) FROM orders WHERE order_status = 'complete' AND order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$complete_orders = pg_fetch_result($result, 0, 0);

// Delivered orders count (only today's delivered)
$query = "SELECT COUNT(*) FROM orders WHERE order_status = 'delivered' AND order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$delivered_orders = pg_fetch_result($result, 0, 0);

// Table orders count (only today's)
$query = "SELECT COUNT(*) FROM orders WHERE order_type = 'tableorder' AND order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$table_order = pg_fetch_result($result, 0, 0);

// Home orders count (only today's)
$query = "SELECT COUNT(*) FROM orders WHERE order_type = 'homeorder' AND order_at BETWEEN '$today_start' AND '$today_end'";
$result = pg_query($conn, $query);
$homeorder = pg_fetch_result($result, 0, 0);

// Fetch today's orders
$query = "SELECT * FROM orders WHERE order_at BETWEEN '$today_start' AND '$today_end' ORDER BY order_at DESC";
$result = pg_query($conn, $query);
$today_orders = pg_fetch_all($result) ?: [];

// Pagination and filtering for previous orders
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Base query for previous orders
$query = "SELECT * FROM orders WHERE order_at < '$today_start'";
$countQuery = "SELECT COUNT(oid) FROM orders WHERE order_at < '$today_start'";

// Apply filters
$filters = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters[] = "order_status = '".pg_escape_string($conn, $_GET['status'])."'";
}
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $filters[] = "order_type = '".pg_escape_string($conn, $_GET['type'])."'";
}
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters[] = "order_at >= '".pg_escape_string($conn, $_GET['date_from'])."'";
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters[] = "order_at <= '".pg_escape_string($conn, $_GET['date_to'])." 23:59:59'";
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = pg_escape_string($conn, $_GET['search']);
    $filters[] = "(cust_name ILIKE '%$search%' OR cust_no ILIKE '%$search%' OR CAST(oid AS TEXT) ILIKE '%$search%' OR delivery_address ILIKE '%$search%')";
}

if (!empty($filters)) {
    $query .= " AND " . implode(" AND ", $filters);
    $countQuery .= " AND " . implode(" AND ", $filters);
}

// Add sorting and pagination
$query .= " ORDER BY order_at DESC LIMIT $perPage OFFSET $start";

// Get total orders for pagination
$result = pg_query($conn, $countQuery);
$total = pg_fetch_result($result, 0, 0);
$pages = ceil($total / $perPage);

// Fetch previous orders
$result = pg_query($conn, $query);
$previous_orders = pg_fetch_all($result) ?: [];

// Get filter parameters for pagination links
function getFilterParams() {
    $params = [];
    if (isset($_GET['status']) && !empty($_GET['status'])) $params[] = "status=" . urlencode($_GET['status']);
    if (isset($_GET['type']) && !empty($_GET['type'])) $params[] = "type=" . urlencode($_GET['type']);
    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) $params[] = "date_from=" . urlencode($_GET['date_from']);
    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) $params[] = "date_to=" . urlencode($_GET['date_to']);
    if (isset($_GET['search']) && !empty($_GET['search'])) $params[] = "search=" . urlencode($_GET['search']);
    
    return !empty($params) ? "&" . implode("&", $params) : "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aditya - Orders Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
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
            --whatsapp-color: #25D366;
            --card-border-radius: 10px;
            --card-shadow: 0 8px 20px rgba(0,0,0,0.08);
            --transition-speed: 0.3s;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: #333;
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
        
        .nav-link {
            color: rgba(255,255,255,0.85) !important;
            font-weight: 500;
            transition: all 0.2s;
            padding: 8px 15px;
            border-radius: 6px;
        }
        
        .nav-link:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-link i {
            margin-right: 6px;
        }
        
        .card {
            border: none;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed) ease;
            border-radius: var(--card-border-radius);
            overflow: hidden;
            height: 100%;
            background-color: white;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 6px;
            padding: 8px 18px;
            font-weight: 500;
            box-shadow: 0 4px 6px rgba(230, 57, 70, 0.1);
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #d62b39;
            border-color: #d62b39;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(230, 57, 70, 0.2);
        }
        
        .status-pending { 
            border-left: 4px solid var(--warning-color); 
        }
        
        .status-preparing { 
            border-left: 4px solid var(--secondary-color); 
        }
        
        .status-served { 
            border-left: 4px solid var(--success-color);
            background-color: rgba(42, 157, 143, 0.03);
        }
        
        .status-out_for_delivery {
            border-left: 4px solid var(--info-color);
        }
        
        .status-delivered {
            border-left: 4px solid var(--success-color);
            background-color: rgba(42, 157, 143, 0.03);
        }
        
        .status-complete {
            border-left: 4px solid var(--dark-color);
            background-color: rgba(29, 53, 87, 0.03);
        }
        
        .order-card { 
            transition: all var(--transition-speed); 
            height: 100%;
            position: relative;
        }
        
        .order-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 12px 20px rgba(0,0,0,0.1); 
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            font-size: 0.95rem;
            box-shadow: none;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.15);
            border-color: var(--primary-color);
        }
        
        .page-link {
            color: var(--dark-color);
            border-radius: 6px;
            margin: 0 3px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .page-link:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(230, 57, 70, 0.2);
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 6px;
            font-size: 0.75rem;
            letter-spacing: 0.3px;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .bill-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            border-radius: var(--card-border-radius);
            box-shadow: var(--card-shadow);
        }
        
        .bill-header {
            text-align: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 25px;
            margin-bottom: 25px;
        }
        
        .bill-logo {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        
        .bill-title {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
        }
        
        .bill-details, .customer-details {
            flex: 1;
        }
        
        .bill-details h5, .customer-details h5 {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 8px;
        }
        
        .bill-details h5:after, .customer-details h5:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .bill-total {
            font-size: 24px;
            font-weight: 700;
            text-align: right;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
            color: var(--dark-color);
        }
        
        .bill-footer {
            text-align: center;
            margin-top: 35px;
            color: #666;
            font-size: 14px;
            border-top: 1px dashed #eee;
            padding-top: 20px;
        }
        
        .bill-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white;
            }
            .bill-container {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .action-buttons .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .action-buttons .btn i {
            font-size: 1rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .card-text {
            color: #555;
            margin-bottom: 15px;
        }
        
        .card-text i {
            width: 20px;
            color: var(--primary-color);
        }
        
        .status-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .order-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .order-info-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .order-info-item i {
            min-width: 16px;
            color: var(--primary-color);
            margin-top: 3px;
        }
        
        .order-cost {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #eee;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .order-cost i {
            color: var(--primary-color);
        }
        
        .filter-card .card-body {
            padding: 20px;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 25px;
            padding-bottom: 10px;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 0;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-weight: 600;
            color: #555;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #888;
            max-width: 400px;
            margin: 0 auto 20px;
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-top: 50px;
        }
        
        .footer p {
            margin-bottom: 0;
        }
        
        /* Improved button styles */
        .btn-action {
            border-radius: 6px;
            padding: 6px 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn-action i {
            font-size: 0.9rem;
        }
        
        .btn-action.btn-whatsapp {
            background-color: var(--whatsapp-color);
            color: white;
        }
        
        .btn-action.btn-whatsapp:hover {
            background-color: #128C7E;
        }
        
        .btn-action.btn-edit {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-action.btn-edit:hover {
            background-color: #3a6a8a;
        }
        
        .btn-action.btn-bill {
            background-color: var(--dark-color);
            color: white;
        }
        
        .btn-action.btn-bill:hover {
            background-color: #142638;
        }
        
        .btn-action.btn-status {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-action.btn-status:hover {
            background-color: #218a7e;
        }
        
        .btn-action.btn-delete {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-action.btn-delete:hover {
            background-color: #d55a3d;
        }
        
        /* Dashboard stats */
        .stats-card {
            border-radius: var(--card-border-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
            background-color: white;
            height: 100%;
            transition: all var(--transition-speed);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-icon.bg-primary {
            background-color: var(--primary-color);
        }
        
        .stats-icon.bg-success {
            background-color: var(--success-color);
        }
        
        .stats-icon.bg-warning {
            background-color: var(--warning-color);
        }
        
        .stats-icon.bg-info {
            background-color: var(--secondary-color);
        }
        
        .stats-icon.bg-dark {
            background-color: var(--dark-color);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .stats-label {
            color: #777;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        
        /* Address display */
        .address-box {
            background-color: rgba(0,0,0,0.02);
            border-radius: 6px;
            padding: 10px;
            margin-top: 8px;
            border-left: 3px solid var(--secondary-color);
            font-size: 0.9rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .bill-info {
                flex-direction: column;
                gap: 25px;
            }
        }
        
        /* Served orders darker style */
        .order-card.status-served,
        .order-card.status-delivered,
        .order-card.status-complete {
            opacity: 0.9;
        }
        
        /* Filter tags */
        .filter-tag {
            display: inline-block;
            background-color: #f0f0f0;
            color: #555;
            padding: 5px 12px;
            border-radius: 6px;
            margin-right: 8px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        }
        
        .filter-tag i {
            margin-left: 6px;
            cursor: pointer;
        }
        
        .filter-tag:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }
        
        /* Navigation */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 15px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            font-weight: 500;
        }
        
        /* Tab styling */
        .nav-tabs {
            border-bottom: 2px solid #eee;
           background-color:black;
            margin-bottom: 25px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            font-weight: 600;
            color: #666;
            padding: 12px 25px;
            margin-right: 8px;
        }
        
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            background-color: rgba(0,0,0,0.02);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background-color: transparent;
        }
        
        /* Section divider */
        .section-divider {
            display: flex;
            align-items: center;
            margin: 35px 0;
        }
        
        .section-divider h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            padding: 0 20px;
        }
        
        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background-color: #eee;
        }
        
        /* Order items styling */
        .order-items {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
        }
        
        .order-items ul {
            margin-bottom: 0;
        }
        
        .order-items li {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .order-items li:last-child {
            margin-bottom: 0;
        }
        
        .order-items .badge {
            margin: 0 5px;
        }
        
        /* Table status boxes */
        .table-status-box {
            width: 48px;
            height: 48px;
            color: #fff;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 11px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .table-status-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.15);
        }
        
        /* Action dropdown menu */
        .dropdown-menu {
            border: none;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 8px;
        }
        
        .dropdown-item {
            border-radius: 6px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown-item i {
            color: var(--primary-color);
        }
        
        /* Bill table styling */
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .bill-table th, .bill-table td {
            border: 2px solid #000;
            padding: 10px 15px;
        }
        
        .bill-table th {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .bill-table tfoot th, .bill-table tfoot td {
            font-weight: bold;
        }
        
        /* Thank you message */
        .thank-you-message {
            margin-top: 25px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            line-height: 1.6;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt" style="color:red;"></i> <b style="color:white;">Logout</b>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>
        
        <?php if (isset($bill_html)): ?>
            <!-- Bill Generation View -->
            <div class="bill-container mb-4">
                <div class="bill-header">
                    <div class="bill-logo">HOTEL ADITYA</div>
                    <p class="mb-0">Fine Dining & Excellent Service</p>
                </div>
                
                <div class="bill-title">
                    INVOICE #<?= $order['oid'] ?>
                </div>
                
                <div class="bill-info">
                    <div class="bill-details">
                        <h5>Order Details</h5>
                        <p>
                            <strong>Order ID:</strong> #<?= $order['oid'] ?><br>
                            <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order['order_at'])) ?><br>
                            <strong>Order Type:</strong> <?= $order['order_type'] == 'tableorder' ? 'Table Order' : 'Home Delivery' ?><br>
                            <?php if ($order['order_type'] == 'tableorder'): ?>
                                <strong>Table Number:</strong> <?= $order['table_no'] ?><br>
                            <?php endif; ?>
                            <strong>Status:</strong> 
                            <span class="badge <?= getStatusBadgeClass($order['order_status']) ?>">
                                <i class="bi <?= getStatusIcon($order['order_status']) ?>"></i>
                                <?= getStatusLabel($order['order_status']) ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="customer-details">
                        <h5>Customer Details</h5>
                        <p>
                            <strong>Name:</strong> <?= htmlspecialchars($order['cust_name']) ?><br>
                            <strong>Phone:</strong> <?= htmlspecialchars($order['cust_no']) ?><br>
                            <?php if ($order['order_type'] == 'homeorder' && !empty($order['delivery_address'])): ?>
                                <strong>Delivery Address:</strong><br>
                                <div class="address-box">
                                    <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                                </div>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
           
                <div class="mt-4">
    <!-- Customer Name -->
    <div style="font-weight: bold; font-size: 16px; margin-bottom: 15px;">
        <strong>Name:</strong> <?= htmlspecialchars($order['cust_name']) ?><br>
    </div>

    <h5 style="font-weight: bold; margin-bottom: 15px;">Order Items</h5>
    <table class="bill-table" border="1" cellspacing="0" cellpadding="5" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>#</th>
                <th>Item</th>
                <th>Item Price</th>
                <th>Quantity</th>
                <th>Total Price</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT oi.*, m.pname as menu_name 
                      FROM order_items oi 
                      JOIN menu m ON oi.mid = m.mid 
                      WHERE oi.oid = " . $order['oid'];
            $result = pg_query($conn, $query);
            $items = pg_fetch_all($result) ?: [];

            $chapati_price = 10;
            $roti_price = 20;
            $total = 0;
            $count = 1;

            foreach ($items as $item):
                $chapati_total = (!empty($item['chapati'])) ? $item['chapati'] * $chapati_price : 0;
                $roti_total = (!empty($item['roti'])) ? $item['roti'] * $roti_price : 0;

                // Calculate base item total (excluding chapati & roti)
                $base_total = $item['price'] - $chapati_total - $roti_total;

                // Price per single item unit
                $item_price = $item['quantity'] ? $base_total / $item['quantity'] : 0;

                $total += $item['price'];

                // Number of rows to span for total price column
                $rowspan = 1 + (!empty($item['chapati']) ? 1 : 0) + (!empty($item['roti']) ? 1 : 0);
            ?>
            <tr>
                <td><?= $count++ ?></td>
                <td><?= htmlspecialchars($item['menu_name']) ?></td>
                <td>₹<?= number_format($item_price, 2) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td rowspan="<?= $rowspan ?>" style="vertical-align: middle;">₹<?= number_format($item['price'], 2) ?></td>
            </tr>
            <?php if (!empty($item['chapati'])): ?>
            <tr>
                <td></td>
                <td>- Chapati</td>
                <td>₹<?= number_format($chapati_price, 2) ?></td>
                <td><?= $item['chapati'] ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($item['roti'])): ?>
            <tr>
                <td></td>
                <td>- Roti</td>
                <td>₹<?= number_format($roti_price, 2) ?></td>
                <td><?= $item['roti'] ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="4" style="text-align: right;">Total:</td>
                <td>₹<?= number_format($total, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Thank You Message -->
    <div class="thank-you-message" style="margin-top: 20px; font-style: italic; color: #555;">
        धन्यवाद! कृपया फिर से आइए।<br>
        <em>Thanks for visiting. Please visit again!</em>
    </div>
</div>

<div class="bill-footer" style="margin-top: 30px; font-weight: bold; text-align: center;">
    <p>Thank you for dining with us!</p>
    <p>Hotel Aditya - Where Every Meal is a Celebration</p>
</div>

<div class="bill-actions no-print" style="margin-top: 20px; text-align: center;">
    <button class="btn btn-action btn-primary" onclick="window.print()" style="margin-right: 10px;">
        <i class="bi bi-printer"></i> Print Bill
    </button>
    
    <button class="btn btn-action btn-whatsapp" onclick="confirmWhatsApp('<?= $order['oid'] ?>', 'bill')" style="margin-right: 10px;">
        <i class="bi bi-whatsapp"></i> Send via WhatsApp
    </button>
    
    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-action btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Orders
    </a>
</div>

<?php else: ?>

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-primary">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="stats-value"><?= $today_orders_count ?></div>
                        <div class="stats-label">Today's Orders</div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background-color: #2a9d8f;">
                            <i class="bi bi-table"></i>
                        </div>
                        <div class="stats-value"><?= $table_order ?></div>
                        <div class="stats-label">Today's Table Orders</div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background-color: #e76f51;">
                            <i class="bi bi-house-door-fill"></i>
                        </div>
                        <div class="stats-value"><?= $homeorder ?></div>
                        <div class="stats-label">Today's Home Orders</div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background-color: #2a9d8f;">
                            <i class="bi bi-grid-fill"></i>
                        </div>
                        <div class="stats-value">Table Status</div>
                        <div class="stats-label">Latest Order Completion</div>

                        <!-- Table Boxes -->
                        <div class="d-flex flex-wrap justify-content-between mt-3" style="gap: 6px;">
                            <?php
                            // Get today's date
                            $today = date('Y-m-d');

                            // Loop through tables 1 to 10
                            for ($i = 1; $i <= 10; $i++) {
                                // Query to check if any orders exist for this table on today's date
                                $query = "SELECT order_status FROM orders WHERE table_no = $i AND order_at::date = '$today' ORDER BY order_at DESC LIMIT 1";
                                $result = pg_query($conn, $query);
                                
                                if ($result && pg_num_rows($result) > 0) {
                                    // If there's an order, fetch the order status
                                    $status = pg_fetch_result($result, 0, 0);
                                    
                                    if ($status === 'complete') {
                                        // If the order status is 'complete', set green
                                        $bgColor = '#2ecc71'; // Green
                                        $label = 'Empty';
                                    } else {
                                        // If the order is not complete, set red (Busy)
                                        $bgColor = '#e74c3c'; // Red
                                        $label = 'Busy';
                                    }
                                } else {
                                    // If there's no order for this table today, set gray (No order)
                                    $bgColor = '#bdc3c7'; // Gray
                                    $label = 'Empty';
                                }
                                ?>
                                <div class="table-status-box" style="background-color: <?= $bgColor ?>;" onclick="showTableInfo(<?= $i ?>)">
                                    <div>T<?= $i ?></div>
                                    <div style="font-size: 10px;"><?= $label ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-success">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                        <div class="stats-value">₹<?= number_format($today_earnings, 2) ?></div>
                        <div class="stats-label">Today's Earnings</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stats-value"><?= $pending_orders ?></div>
                        <div class="stats-label">Pending Orders</div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-success">
                            <i class="bi bi-egg-fried"></i>
                        </div>
                        <div class="stats-value"><?= $serv_order ?></div>
                        <div class="stats-label">Served Order Count</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-info">
                            <i class="bi bi-fire"></i>
                        </div>
                        <div class="stats-value"><?= $preparing_orders ?></div>
                        <div class="stats-label">Preparing Orders</div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Stats Row -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-dark">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <div class="stats-value"><?= $complete_orders ?></div>
                        <div class="stats-label">Completed Orders</div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-success">
                            <i class="bi bi-house-check"></i>
                        </div>
                        <div class="stats-value"><?= $delivered_orders ?></div>
                        <div class="stats-label">Delivered Orders</div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <h2 class="section-title"><i class="bi bi-list-check"></i> Orders Management</h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="report.php" class="btn btn-primary">
                        <i class="bi bi-graph-up"></i> View Detailed Reports
                    </a>
                </div>
            </div>

           
            <!-- Orders Tabs -->
            <ul class="nav nav-tabs" id="ordersTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="today-tab" data-bs-toggle="tab" data-bs-target="#today-orders" type="button" role="tab" aria-controls="today-orders" aria-selected="true">
                        <i class="bi bi-calendar-day"></i> Today's Orders (<?= count($today_orders) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="previous-tab" data-bs-toggle="tab" data-bs-target="#previous-orders" type="button" role="tab" aria-controls="previous-orders" aria-selected="false">
                        <i class="bi bi-calendar-week"></i> Previous Orders
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="ordersTabContent">
                <!-- Today's Orders Tab -->
                <div class="tab-pane fade show active" id="today-orders" role="tabpanel" aria-labelledby="today-tab">
                    <?php if (empty($today_orders)): ?>
                        <div class="col-12 empty-state">
                            <i class="bi bi-inbox"></i>
                            <h3>No Orders Today</h3>
                            <p>There are no orders for today yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($today_orders as $order): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card order-card status-<?= $order['order_status'] ?>">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="bi bi-receipt"></i> Order #<?= $order['oid'] ?>
                                            </span>
                                            <span class="badge <?= getStatusBadgeClass($order['order_status']) ?>">
                                                <i class="bi <?= getStatusIcon($order['order_status']) ?>"></i>
                                                <?= getStatusLabel($order['order_status']) ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?= htmlspecialchars($order['cust_name']) ?>
                                            </h5>
                                            
                                            <div class="order-info">
                                                <div class="order-info-item">
                                                    <i class="bi bi-telephone"></i>
                                                    <span><?= htmlspecialchars($order['cust_no']) ?></span>
                                                </div>
                                                <div class="order-info-item">
                                                    <?php if ($order['order_type'] == 'tableorder'): ?>
                                                        <i class="bi bi-cup-hot"></i>
                                                        <span>Table <?= $order['table_no'] ?></span>
                                                    <?php else: ?>
                                                        <i class="bi bi-house"></i>
                                                        <span>Home Delivery</span>
                                                        <?php if (!empty($order['delivery_address'])): ?>
                                                            <div class="address-box mt-1 w-100">
                                                                <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="order-info-item">
                                                    <i class="bi bi-clock"></i>
                                                    <span><?= date('g:i A', strtotime($order['order_at'])) ?></span>
                                                </div>
                                            </div>

                                            <!-- Order Items -->
                                            <div class="order-items mt-2 mb-2">
                                                <small class="text-muted d-block mb-1">Order Items:</small>
                                                <?php 
                                                $orderItems = getOrderItems($order['oid']);
                                                if (!empty($orderItems)): 
                                                ?>
                                                    <ul class="list-unstyled" style="font-size: 0.85rem;">
                                                        <?php foreach ($orderItems as $item): ?>
                                                            <li>
                                                                <i class="bi bi-dot"></i>
                                                                <?= htmlspecialchars($item['menu_name']) ?>
                                                                <span class="badge bg-secondary">x<?= $item['quantity'] ?></span>
                                                                <span class="text-muted">₹<?= number_format($item['price'], 2) ?></span>
                                                            </li>
                                                            <?php if (isset($item['chapati']) && $item['chapati'] > 0): ?>
                                                                <li class="ms-3 small text-muted">
                                                                    <i class="bi bi-dash"></i> Chapati x<?= $item['chapati'] ?>
                                                                    <span class="text-muted">₹<?= number_format($item['chapati'] * CHAPATI_PRICE, 2) ?></span>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if (isset($item['roti']) && $item['roti'] > 0): ?>
                                                                <li class="ms-3 small text-muted">
                                                                    <i class="bi bi-dash"></i> Roti x<?= $item['roti'] ?>
                                                                    <span class="text-muted">₹<?= number_format($item['roti'] * ROTI_PRICE, 2) ?></span>
                                                                </li>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="text-muted small">No items found</p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="order-cost">
                                                <i class="bi bi-currency-rupee"></i> Total: ₹<?= number_format($order['ordercost'], 2) ?>
                                            </div>
                                            
                                            <!-- Action Dropdown Menu -->
                                            <div class="d-flex justify-content-end mt-3">
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="actionDropdown<?= $order['oid'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots"></i> Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown<?= $order['oid'] ?>">
                                                        <li>
                                                            <a class="dropdown-item" href="<?= $_SERVER['PHP_SELF'] ?>?action=generate_bill&id=<?= $order['oid'] ?>">
                                                                <i class="bi bi-receipt"></i> View Bill
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0)" onclick="confirmWhatsApp('<?= $order['oid'] ?>', 'bill')">
                                                                <i class="bi bi-whatsapp"></i> Send Bill
                                                            </a>
                                                        </li>
                                                        
                                                        <?php if ($order['order_type'] == 'tableorder'): ?>
                                                            <?php if ($order['order_status'] != 'preparing'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="updateStatus('<?= $order['oid'] ?>', 'preparing')">
                                                                        <i class="bi bi-fire"></i> Mark as Preparing
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($order['order_status'] != 'served'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="updateStatus('<?= $order['oid'] ?>', 'served')">
                                                                        <i class="bi bi-check-circle"></i> Mark as Served
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <?php if ($order['order_status'] != 'preparing'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="updateStatus('<?= $order['oid'] ?>', 'preparing')">
                                                                        <i class="bi bi-fire"></i> Mark as Preparing
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($order['order_status'] != 'out_for_delivery'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="updateStatus('<?= $order['oid'] ?>', 'out_for_delivery')">
                                                                        <i class="bi bi-truck"></i> Mark as Out for Delivery
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($order['order_status'] != 'delivered'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="updateStatus('<?= $order['oid'] ?>', 'delivered')">
                                                                        <i class="bi bi-house-check"></i> Mark as Delivered
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($order['order_status'] != 'complete'): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="confirmComplete('<?= $order['oid'] ?>')">
                                                                    <i class="bi bi-trophy"></i> Mark as Complete
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmDelete('<?= $order['oid'] ?>')">
                                                                <i class="bi bi-trash"></i> Delete Order
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Previous Orders Tab -->
                <div class="tab-pane fade" id="previous-orders" role="tabpanel" aria-labelledby="previous-tab">
                    <?php if (empty($previous_orders)): ?>
                        <div class="col-12 empty-state">
                            <i class="bi bi-inbox"></i>
                            <h3>No Previous Orders Found</h3>
                            <p>Try adjusting your search or filter criteria</p>
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-primary mt-2">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($previous_orders as $order): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card order-card status-<?= $order['order_status'] ?>">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="bi bi-receipt"></i> Order #<?= $order['oid'] ?>
                                            </span>
                                            <span class="badge <?= getStatusBadgeClass($order['order_status']) ?>">
                                                <i class="bi <?= getStatusIcon($order['order_status']) ?>"></i>
                                                <?= getStatusLabel($order['order_status']) ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?= htmlspecialchars($order['cust_name']) ?>
                                            </h5>
                                            
                                            <div class="order-info">
                                                <div class="order-info-item">
                                                    <i class="bi bi-telephone"></i>
                                                    <span><?= htmlspecialchars($order['cust_no']) ?></span>
                                                </div>
                                                <div class="order-info-item">
                                                    <?php if ($order['order_type'] == 'tableorder'): ?>
                                                        <i class="bi bi-cup-hot"></i>
                                                        <span>Table <?= $order['table_no'] ?></span>
                                                    <?php else: ?>
                                                        <i class="bi bi-house"></i>
                                                        <span>Home Delivery</span>
                                                        <?php if (!empty($order['delivery_address'])): ?>
                                                            <div class="address-box mt-1 w-100">
                                                                <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="order-info-item">
                                                    <i class="bi bi-clock"></i>
                                                    <span><?= date('M j, Y g:i A', strtotime($order['order_at'])) ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Order Items -->
                                            <div class="order-items mt-2 mb-2">
                                                <small class="text-muted d-block mb-1">Order Items:</small>
                                                <?php 
                                                $orderItems = getOrderItems($order['oid']);
                                                if (!empty($orderItems)): 
                                                ?>
                                                    <ul class="list-unstyled" style="font-size: 0.85rem;">
                                                        <?php foreach ($orderItems as $item): ?>
                                                            <li>
                                                                <i class="bi bi-dot"></i>
                                                                <?= htmlspecialchars($item['menu_name']) ?>
                                                                <span class="badge bg-secondary">x<?= $item['quantity'] ?></span>
                                                                <span class="text-muted">₹<?= number_format($item['price'], 2) ?></span>
                                                            </li>
                                                            <?php if (isset($item['chapati']) && $item['chapati'] > 0): ?>
                                                                <li class="ms-3 small text-muted">
                                                                    <i class="bi bi-dash"></i> Chapati x<?= $item['chapati'] ?>
                                                                    <span class="text-muted">₹<?= number_format($item['chapati'] * CHAPATI_PRICE, 2) ?></span>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if (isset($item['roti']) && $item['roti'] > 0): ?>
                                                                <li class="ms-3 small text-muted">
                                                                    <i class="bi bi-dash"></i> Roti x<?= $item['roti'] ?>
                                                                    <span class="text-muted">₹<?= number_format($item['roti'] * ROTI_PRICE, 2) ?></span>
                                                                </li>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="text-muted small">No items found</p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="order-cost">
                                                <i class="bi bi-currency-rupee"></i> Total: ₹<?= number_format($order['ordercost'], 2) ?>
                                            </div>
                                            
                                            <!-- Action Dropdown Menu -->
                                            <div class="d-flex justify-content-end mt-3">
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="actionDropdown<?= $order['oid'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots"></i> Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown<?= $order['oid'] ?>">
                                                        <li>
                                                            <a class="dropdown-item" href="<?= $_SERVER['PHP_SELF'] ?>?action=generate_bill&id=<?= $order['oid'] ?>">
                                                                <i class="bi bi-receipt"></i> View Bill
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0)" onclick="confirmWhatsApp('<?= $order['oid'] ?>', 'bill')">
                                                                <i class="bi bi-whatsapp"></i> Send Bill
                                                            </a>
                                                        </li>
                                                        
                                                        <?php if ($order['order_status'] != 'complete'): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="confirmComplete('<?= $order['oid'] ?>')">
                                                                    <i class="bi bi-trophy"></i> Mark as Complete
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmDelete('<?= $order['oid'] ?>')">
                                                                <i class="bi bi-trash"></i> Delete Order
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination for Previous Orders -->
                        <?php if ($pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page-1 ?><?= getFilterParams() ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php 
                                    // Show limited page numbers with ellipsis
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($pages, $page + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1' . getFilterParams() . '">1</a></li>';
                                        if ($startPage > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }
                                    
                                    for($i = $startPage; $i <= $endPage; $i++): 
                                    ?>
                                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= getFilterParams() ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; 
                                    
                                    if ($endPage < $pages) {
                                        if ($endPage < $pages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $pages . getFilterParams() . '">' . $pages . '</a></li>';
                                    }
                                    ?>

                                    <?php if ($page < $pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page+1 ?><?= getFilterParams() ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer mt-5">
        <div class="container text-center">
            <p>© <?= date('Y') ?> Hotel Aditya. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        // SweetAlert confirmation for WhatsApp
        function confirmWhatsApp(orderId, type) {
            Swal.fire({
                title: 'Send WhatsApp Message?',
                text: 'Do you want to send a WhatsApp message to the customer?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#25D366',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, send it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (type === 'bill') {
                        window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?action=whatsapp_bill&id=' + orderId;
                    }
                }
            });
        }
        
        // Update status with confirmation for WhatsApp notification
        function updateStatus(orderId, status) {
            let statusText = '';
            switch(status) {
                case 'preparing': statusText = 'Preparing'; break;
                case 'served': statusText = 'Served'; break;
                case 'out_for_delivery': statusText = 'Out for Delivery'; break;
                case 'delivered': statusText = 'Delivered'; break;
                default: statusText = status;
            }
            
            // First update the status
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'update_status';
            
            const oidInput = document.createElement('input');
            oidInput.type = 'hidden';
            oidInput.name = 'oid';
            oidInput.value = orderId;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            
            form.appendChild(actionInput);
            form.appendChild(oidInput);
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Show table information
        function showTableInfo(tableNo) {
            // AJAX request to get table information
            fetch('get-table-info.php?table=' + tableNo)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.order) {
                        const order = data.order;
                        const orderTime = new Date(order.order_at);
                        const formattedTime = orderTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        Swal.fire({
                            title: 'Table ' + tableNo + ' Information',
                            html: `
                                <div class="text-start">
                                    <p><strong>Customer:</strong> ${order.cust_name}</p>
                                    <p><strong>Phone:</strong> ${order.cust_no}</p>
                                    <p><strong>Order Time:</strong> ${formattedTime}</p>
                                    <p><strong>Status:</strong> ${getStatusLabel(order.order_status)}</p>
                                    <p><strong>Total Amount:</strong> ₹${parseFloat(order.ordercost).toFixed(2)}</p>
                                </div>
                            `,
                            icon: 'info',
                            confirmButtonText: 'Close'
                        });
                    } else {
                        Swal.fire({
                            title: 'Table ' + tableNo,
                            text: 'This table is currently empty.',
                            icon: 'info',
                            confirmButtonText: 'Close'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching table info:', error);
                    
                    // Fallback if AJAX fails - use PHP function directly
                    <?php
                    // Generate JavaScript object with table information
                    echo "const tableInfo = {";
                    for ($i = 1; $i <= 10; $i++) {
                        $info = getTableInfo($i);
                        echo "$i: " . ($info ? json_encode($info) : "null") . ",";
                    }
                    echo "};";
                    ?>
                    
                    const order = tableInfo[tableNo];
                    if (order) {
                        const orderTime = new Date(order.order_at);
                        const formattedTime = orderTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        Swal.fire({
                            title: 'Table ' + tableNo + ' Information',
                            html: `
                                <div class="text-start">
                                    <p><strong>Customer:</strong> ${order.cust_name}</p>
                                    <p><strong>Phone:</strong> ${order.cust_no}</p>
                                    <p><strong>Order Time:</strong> ${formattedTime}</p>
                                    <p><strong>Status:</strong> ${getStatusLabel(order.order_status)}</p>
                                    <p><strong>Total Amount:</strong> ₹${parseFloat(order.ordercost).toFixed(2)}</p>
                                </div>
                            `,
                            icon: 'info',
                            confirmButtonText: 'Close'
                        });
                    } else {
                        Swal.fire({
                            title: 'Table ' + tableNo,
                            text: 'This table is currently empty.',
                            icon: 'info',
                            confirmButtonText: 'Close'
                        });
                    }
                });
        }
        
        // Helper function to get status label
        function getStatusLabel(status) {
            switch (status) {
                case 'pending': return 'Pending';
                case 'preparing': return 'Preparing';
                case 'served': return 'Served';
                case 'out_for_delivery': return 'Out for Delivery';
                case 'delivered': return 'Delivered';
                case 'complete': return 'Completed';
                default: return status.charAt(0).toUpperCase() + status.slice(1);
            }
        }
        
        // SweetAlert confirmation for complete
        function confirmComplete(orderId) {
            Swal.fire({
                title: 'Complete Order?',
                text: 'Do you want to mark this order as complete?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1d3557',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, complete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'complete_order';
                    
                    const oidInput = document.createElement('input');
                    oidInput.type = 'hidden';
                    oidInput.name = 'oid';
                    oidInput.value = orderId;
                    
                    form.appendChild(actionInput);
                    form.appendChild(oidInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        // SweetAlert confirmation for delete
        function confirmDelete(orderId) {
            Swal.fire({
                title: 'Delete Order?',
                text: 'Are you sure you want to delete this order? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e76f51',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_order';
                    
                    const oidInput = document.createElement('input');
                    oidInput.type = 'hidden';
                    oidInput.name = 'oid';
                    oidInput.value = orderId;
                    
                    form.appendChild(actionInput);
                    form.appendChild(oidInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        // Check if we need to show WhatsApp notification after status update
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['status_updated'])): ?>
                const orderId = <?= $_SESSION['status_updated']['oid'] ?>;
                const status = '<?= $_SESSION['status_updated']['status'] ?>';
                
                Swal.fire({
                    title: 'Status Updated',
                    text: 'Do you want to notify the customer via WhatsApp?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#25D366',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, notify them!',
                    cancelButtonText: 'No, skip notification'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?action=whatsapp_status&id=' + orderId + '&status=' + status;
                    }
                });
                
                <?php 
                unset($_SESSION['status_updated']);
                ?>
            <?php endif; ?>
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>

<!-- Create a PHP file to handle AJAX requests for table information -->
<?php
// Create get-table-info.php file
$get_table_info_content = <<<'EOT'
<?php
require_once 'assets/config/dbconnect.php';

if (isset($_GET['table'])) {
    $tableNo = (int)$_GET['table'];
    
    $today = date('Y-m-d');
    $query = "SELECT * FROM orders 
              WHERE table_no = $tableNo 
              AND order_at::date = '$today' 
              AND order_status NOT IN ('complete') 
              ORDER BY order_at DESC 
              LIMIT 1";
    
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) > 0) {
        $order = pg_fetch_assoc($result);
        echo json_encode([
            'status' => 'success',
            'order' => $order
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No active orders for this table'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Table number not provided'
    ]);
}
EOT;

if (@file_put_contents('get-table-info.php', $get_table_info_content) !== false) {
    // success, do nothing
} else {
    // failure, do nothing
}

?>
