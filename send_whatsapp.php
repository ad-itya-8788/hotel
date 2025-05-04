<?php
require_once 'assets/config/dbconnect.php';

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];
$status = $_GET['status'];

// Get order details
$query = "SELECT * FROM orders WHERE oid = $id";
$result = pg_query($conn, $query);
$order = pg_fetch_assoc($result);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Update order status
$updateQuery = "UPDATE orders SET order_status = '$status' WHERE oid = $id";
pg_query($conn, $updateQuery);

// Prepare WhatsApp message
$phone = $order['cust_no'];
$message = urlencode("Hello " . $order['cust_name'] . ",\n\n");

switch ($status) {
    case 'preparing':
        $message .= urlencode("Your order #" . $order['oid'] . " is now being prepared.\n");
        $message .= urlencode("Estimated time: 20-30 minutes.\n\n");
        $message .= urlencode("Thank you for your patience!");
        break;
    case 'served':
        $message .= urlencode("Your order #" . $order['oid'] . " has been served.\n");
        if ($order['order_type'] == 'tableorder') {
            $message .= urlencode("Please enjoy your meal at Table " . $order['table_no'] . ".\n\n");
        } else {
            $message .= urlencode("Please enjoy your meal at home.\n\n");
        }
        $message .= urlencode("Thank you for dining with us!");
        break;
}

// Create WhatsApp link
$whatsappLink = "https://wa.me/$phone?text=$message";

// Redirect to WhatsApp
header("Location: $whatsappLink");
exit;
?>