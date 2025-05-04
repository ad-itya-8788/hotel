<?php
// Start session to access order details
session_start();

// Check if order details exist
if (!isset($_SESSION['order_details'])) {
    header("Location: index.php");
    exit;
}

// Get order details
$order = $_SESSION['order_details'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Hotel Aditya</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ddd;
        }
        
        .receipt-logo {
            font-size: 24px;
            font-weight: 700;
            color: #4a6fa5;
            margin-bottom: 5px;
        }
        
        .receipt-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .receipt-subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ddd;
        }
        
        .receipt-info-group {
            flex: 1;
        }
        
        .receipt-info-item {
            margin-bottom: 8px;
        }
        
        .receipt-info-label {
            font-weight: 500;
            color: #666;
            margin-right: 5px;
        }
        
        .receipt-info-value {
            font-weight: 600;
            color: #333;
        }
        
        .receipt-items {
            margin-bottom: 20px;
        }
        
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .receipt-table th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }
        
        .receipt-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }
        
        .receipt-table .item-name {
            font-weight: 500;
        }
        
        .receipt-table .item-details {
            font-size: 12px;
            color: #666;
        }
        
        .receipt-table .text-right {
            text-align: right;
        }
        
        .receipt-total {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }
        
        .receipt-total-table {
            width: 300px;
        }
        
        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .receipt-total-label {
            font-weight: 500;
            color: #666;
        }
        
        .receipt-total-value {
            font-weight: 600;
            color: #333;
        }
        
        .receipt-grand-total {
            font-size: 18px;
            font-weight: 700;
            color: #4a6fa5;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid #ddd;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
        }
        
        .receipt-footer-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .receipt-actions {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .receipt-btn {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-print {
            background-color: #4a6fa5;
            color: white;
        }
        
        .btn-print:hover {
            background-color: #3a5a8a;
        }
        
        .btn-home {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .btn-home:hover {
            background-color: #e5e5e5;
        }
        
        @media print {
            .receipt-actions {
                display: none;
            }
            
            body {
                padding: 0;
                margin: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                margin: 0;
                padding: 15px;
            }
            
            .header, .footer {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .receipt-info {
                flex-direction: column;
            }
            
            .receipt-info-group {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Header with Logo -->
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-utensils"></i>
                Hotel Aditya
            </a>
        </div>
    </header>

    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-logo"><i class="fas fa-utensils"></i> Hotel Aditya</div>
            <div class="receipt-title">Order Receipt</div>
            <div class="receipt-subtitle">Thank you for your order!</div>
        </div>
        
        <div class="receipt-info">
            <div class="receipt-info-group">
                <div class="receipt-info-item">
                    <span class="receipt-info-label">Order ID:</span>
                    <span class="receipt-info-value">#<?php echo $order['oid']; ?