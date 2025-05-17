<?php
require_once 'assets/config/dbconnect.php';
require_once 'active.php';

// Set default date range (current month)
$default_start_date = date('Y-m-01'); // First day of current month
$default_end_date = date('Y-m-d'); // Today

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start_date;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end_date;
$order_type = isset($_GET['order_type']) ? $_GET['order_type'] : '';
$order_status = isset($_GET['order_status']) ? $_GET['order_status'] : '';

// Build WHERE clause for filters
$where_clause = "WHERE order_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";

if (!empty($order_type)) {
    $order_type = pg_escape_string($conn, $order_type);
    $where_clause .= " AND order_type = '$order_type'";
}

if (!empty($order_status)) {
    $order_status = pg_escape_string($conn, $order_status);
    $where_clause .= " AND order_status = '$order_status'";
}

// Get summary statistics
// Total orders
$query = "SELECT COUNT(*) FROM orders $where_clause";
$result = pg_query($conn, $query);
$total_orders = pg_fetch_result($result, 0, 0);

// Total revenue
$query = "SELECT SUM(ordercost) FROM orders $where_clause";
$result = pg_query($conn, $query);
$total_revenue = pg_fetch_result($result, 0, 0) ?: 0;

// Average order value
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Order type breakdown
$query = "SELECT order_type, COUNT(*) as count FROM orders $where_clause GROUP BY order_type";
$result = pg_query($conn, $query);
$order_types = pg_fetch_all($result) ?: [];

$table_orders = 0;
$home_orders = 0;

foreach ($order_types as $type) {
    if ($type['order_type'] == 'tableorder') {
        $table_orders = $type['count'];
    } else if ($type['order_type'] == 'homeorder') {
        $home_orders = $type['count'];
    }
}

// Order status breakdown
$query = "SELECT order_status, COUNT(*) as count FROM orders $where_clause GROUP BY order_status";
$result = pg_query($conn, $query);
$order_statuses = pg_fetch_all($result) ?: [];

$status_counts = [
    'pending' => 0,
    'preparing' => 0,
    'served' => 0,
    'out_for_delivery' => 0,
    'delivered' => 0,
    'complete' => 0
];

foreach ($order_statuses as $status) {
    $status_counts[$status['order_status']] = $status['count'];
}

// Daily orders trend
$query = "SELECT DATE(order_at) as date, COUNT(*) as count, SUM(ordercost) as revenue 
          FROM orders 
          $where_clause 
          GROUP BY DATE(order_at) 
          ORDER BY date";
$result = pg_query($conn, $query);
$daily_orders = pg_fetch_all($result) ?: [];

// Format data for charts
$dates = [];
$order_counts = [];
$revenues = [];

foreach ($daily_orders as $day) {
    $dates[] = date('M d', strtotime($day['date']));
    $order_counts[] = $day['count'];
    $revenues[] = $day['revenue'];
}

// Top selling items
$query = "SELECT m.pname, SUM(oi.quantity) as total_quantity, SUM(oi.price) as total_revenue
          FROM order_items oi
          JOIN menu m ON oi.mid = m.mid
          JOIN orders o ON oi.oid = o.oid
          $where_clause
          GROUP BY m.pname
          ORDER BY total_quantity DESC
          LIMIT 10";
$result = pg_query($conn, $query);
$top_items = pg_fetch_all($result) ?: [];

// Get detailed orders for the report
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE oid = o.oid) as item_count
          FROM orders o 
          $where_clause 
          ORDER BY o.order_at DESC";
$result = pg_query($conn, $query);
$orders = pg_fetch_all($result) ?: [];

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return "bg-warning";
        case 'preparing':
            return "bg-primary";
        case 'served':
            return "bg-success";
        case 'out_for_delivery':
            return "bg-info";
        case 'delivered':
            return "bg-success";
        case 'complete':
            return "bg-success";
        default:
            return "bg-secondary";
    }
}

