<?php
// Database connection
include 'assets/config/dbconnect.php';

// Define prices for extras
define('CHAPATI_PRICE', 10);
define('ROTI_PRICE', 20);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_order'])) {
    // Get form data
    $cust_name = pg_escape_string($conn, $_POST['customerName']);
    $cust_no = pg_escape_string($conn, $_POST['customerPhone']);
    $order_type = pg_escape_string($conn, $_POST['orderType']);
    $table_no = ($order_type == 'tableorder') ? (int)$_POST['tableNumber'] : 'NULL';
    
    // Get cart items from JSON
    $cart_items = json_decode($_POST['cartItems'], true);
    
    if (!empty($cart_items)) {
        // Generate a unique order ID
        $oid_query = "SELECT MAX(oid) as max_oid FROM orders";
        $oid_result = pg_query($conn, $oid_query);
        
        if (!$oid_result) {
            $error_message = "Error generating order ID: " . pg_last_error($conn);
            error_log($error_message);
            header("Location: order-error.php?error=" . urlencode($error_message));
            exit;
        }
        
        $oid_row = pg_fetch_assoc($oid_result);
        $new_oid = ($oid_row['max_oid'] ? (int)$oid_row['max_oid'] + 1 : 1);
        
        // Insert each cart item as a separate order with the same OID
        $success = true;
        $order_items = [];
        $total_order_cost = 0;
        
        foreach ($cart_items as $item) {
            $mid = (int)$item['id'];
            $quantity = (int)$item['quantity'];
            $chapati = isset($item['chapati']) ? (int)$item['chapati'] : 0;
            $roti = isset($item['roti']) ? (int)$item['roti'] : 0;
            
            // Calculate item cost including extras
            $item_base_cost = $item['unitPrice'] * $quantity;
            $extras_cost = ($chapati * CHAPATI_PRICE) + ($roti * ROTI_PRICE);
            $item_total_cost = $item_base_cost + $extras_cost;
            $total_order_cost += $item_total_cost;
            
            // For table orders, ensure table_no is properly formatted
            $table_no_sql = ($order_type == 'tableorder') ? $table_no : 'NULL';
            
            $sql = "INSERT INTO orders (oid, cust_name, cust_no, table_no, mid, quantity, chapati, roti, order_type, order_status, ordercost) 
                    VALUES ($new_oid, '$cust_name', '$cust_no', $table_no_sql, $mid, $quantity, $chapati, $roti, '$order_type', 'pending', $item_total_cost)";
            
            error_log("Executing SQL: $sql");
            $result = pg_query($conn, $sql);
            
            if (!$result) {
                $success = false;
                $error_message = "Error inserting order: " . pg_last_error($conn);
                error_log($error_message);
                break;
            }
            
            // Store item details for receipt
            $order_items[] = [
                'id' => $mid,
                'name' => $item['name'],
                'size' => $item['size'],
                'quantity' => $quantity,
                'unitPrice' => $item['unitPrice'],
                'basePrice' => $item_base_cost,
                'chapati' => $chapati,
                'chapatiCost' => $chapati * CHAPATI_PRICE,
                'roti' => $roti,
                'rotiCost' => $roti * ROTI_PRICE,
                'totalPrice' => $item_total_cost
            ];
        }
        
        if ($success) {
            // Store order details in session for receipt page
            session_start();
            $_SESSION['order_details'] = [
                'oid' => $new_oid,
                'customer_name' => $cust_name,
                'customer_phone' => $cust_no,
                'order_type' => $order_type,
                'table_no' => ($order_type == 'tableorder') ? $table_no : null,
                'items' => $order_items,
                'total_cost' => $total_order_cost,
                'order_time' => date('Y-m-d H:i:s')
            ];
            
            // Redirect to receipt page
            header("Location: order-confirmation.php");
            exit;
        } else {
            // Redirect to error page
            header("Location: order-error.php?error=" . urlencode($error_message));
            exit;
        }
    } else {
        // Empty cart error
        header("Location: index.php?error=empty_cart");
        exit;
    }
} else {
    // Invalid request
    header("Location: index.php");
    exit;
}
?>