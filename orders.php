<?php
require_once 'assets/config/dbconnect.php';
session_start();

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_order':
                // Update existing order
                $oid = (int)$_POST['oid'];
                $cust_name = pg_escape_string($conn, $_POST['cust_name']);
                // Ensure phone number has +91 prefix
                $cust_no = pg_escape_string($conn, formatPhoneNumber($_POST['cust_no']));
                $order_type = pg_escape_string($conn, $_POST['order_type']);
                $table_no = ($order_type == 'tableorder') ? (int)$_POST['table_no'] : null;
                $order_status = pg_escape_string($conn, $_POST['order_status']);
                $ordercost = (float)$_POST['ordercost'];
                
                // Get previous status
                $result = pg_query($conn, "SELECT order_status FROM orders WHERE oid = $oid");
                $prev_status = pg_fetch_result($result, 0, 0);
                
                $query = "UPDATE orders SET 
                          cust_name = '$cust_name',
                          cust_no = '$cust_no',
                          order_type = '$order_type',
                          table_no = " . ($table_no ? $table_no : 'NULL') . ",
                          order_status = '$order_status',
                          ordercost = $ordercost
                          WHERE oid = $oid";
                pg_query($conn, $query);
                
                // Redirect to WhatsApp if status changed and notification is requested
                if (isset($_POST['send_whatsapp']) && $_POST['send_whatsapp'] == 'yes' && $prev_status != $order_status) {
                    $phone = cleanPhoneNumber($cust_no);
                    $status_message = urlencode(getStatusMessage($order_status, $oid, $ordercost));
                    header("Location: https://wa.me/$phone?text=$status_message");
                    exit();
                }
                break;
                
            case 'delete_order':
                // Delete order
                $oid = (int)$_POST['oid'];
                $query = "DELETE FROM orders WHERE oid = $oid";
                pg_query($conn, $query);
                break;
                
                
            case 'complete_order':
                // mark complete order
                $oid = (int)$_POST['oid'];
    $query = "UPDATE orders SET order_status = 'complete' WHERE oid = $oid";
                pg_query($conn, $query);
                break;
                
            case 'update_status':
                // Update order status
                $oid = (int)$_POST['oid'];
                $status = pg_escape_string($conn, $_POST['status']);
                
                // Get customer phone and order cost
                $result = pg_query($conn, "SELECT cust_no, ordercost FROM orders WHERE oid = $oid");
                $order_data = pg_fetch_assoc($result);
                
                $query = "UPDATE orders SET order_status = '$status' WHERE oid = $oid";
                pg_query($conn, $query);
                
                // Redirect to WhatsApp if requested
                if (isset($_POST['send_whatsapp']) && $_POST['send_whatsapp'] == 'yes') {
                    $phone = cleanPhoneNumber($order_data['cust_no']);
                    $status_message = urlencode(getStatusMessage($status, $oid, $order_data['ordercost']));
                    header("Location: https://wa.me/$phone?text=$status_message");
                    exit();
                }
                break;
                
            case 'send_bill':
                // Send bill via WhatsApp
                $oid = (int)$_POST['oid'];
                $result = pg_query($conn, "SELECT * FROM orders WHERE oid = $oid");
                $order = pg_fetch_assoc($result);
                
                if ($order) {
                    $phone = cleanPhoneNumber($order['cust_no']);
                    $bill_message = urlencode(generateBillMessage($order));
                    header("Location: https://wa.me/$phone?text=$bill_message");
                    exit();
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header("Location: ".$_SERVER['PHP_SELF'] . (isset($_GET['page']) ? "?page=".$_GET['page'] : ""));
        exit();
    }
}

// Helper function to format phone number with +91 prefix
function formatPhoneNumber($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If the number doesn't start with 91 and is 10 digits, add +91
    if (strlen($phone) == 10) {
        return '+91' . $phone;
    } elseif (strlen($phone) == 12 && substr($phone, 0, 2) == '91') {
        return '+' . $phone;
    } elseif (substr($phone, 0, 1) == '+') {
        return $phone;
    } else {
        return '+91' . $phone;
    }
}

