<?php
require_once '../assets/config/dbconnect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if order ID is provided
if (isset($_GET['oid']) && !empty($_GET['oid'])) {
    $order_id = (int)$_GET['oid'];
    
    // Fetch order details
    $query = "SELECT * FROM orders WHERE oid = $order_id";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) > 0) {
        $order = pg_fetch_assoc($result);
        
        // Fetch order items
        $items_query = "SELECT oi.*, m.pname as menu_name 
                      FROM order_items oi 
                      JOIN menu m ON oi.mid = m.mid 
                      WHERE oi.oid = $order_id";
        $items_result = pg_query($conn, $items_query);
        $order_items = pg_fetch_all($items_result) ?: [];
        
        // Return order data
        echo json_encode([
            'status' => 'success',
            'order' => $order,
            'items' => $order_items
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Order not found'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Order ID is required'
    ]);
}
