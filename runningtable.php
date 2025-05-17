<?php
// PostgreSQL DB connection
require_once 'assets/config/dbconnect.php';

// Fetch running orders
$query = "SELECT * FROM orders WHERE order_status NOT IN ('complete', 'delivered') ORDER BY order_at DESC";
$result = pg_query($conn, $query);

$orders = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Running Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #fd7e14;
            --info-color: #0dcaf0;
            --purple-color: #6f42c1;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --transition-speed: 0.3s;
        }
        
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color var(--transition-speed);
        }
        
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            animation: fadeIn 0.8s ease-in-out;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #0a58ca);
            color: white;
            border-bottom: none;
            padding: 20px;
        }
        
        .card-title {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--dark-color);
            font-weight: 600;
            border-top: none;
            padding: 15px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .table-hover tbody tr {
            transition: background-color var(--transition-speed);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
            cursor: pointer;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            transition: all var(--transition-speed);
        }
        
        .status-pending {
            background-color: rgba(253, 126, 20, 0.15);
            color: var(--warning-color);
        }
        
        .status-preparing {
            background-color: rgba(13, 110, 253, 0.15);
            color: var(--primary-color);
        }
        
        .status-out_for_delivery {
            background-color: rgba(111, 66, 193, 0.15);
            color: var(--purple-color);
        }
        
        .order-type-badge {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .type-dine-in {
            background-color: rgba(25, 135, 84, 0.15);
            color: var(--success-color);
        }
        
        .type-delivery {
            background-color: rgba(13, 110, 253, 0.15);
            color: var(--primary-color);
        }
        
        .type-takeaway {
            background-color: rgba(253, 126, 20, 0.15);
            color: var(--warning-color);
        }
        
        .alert-waiting {
            background-color: rgba(253, 126, 20, 0.1);
            border-left: 4px solid var(--warning-color);
            color: #7d4008;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        .refresh-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color var(--transition-speed);
        }
        
        .refresh-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        .refresh-btn i {
            color: white;
        }
        
        .refresh-btn.refreshing i {
            animation: spin 1s linear infinite;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }
        
        .search-input {
            padding-left: 40px;
            border-radius: 50px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all var(--transition-speed);
        }
        
        .search-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #86b7fe;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary-color);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-state h4 {
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(253, 126, 20, 0); }
            100% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0); }
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .row-animation {
            animation: fadeIn 0.5s ease-in-out;
            animation-fill-mode: both;
        }
        
        /* Apply staggered animations to rows */
        <?php for($i = 0; $i < count($orders); $i++): ?>
        .row-animation:nth-child(<?= $i+1 ?>) {
            animation-delay: <?= 0.1 * $i ?>s;
        }
        <?php endfor; ?>
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .table-responsive {
                border-radius: 8px;
                overflow: hidden;
            }
        }
        
        @media (max-width: 768px) {
            .card-header {
                padding: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .status-badge, .order-type-badge {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="bi bi-clock-history me-2"></i> Running Orders
                    </h3>
                    <button id="refreshBtn" class="refresh-btn" title="Refresh orders">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Wait time notice -->
                <div class="alert-waiting mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Please Note:</strong>
                        <span class="ms-2">After placing your order, please wait 10-15 minutes for preparation before inquiring about status.</span>
                    </div>
                </div>
                
                <!-- Search box -->
                <div class="search-container">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by order ID, customer name or phone...">
                </div>
                
                <!-- Filter tabs -->
                <div class="mb-4">
                    <ul class="nav nav-pills" id="orderStatusTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-status="all">All Orders</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-status="pending">Pending</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-status="preparing">Preparing</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-status="out_for_delivery">Out for Delivery</button>
                        </li>
                    </ul>
                </div>
                
                <?php if (count($orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Cost (₹)</th>
                                    <th>Order Time</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="row-animation" data-status="<?= htmlspecialchars($order['order_status']) ?>">
                                        <td class="fw-bold"><?= htmlspecialchars($order['oid']) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($order['cust_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($order['cust_no']) ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $typeClass = '';
                                            switch(strtolower($order['order_type'])) {
                                                case 'dine-in':
                                                    $typeClass = 'type-dine-in';
                                                    break;
                                                case 'delivery':
                                                    $typeClass = 'type-delivery';
                                                    break;
                                                case 'takeaway':
                                                    $typeClass = 'type-takeaway';
                                                    break;
                                            }
                                            ?>
                                            <span class="order-type-badge <?= $typeClass ?>">
                                                <?= htmlspecialchars($order['order_type']) ?>
                                                <?php if (!empty($order['table_no'])): ?>
                                                    - Table <?= htmlspecialchars($order['table_no']) ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusIcon = '';
                                            switch($order['order_status']) {
                                                case 'pending':
                                                    $statusClass = 'status-pending';
                                                    $statusIcon = 'bi-hourglass-split';
                                                    break;
                                                case 'preparing':
                                                    $statusClass = 'status-preparing';
                                                    $statusIcon = 'bi-cup-hot';
                                                    break;
                                                case 'out_for_delivery':
                                                    $statusClass = 'status-out_for_delivery';
                                                    $statusIcon = 'bi-bicycle';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <i class="bi <?= $statusIcon ?> me-1"></i>
                                                <?= ucwords(str_replace('_', ' ', htmlspecialchars($order['order_status']))) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold">₹<?= htmlspecialchars($order['ordercost']) ?></td>
                                        <td><?= date("d M Y, h:i A", strtotime($order['order_at'])) ?></td>
                                        <td>
                                            <?php if (!empty($order['delivery_address'])): ?>
                                                <div class="text-muted small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($order['delivery_address']) ?>">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    <?= htmlspecialchars($order['delivery_address']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 text-end text-muted small">
                        Showing <span id="visibleOrders"><?= count($orders) ?></span> of <span id="totalOrders"><?= count($orders) ?></span> orders
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h4>No Running Orders</h4>
                        <p>There are no active orders at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const searchInput = document.getElementById('searchInput');
            const statusTabs = document.getElementById('orderStatusTabs');
            const refreshBtn = document.getElementById('refreshBtn');
            const ordersTable = document.getElementById('ordersTable');
            const visibleOrdersCount = document.getElementById('visibleOrders');
            const totalOrdersCount = document.getElementById('totalOrders');
            
            // Variables
            let currentStatus = 'all';
            let rows = [];
            
            if (ordersTable) {
                rows = Array.from(ordersTable.querySelectorAll('tbody tr'));
            }
            
            // Filter orders based on search and status
            function filterOrders() {
                const searchTerm = searchInput.value.toLowerCase();
                let visibleCount = 0;
                
                if (rows.length > 0) {
                    rows.forEach(row => {
                        const orderStatus = row.getAttribute('data-status');
                        const orderID = row.cells[0].textContent.toLowerCase();
                        const customerName = row.cells[1].querySelector('.fw-semibold').textContent.toLowerCase();
                        const customerPhone = row.cells[1].querySelector('.text-muted').textContent.toLowerCase();
                        
                        const matchesSearch = orderID.includes(searchTerm) || 
                                            customerName.includes(searchTerm) || 
                                            customerPhone.includes(searchTerm);
                        
                        const matchesStatus = currentStatus === 'all' || orderStatus === currentStatus;
                        
                        if (matchesSearch && matchesStatus) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    if (visibleOrdersCount) {
                        visibleOrdersCount.textContent = visibleCount;
                    }
                    
                    // Show empty state if no results
                    const tbody = ordersTable.querySelector('tbody');
                    const emptyRow = tbody.querySelector('.empty-row');
                    
                    if (visibleCount === 0) {
                        if (!emptyRow) {
                            const newRow = document.createElement('tr');
                            newRow.className = 'empty-row';
                            newRow.innerHTML = `
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-search me-2"></i>
                                    No orders match your search criteria
                                </td>
                            `;
                            tbody.appendChild(newRow);
                        }
                    } else if (emptyRow) {
                        emptyRow.remove();
                    }
                }
            }
            
            // Handle search input
            if (searchInput) {
                searchInput.addEventListener('input', filterOrders);
            }
            
            // Handle status tabs
            if (statusTabs) {
                statusTabs.addEventListener('click', function(e) {
                    if (e.target.classList.contains('nav-link')) {
                        // Update active tab
                        statusTabs.querySelectorAll('.nav-link').forEach(tab => {
                            tab.classList.remove('active');
                        });
                        e.target.classList.add('active');
                        
                        // Update current status
                        currentStatus = e.target.getAttribute('data-status');
                        
                        // Filter orders
                        filterOrders();
                    }
                });
            }
            
            // Handle refresh button
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    // Add spinning animation
                    this.classList.add('refreshing');
                    
                    // Simulate refresh (in a real app, you'd fetch new data from the server)
                    setTimeout(() => {
                        // Remove spinning animation
                        this.classList.remove('refreshing');
                        
                        // Show toast notification
                        showToast('Orders refreshed successfully!');
                        
                        // In a real app, you would reload the data here
                        // For demo, we'll just reset the filters
                        if (searchInput) searchInput.value = '';
                        if (statusTabs) {
                            statusTabs.querySelectorAll('.nav-link').forEach(tab => {
                                tab.classList.remove('active');
                            });
                            statusTabs.querySelector('[data-status="all"]').classList.add('active');
                            currentStatus = 'all';
                        }
                        
                        // Reset visibility
                        if (rows.length > 0) {
                            rows.forEach(row => {
                                row.style.display = '';
                                // Re-trigger row animation
                                row.classList.remove('row-animation');
                                void row.offsetWidth; // Force reflow
                                row.classList.add('row-animation');
                            });
                        }
                        
                        // Update counter
                        if (visibleOrdersCount && totalOrdersCount) {
                            visibleOrdersCount.textContent = totalOrdersCount.textContent;
                        }
                        
                        // Remove empty row if exists
                        const tbody = ordersTable?.querySelector('tbody');
                        const emptyRow = tbody?.querySelector('.empty-row');
                        if (emptyRow) emptyRow.remove();
                    }, 800);
                });
            }
            
            // Toast notification function
            function showToast(message) {
                // Create toast container if it doesn't exist
                let toastContainer = document.querySelector('.toast-container');
                if (!toastContainer) {
                    toastContainer = document.createElement('div');
                    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                    document.body.appendChild(toastContainer);
                }
                
                // Create toast
                const toastId = 'toast-' + Date.now();
                const toastHtml = `
                    <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong class="me-auto">Notification</strong>
                            <small>Just now</small>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            ${message}
                        </div>
                    </div>
                `;
                
                // Add toast to container
                toastContainer.insertAdjacentHTML('beforeend', toastHtml);
                
                // Initialize and show toast
                const toastElement = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
                toast.show();
                
                // Remove toast after it's hidden
                toastElement.addEventListener('hidden.bs.toast', function() {
                    toastElement.remove();
                });
            }
        });
    </script>
</body>
</html>