// Helper function to clean phone number for WhatsApp API
function cleanPhoneNumber($phone) {
    // Remove any non-digit characters and the + sign
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
        default:
            return "Your order #$oid at Hotel Aditya has been updated to: $status. Total amount: ₹$cost";
    }
}

// Helper function to generate bill message for WhatsApp
function generateBillMessage($order) {
    $message = "🧾 *HOTEL ADITYA - BILL RECEIPT* 🧾\n\n";
    $message .= "📋 *Order #" . $order['oid'] . "*\n";
    $message .= "👤 Customer: " . $order['cust_name'] . "\n";
    $message .= "📱 Phone: " . $order['cust_no'] . "\n";
    $message .= "📅 Date: " . date('d-m-Y h:i A', strtotime($order['order_at'])) . "\n";
    $message .= "🍽️ Type: " . ($order['order_type'] == 'tableorder' ? "Table #".$order['table_no'] : "Home Delivery") . "\n";
    $message .= "📊 Status: " . ucfirst($order['order_status']) . "\n\n";
    $message .= "💰 *Total Amount: ₹" . number_format($order['ordercost'], 2) . "*\n\n";
    $message .= "Thank you for dining with Hotel Aditya! 🙏\n";
    $message .= "Visit us again soon!";
    
    return $message;
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

// Pending orders count
$query = "SELECT COUNT(*) FROM orders WHERE order_status = 'pending'";
$result = pg_query($conn, $query);
$pending_orders = pg_fetch_result($result, 0, 0);

// Preparing orders count
$query = "SELECT COUNT(*) FROM orders WHERE order_status = 'preparing'";
$result = pg_query($conn, $query);
$preparing_orders = pg_fetch_result($result, 0, 0);

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Base query
$query = "SELECT * FROM orders";
$countQuery = "SELECT COUNT(oid) FROM orders";

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
    $filters[] = "(cust_name ILIKE '%$search%' OR cust_no ILIKE '%$search%' OR CAST(oid AS TEXT) ILIKE '%$search%')";
}

if (!empty($filters)) {
    $query .= " WHERE " . implode(" AND ", $filters);
    $countQuery .= " WHERE " . implode(" AND ", $filters);
}

// Add sorting and pagination
$query .= " ORDER BY order_at DESC LIMIT $perPage OFFSET $start";

// Get total orders for pagination
$result = pg_query($conn, $countQuery);
$total = pg_fetch_result($result, 0, 0);
$pages = ceil($total / $perPage);

// Fetch orders
$result = pg_query($conn, $query);
$orders = pg_fetch_all($result) ?: [];

// Check if we're in edit mode
$edit_mode = false;
$current_order = null;

