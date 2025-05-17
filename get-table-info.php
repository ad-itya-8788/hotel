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