// Helper function to get status icon
function getStatusIcon($status) {
    switch ($status) {
        case 'pending':
            return "bi-hourglass";
        case 'preparing':
            return "bi-fire";
        case 'served':
            return "bi-check-circle";
        case 'out_for_delivery':
            return "bi-truck";
        case 'delivered':
            return "bi-house-check";
        case 'complete':
            return "bi-trophy";
        default:
            return "bi-question-circle";
    }
}

// Helper function to get formatted status label
function getStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return "Pending";
        case 'preparing':
            return "Preparing";
        case 'served':
            return "Served";
        case 'out_for_delivery':
            return "Out for Delivery";
        case 'delivered':
            return "Delivered";
        case 'complete':
            return "Completed";
        default:
            return ucfirst($status);
    }
}

// Handle export to CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="hotel_aditya_report_' . date('Y-m-d') . '.csv"');
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Output the column headings
    fputcsv($output, ['Order ID', 'Customer Name', 'Phone', 'Order Type', 'Table No', 'Order Status', 'Order Date', 'Order Cost']);
    
    // Output each row of data
    foreach ($orders as $order) {
        $row = [
            $order['oid'],
            $order['cust_name'],
            $order['cust_no'],
            $order['order_type'],
            $order['table_no'] ?: 'N/A',
            getStatusLabel($order['order_status']),
            date('Y-m-d H:i:s', strtotime($order['order_at'])),
            $order['ordercost']
        ];
        fputcsv($output, $row);
    }
    
    // Close the file pointer
    fclose($output);
    exit;
}