if (isset($_GET['edit'])) {
    $oid = (int)$_GET['edit'];
    $query = "SELECT * FROM orders WHERE oid = $oid";
    $result = pg_query($conn, $query);
    $current_order = pg_fetch_assoc($result);
    $edit_mode = true;
}

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e63946;
            --secondary-color: #457b9d;
            --light-color: #f1faee;
            --dark-color: #1d3557;
            --success-color: #2a9d8f;
            --warning-color: #e9c46a;
            --danger-color: #e76f51;
            --whatsapp-color: #25D366;
            --card-border-radius: 12px;
            --card-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--dark-color), #0f2942) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            display: flex;
            align-items: center;
            font-size: 1.5rem;
        }
        
        .navbar-brand span {
            color: var(--primary-color);
            margin-left: 5px;
        }
        
        .navbar-brand img {
            height: 35px;
            margin-right: 10px;
        }
        
        .card {
            border: none;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border-radius: var(--card-border-radius);
            overflow: hidden;
            height: 100%;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #d62b39;
            border-color: #d62b39;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(230, 57, 70, 0.3);
        }
        
        .status-pending { 
            border-left: 4px solid var(--warning-color); 
        }
        
        .status-preparing { 
            border-left: 4px solid var(--secondary-color); 
        }
        
        .status-served { 
            border-left: 4px solid var(--success-color);
            background-color: rgba(42, 157, 143, 0.05); /* Darker background for served orders */
        }
        
        .order-card { 
            transition: all 0.3s; 
            height: 100%;
        }
        
        .order-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 24px rgba(0,0,0,0.15); 
        }
        
        .form-section { 
            display: <?= ($edit_mode) ? 'block' : 'none' ?>;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.2);
            border-color: var(--primary-color);
        }
        
        .page-link {
            color: var(--dark-color);
            border-radius: 50%;
            margin: 0 3px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .bill-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: var(--card-border-radius);
            box-shadow: var(--card-shadow);
        }
        
        .bill-header {
            text-align: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .bill-logo {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .bill-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .bill-details, .customer-details {
            flex: 1;
        }
        
        .bill-total {
            font-size: 22px;
            font-weight: 700;
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .bill-footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
        
        .bill-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
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
            }
        }
        
        .search-container {
            position: relative;
        }
        
        .search-container .bi-search {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .search-input {
            padding-left: 40px;
            border-radius: 50px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.2);
            border-color: var(--primary-color);
        }
        
        .whatsapp-badge {
            background-color: var(--whatsapp-color);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .btn-whatsapp {
            background-color: var(--whatsapp-color);
            color: white;
            border: none;
        }
        
        .btn-whatsapp:hover {
            background-color: #128C7E;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .action-buttons .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 50px;
            transition: all 0.2s;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .action-buttons .btn i {
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-title {
            font-weight: 600;
            margin-bottom: 10px;
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
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .order-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .order-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .order-info-item i {
            min-width: 20px;
            color: var(--primary-color);
        }
        
        .order-cost {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }
        
        .filter-card {
            background: white;
            border-radius: var(--card-border-radius);
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
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
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
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
            border-radius: 50px;
            padding: 8px 16px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-action i {
            font-size: 1rem;
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
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.8rem;
            color: white;
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
        }
        
        /* Served orders darker style */
        .order-card.status-served {
            opacity: 0.85;
        }
        
        .order-card.status-served .card-title,
        .order-card.status-served .order-info-item {
            color: #444;
        }
        
        /* Filter tags */
        .filter-tag {
            display: inline-block;
            background-color: #f0f0f0;
            color: #555;
            padding: 5px 12px;
            border-radius: 50px;
            margin-right: 8px;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }
        
        .filter-tag i {
            margin-left: 5px;
            cursor: pointer;
        }
        
        .filter-tag:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-building"></i>
                Hotel <span>Aditya</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= $_SERVER['PHP_SELF'] ?>">
                            <i class="bi bi-list-check"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
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
                            <span class="badge bg-<?= 
                                $order['order_status'] == 'pending' ? 'warning' : 
                                ($order['order_status'] == 'preparing' ? 'primary' : 'success') 
                            ?>">
                                <?= ucfirst($order['order_status']) ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="customer-details">
                        <h5>Customer Details</h5>
                        <p>
                            <strong>Name:</strong> <?= htmlspecialchars($order['cust_name']) ?><br>
                            <strong>Phone:</strong> <?= htmlspecialchars($order['cust_no']) ?><br>
                        </p>
                    </div>
                </div>
                
                <div class="bill-total">
                    Total Amount: ₹<?= number_format($order['ordercost'], 2) ?>
                </div>
                
                <div class="bill-footer">
                    <p>Thank you for dining with us!</p>
                    <p>Hotel Aditya - Where Every Meal is a Celebration</p>
                </div>
                
                <div class="bill-actions no-print">
                    <button class="btn btn-action btn-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Bill
                    </button>
                    
                    <a href="<?= $_SERVER['PHP_SELF'] ?>?action=whatsapp_bill&id=<?= $order['oid'] ?>" class="btn btn-action btn-whatsapp">
                        <i class="bi bi-whatsapp"></i> Send via WhatsApp
                    </a>
                    
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-action btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                </div>
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
                        <div class="stats-icon bg-info">
                            <i class="bi bi-fire"></i>
                        </div>
                        <div class="stats-value"><?= $preparing_orders ?></div>
                        <div class="stats-label">Preparing Orders</div>
                    </div>
                </div>
            </div>
            
            <!-- Order Form (Edit) -->
            <div class="card mb-4 form-section">
                <div class="card-header">
                    <i class="bi bi-pencil-square"></i>
                    <?= $edit_mode ? 'Edit Order #' . $current_order['oid'] : '' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_order">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="oid" value="<?= $current_order['oid'] ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer Name</label>
                                <input type="text" name="cust_name" class="form-control" required 
                                       value="<?= $edit_mode ? htmlspecialchars($current_order['cust_name']) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text">+91</span>
                                    <input type="text" name="cust_no" class="form-control" required 
                                           value="<?= $edit_mode ? preg_replace('/^\+91/', '', htmlspecialchars($current_order['cust_no'])) : '' ?>"
                                           placeholder="10-digit mobile number">
                                </div>
                                <div class="form-text">Number will be automatically formatted with +91 prefix</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Order Type</label>
                                <select name="order_type" class="form-select" id="orderTypeSelect" required>
                                    <option value="tableorder" <?= ($edit_mode && $current_order['order_type'] == 'tableorder') ? 'selected' : '' ?>>Table Order</option>
                                    <option value="homeorder" <?= ($edit_mode && $current_order['order_type'] == 'homeorder') ? 'selected' : '' ?>>Home Delivery</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="tableNoField">
                                <label class="form-label">Table Number</label>
                                <input type="number" name="table_no" class="form-control" 
                                       value="<?= $edit_mode && $current_order['order_type'] == 'tableorder' ? $current_order['table_no'] : '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Order Cost (₹)</label>
                                <input type="number" step="0.01" name="ordercost" class="form-control" required 
                                       value="<?= $edit_mode ? $current_order['ordercost'] : '' ?>">
                            </div>
                        </div>
                        
                        <?php if ($edit_mode): ?>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Order Status</label>
                                    <select name="order_status" class="form-select" required>
                                        <option value="pending" <?= $current_order['order_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="preparing" <?= $current_order['order_status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                        <option value="served" <?= $current_order['order_status'] == 'served' ? 'selected' : '' ?>>Served</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="send_whatsapp" value="yes" id="sendWhatsAppCheck" checked>
                            <label class="form-check-label" for="sendWhatsAppCheck">
                                Send WhatsApp notification to customer
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary me-2">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title"><i class="bi bi-list-check"></i> Orders Management</h2>
                    <p class="text-muted">Manage all your restaurant orders in one place</p>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card filter-card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="search-container">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" class="form-control search-input" placeholder="Search by name, phone or order ID..." value="<?= $_GET['search'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="preparing" <?= isset($_GET['status']) && $_GET['status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                <option value="served" <?= isset($_GET['status']) && $_GET['status'] == 'served' ? 'selected' : '' ?>>Served</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="tableorder" <?= isset($_GET['type']) && $_GET['type'] == 'tableorder' ? 'selected' : '' ?>>Table Order</option>
                                <option value="homeorder" <?= isset($_GET['type']) && $_GET['type'] == 'homeorder' ? 'selected' : '' ?>>Home Order</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>" placeholder="From">
                        </div>
                        <div class="col-md-2">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (!empty($_GET['status']) || !empty($_GET['type']) || !empty($_GET['date_from']) || !empty($_GET['search'])): ?>
                        <div class="mt-3">
                            <div class="filter-tag">
                                Active Filters:
                            </div>
                            
                            <?php if (!empty($_GET['status'])): ?>
                                <div class="filter-tag">
                                    Status: <?= ucfirst($_GET['status']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['type'])): ?>
                                <div class="filter-tag">
                                    Type: <?= $_GET['type'] == 'tableorder' ? 'Table Order' : 'Home Delivery' ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['date_from'])): ?>
                                <div class="filter-tag">
                                    Date: <?= date('d M Y', strtotime($_GET['date_from'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['search'])): ?>
                                <div class="filter-tag">
                                    Search: "<?= htmlspecialchars($_GET['search']) ?>"
                                </div>
                            <?php endif; ?>
                            
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="filter-tag">
                                Clear All <i class="bi bi-x-circle"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Orders List -->
            <div class="row">
                <?php if (empty($orders)): ?>
                    <div class="col-12 empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3>No Orders Found</h3>
                        <p>Try adjusting your search or filter criteria</p>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-primary mt-2">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card order-card status-<?= $order['order_status'] ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-receipt"></i> Order #<?= $order['oid'] ?>
                                    </span>
                                    <span class="badge bg-<?= 
                                        $order['order_status'] == 'pending' ? 'warning' : 
                                        ($order['order_status'] == 'preparing' ? 'primary' : 'success') 
                                    ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title d-flex align-items-center">
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
                                            <?php endif; ?>
                                        </div>
                                        <div class="order-info-item">
                                            <i class="bi bi-clock"></i>
                                            <span><?= date('M j, Y g:i A', strtotime($order['order_at'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="order-cost">
                                        <i class="bi bi-currency-rupee"></i> Total: ₹<?= number_format($order['ordercost'], 2) ?>
                                    </div>
                                    
                                    <div class="action-buttons mt-3">
                                        <a href="<?= $_SERVER['PHP_SELF'] ?>?edit=<?= $order['oid'] ?>" class="btn btn-action btn-edit">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        
                                        <a href="<?= $_SERVER['PHP_SELF'] ?>?action=generate_bill&id=<?= $order['oid'] ?>" class="btn btn-action btn-bill">
                                            <i class="bi bi-receipt"></i> Bill
                                        </a>
                                        
                                        <a href="<?= $_SERVER['PHP_SELF'] ?>?action=whatsapp_bill&id=<?= $order['oid'] ?>" class="btn btn-action btn-whatsapp">
                                            <i class="bi bi-whatsapp"></i> Send Bill
                                        </a>
                                        
                                        <?php if ($order['order_status'] != 'preparing'): ?>
                                            <a href="<?= $_SERVER['PHP_SELF'] ?>?action=whatsapp_status&id=<?= $order['oid'] ?>&status=preparing" class="btn btn-action btn-status">
                                                <i class="bi bi-hourglass"></i> Preparing
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['order_status'] != 'served'): ?>
                                            <a href="<?= $_SERVER['PHP_SELF'] ?>?action=whatsapp_status&id=<?= $order['oid'] ?>&status=served" class="btn btn-action btn-status">
                                                <i class="bi bi-check-circle"></i> Served
                                            </a>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_order">
                                            <input type="hidden" name="oid" value="<?= $order['oid'] ?>">
                                            <button type="submit" class="btn btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this order?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;">
    <input type="hidden" name="action" value="complete_order">
    <input type="hidden" name="oid" value="<?= $order['oid'] ?>">
    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to complete this order?')">
        <i class="bi bi-check-circle-fill text-white"></i> Complete
    </button>
</form>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
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

    <footer class="footer mt-5">
        <div class="container text-center">
            <p>© <?= date('Y') ?> Hotel Aditya. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide table number field based on order type
        document.getElementById('orderTypeSelect')?.addEventListener('change', function() {
            const tableNoField = document.getElementById('tableNoField');
            if (this.value === 'tableorder') {
                tableNoField.style.display = 'block';
            } else {
                tableNoField.style.display = 'none';
            }
        });

        // Initialize the form visibility
        document.addEventListener('DOMContentLoaded', function() {
            const orderTypeSelect = document.getElementById('orderTypeSelect');
            if (orderTypeSelect) {
                const event = new Event('change');
                orderTypeSelect.dispatchEvent(event);
            }
            
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