// Handle export to PDF
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    // Redirect back with a message that PDF export is not implemented
    // In a real application, you would use a library like TCPDF or FPDF to generate the PDF
    header("Location: report.php?start_date=$start_date&end_date=$end_date&order_type=$order_type&order_status=$order_status&message=pdf_not_implemented");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aditya - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --card-border-radius: 10px;
            --card-shadow: 0 8px 20px rgba(0,0,0,0.08);
            --transition-speed: 0.3s;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: #333;
            line-height: 1.6;
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
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 6px;
            font-size: 0.75rem;
            letter-spacing: 0.3px;
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
            background-color: var(--info-color);
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
        
        .filter-card {
            background: white;
            border-radius: var(--card-border-radius);
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
        }
        
        .filter-card .card-body {
            padding: 20px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .table-responsive {
            border-radius: var(--card-border-radius);
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .table td, .table th {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(78, 205, 196, 0.05);
        }
        
        .footer {
            background: linear-gradient(90deg, #1a0d0d, #050d0f, #000000);
            color: white;
            padding: 20px 0;
            margin-top: 50px;
        }
        
        .footer p {
            margin-bottom: 0;
        }
        
        .export-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .top-item:last-child {
            border-bottom: none;
        }
        
        .top-item-name {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .top-item-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-badge i {
            font-size: 0.9rem;
        }
        
        .date-range-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 20px;
            }
            
            .navbar-brand {
                font-size: 1.3rem;
            }
            
            .section-title {
                font-size: 1.3rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card, .stats-card, .filter-card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
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
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-list-check"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="report.php">
                            <i class="bi bi-graph-up"></i> Reports
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
        <?php if (isset($_GET['message']) && $_GET['message'] == 'pdf_not_implemented'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> PDF export functionality is not implemented in this version.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-md-6">
                <h2 class="section-title"><i class="bi bi-graph-up"></i> Sales Reports</h2>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group">
                    <a href="<?= $_SERVER['PHP_SELF'] ?>?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&order_type=<?= $order_type ?>&order_status=<?= $order_status ?>&export=csv" class="btn btn-success export-btn">
                        <i class="bi bi-file-earmark-excel"></i> Export to CSV
                    </a>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&order_type=<?= $order_type ?>&order_status=<?= $order_status ?>&export=pdf" class="btn btn-danger export-btn">
                        <i class="bi bi-file-earmark-pdf"></i> Export to PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card filter-card mb-4">
            <div class="card-body">
                <form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>" class="row g-3">
                    <div class="col-md-3">
                        <label class="date-range-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="date-range-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="date-range-label">Order Type</label>
                        <select name="order_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="tableorder" <?= $order_type == 'tableorder' ? 'selected' : '' ?>>Table Order</option>
                            <option value="homeorder" <?= $order_type == 'homeorder' ? 'selected' : '' ?>>Home Order</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="date-range-label">Order Status</label>
                        <select name="order_status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $order_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="preparing" <?= $order_status == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="served" <?= $order_status == 'served' ? 'selected' : '' ?>>Served</option>
                            <option value="out_for_delivery" <?= $order_status == 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                            <option value="delivered" <?= $order_status == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="complete" <?= $order_status == 'complete' ? 'selected' : '' ?>>Complete</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                    </div>
                </form>
                
                <div class="mt-3">
                    <strong>Report Period:</strong> <?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?>
                    <?php if (!empty($order_type) || !empty($order_status)): ?>
                        <span class="ms-3">
                            <?php if (!empty($order_type)): ?>
                                <span class="badge bg-info">
                                    Type: <?= $order_type == 'tableorder' ? 'Table Order' : 'Home Order' ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($order_status)): ?>
                                <span class="badge <?= getStatusBadgeClass($order_status) ?>">
                                    Status: <?= getStatusLabel($order_status) ?>
                                </span>
                            <?php endif; ?>
                            
                            <a href="<?= $_SERVER['PHP_SELF'] ?>?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="badge bg-secondary text-decoration-none">
                                <i class="bi bi-x-circle"></i> Clear Filters
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stats-value"><?= $total_orders ?></div>
                    <div class="stats-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                    <div class="stats-value">₹<?= number_format($total_revenue, 2) ?></div>
                    <div class="stats-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stats-value">₹<?= number_format($avg_order_value, 2) ?></div>
                    <div class="stats-label">Average Order Value</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-dark">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stats-value"><?= count($daily_orders) ?></div>
                    <div class="stats-label">Days with Orders</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-graph-up"></i> Daily Orders & Revenue</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dailyOrdersChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-pie-chart"></i> Order Type Distribution
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="orderTypeChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="bi bi-cup-hot text-primary"></i> Table Orders</span>
                                <strong><?= $table_orders ?> (<?= $total_orders > 0 ? round(($table_orders / $total_orders) * 100) : 0 ?>%)</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-house text-success"></i> Home Orders</span>
                                <strong><?= $home_orders ?> (<?= $total_orders > 0 ? round(($home_orders / $total_orders) * 100) : 0 ?>%)</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Status and Top Items -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-list-check"></i> Order Status Breakdown
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>
                                    <i class="bi bi-hourglass text-warning"></i> Pending
                                </span>
                                <span><?= $status_counts['pending'] ?> orders</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: <?= $total_orders > 0 ? ($status_counts['pending'] / $total_orders) * 100 : 0 ?>%" 
                                     aria-valuenow="<?= $status_counts['pending'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_orders ?>"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>
                                    <i class="bi bi-fire text-primary"></i> Preparing
                                </span>
                                <span><?= $status_counts['preparing'] ?> orders</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: <?= $total_orders > 0 ? ($status_counts['preparing'] / $total_orders) * 100 : 0 ?>%" 
                                     aria-valuenow="<?= $status_counts['preparing'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_orders ?>"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>
                                    <i class="bi bi-check-circle text-success"></i> Served
                                </span>
                                <span><?= $status_counts['served'] ?> orders</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?= $total_orders > 0 ? ($status_counts['served'] / $total_orders) * 100 : 0 ?>%" 
                                     aria-valuenow="<?= $status_counts['served'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_orders ?>"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>
                                    <i class="bi bi-truck text-info"></i> Out for Delivery
                                </span>
                                <span><?= $status_counts['out_for_delivery'] ?> orders</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: <?= $total_orders > 0 ? ($status_counts['out_for_delivery'] / $total_orders) * 100 : 0 ?>%" 
                                     aria-valuenow="<?= $status_counts['out_for_delivery'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_orders ?>"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>
                                    <i class="bi bi-house-check text-success"></i> Delivered
                                </span>
                                <span><?= $status_counts['delivered'] ?> orders</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?= $total_orders > 0 ? ($status_counts['delivered'] / $total_orders) * 100 : 0 ?>%" 
                                     aria-valuenow="<?= $status_counts['delivered'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_orders ?>"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>
                                    <i class="bi bi-trophy text-dark"></i> Complete
                                </span>
                                <span><?= $status_counts['complete'] ?> orders</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-dark" role="progressbar" 
                                     style="width: <?= $total_orders > 0 ? ($status_counts['complete'] / $total_orders) * 100 : 0 ?>%" 
                                     aria-valuenow="<?= $status_counts['complete'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_orders ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-star"></i> Top Selling Items
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_items)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-exclamation-circle text-muted" style="font-size: 2rem;"></i>
                                <p class="mt-2 text-muted">No item data available for the selected period.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_items as $item): ?>
                                <div class="top-item">
                                    <div>
                                        <div class="top-item-name"><?= htmlspecialchars($item['pname']) ?></div>
                                        <small class="text-muted">Quantity: <?= $item['total_quantity'] ?></small>
                                    </div>
                                    <div class="top-item-value">₹<?= number_format($item['total_revenue'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Orders Table -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-table"></i> Detailed Order Report</span>
                <span class="badge bg-primary"><?= count($orders) ?> Orders</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Items</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                        <p class="mt-2 text-muted">No orders found for the selected period.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['oid'] ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($order['cust_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($order['cust_no']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($order['order_type'] == 'tableorder'): ?>
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-cup-hot"></i> Table <?= $order['table_no'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-house"></i> Home Delivery
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $order['item_count'] ?> items</span>
                                        </td>
                                        <td>
                                            <div><?= date('d M Y', strtotime($order['order_at'])) ?></div>
                                            <small class="text-muted"><?= date('h:i A', strtotime($order['order_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= getStatusBadgeClass($order['order_status']) ?>">
                                                <i class="bi <?= getStatusIcon($order['order_status']) ?>"></i>
                                                <?= getStatusLabel($order['order_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>₹<?= number_format($order['ordercost'], 2) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer mt-5">
        <div class="container text-center">
            <p>© <?= date('Y') ?> Hotel Aditya. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Daily Orders Chart
        const dailyOrdersCtx = document.getElementById('dailyOrdersChart').getContext('2d');
        const dailyOrdersChart = new Chart(dailyOrdersCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [
                    {
                        label: 'Orders',
                        data: <?= json_encode($order_counts) ?>,
                        backgroundColor: 'rgba(230, 57, 70, 0.7)',
                        borderColor: 'rgba(230, 57, 70, 1)',
                        borderWidth: 1,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Revenue (₹)',
                        data: <?= json_encode($revenues) ?>,
                        type: 'line',
                        fill: false,
                        backgroundColor: 'rgba(42, 157, 143, 0.7)',
                        borderColor: 'rgba(42, 157, 143, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(42, 157, 143, 1)',
                        pointRadius: 4,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Orders'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label === 'Revenue (₹)') {
                                    label += '₹' + context.raw.toFixed(2);
                                } else {
                                    label += context.raw;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Order Type Chart
        const orderTypeCtx = document.getElementById('orderTypeChart').getContext('2d');
        const orderTypeChart = new Chart(orderTypeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Table Orders', 'Home Orders'],
                datasets: [{
                    data: [<?= $table_orders ?>, <?= $home_orders ?>],
                    backgroundColor: [
                        'rgba(230, 57, 70, 0.7)',
                        'rgba(42, 157, 143, 0.7)'
                    ],
                    borderColor: [
                        'rgba(230, 57, 70, 1)',
                        'rgba(42, 157, 143, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>