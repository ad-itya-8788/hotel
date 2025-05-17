<?php
include 'assets/config/dbconnect.php';
include 'active.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Hotel Aditya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #d4af37;
            --primary-dark: #b38f2a;
            --secondary-color: #1a1a1a;
            --accent-color: #8b0000;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            padding-top: 70px;
            padding-bottom: 70px;
            color: #333;
        }

      
        .main-content {
            padding: 30px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .page-title {
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-right: 12px;
            color: var(--primary-color);
        }

        .btn-back {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-back:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: var(--secondary-color);
        }

        .btn-back i {
            margin-right: 8px;
        }

        .menu-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            border-collapse: separate;
        }

        .menu-table th {
            background: var(--secondary-color);
            color: white;
            padding: 15px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .menu-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
        }

        .menu-table tr:last-child td {
            border-bottom: none;
        }

        .menu-table tr:hover td {
            background-color: rgba(212, 175, 55, 0.05);
        }

        .item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .item-img:hover {
            transform: scale(1.05);
        }

        .item-name {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .item-desc {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .price-badge {
            display: inline-block;
            padding: 6px 10px;
            background-color: rgba(212, 175, 55, 0.1);
            color: var(--secondary-color);
            border-radius: 20px;
            margin-right: 8px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .price-badge .size {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .price-badge .price {
            font-weight: 600;
            color: var(--accent-color);
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .btn-action i {
            margin-right: 5px;
        }

    
        .btn-delete {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .btn-delete:hover {
            background-color: rgba(220, 53, 69, 0.2);
            color: #b02a37;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #999;
            max-width: 500px;
            margin: 0 auto 25px;
        }

        .btn-add {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-add:hover {
            background-color: var(--primary-dark);
            color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .footer {
            background: var(--secondary-color);
            color: white;
            padding: 15px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .menu-table {
                display: block;
                overflow-x: auto;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .action-btns {
                flex-direction: column;
                gap: 8px;
            }
            
            .btn-action {
                justify-content: center;
                width: 100%;
            }
        }
    

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 10px 20px;
            background: linear-gradient(90deg, rgb(26, 13, 13), rgb(5, 13, 15), rgb(0, 0, 0));
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid green;
            z-index: 999;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            white-space: nowrap;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo i {
            margin-right: 10px;
            font-size: 28px;
            color: var(--primary);
            animation: pulse 2s infinite;
        }

        .nav-item {
            list-style: none;
            margin: 0;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: background 0.3s ease;
            padding: 8px 12px;
            border-radius: 4px;
        }

        .nav-link:hover {
            background: red;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
 </style>
</head>
<body>

<nav class="navbar">
        <a href="login.html" class="logo">
            <i class="fas fa-utensils" style="color:red;"></i>
            Hotel Aditya
        </a>
        <ul style="margin: 0; padding: 0;">
            <li class="nav-item">
                <a class="nav-link" href="admin.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Go Back
                </a>
            </li>
        </ul>
    </nav>





<div class="container main-content">
    <div class="page-header">
        <h2 class="page-title"><i class="fas fa-utensils"></i>Current Menu Items</h2>
        <div class="d-flex gap-3">
            
        </div>
    </div>

    <?php
    function showMenu() {
        global $conn;
        $query = "SELECT mid, pname, description, img FROM menu ORDER BY mid";
        $res = pg_query($conn, $query);

        if (!$res) {
            echo '<div class="alert alert-danger">Error fetching menu data.</div>';
            return;
        }

        if (pg_num_rows($res) > 0) {
            echo '<div class="table-responsive rounded-3">';
            echo '<table class="menu-table table">';
            echo '<thead><tr><th>Image</th><th>Item Details</th><th>Size & Price</th><th>Actions</th></tr></thead><tbody>';

            while ($item = pg_fetch_assoc($res)) {
                $sizeQuery = "SELECT size, price FROM size_price WHERE mid = " . $item['mid'];
                $sizeRes = pg_query($conn, $sizeQuery);

                echo '<tr>';
                echo '<td><img src="' . $item['img'] . '" alt="' . $item['pname'] . '" class="item-img"></td>';
                echo '<td>';
                echo '<div class="item-name">' . $item['pname'] . '</div>';
                echo '<div class="item-desc">' . $item['description'] . '</div>';
                echo '</td>';
                echo '<td>';

                if ($sizeRes) {
                    while ($size = pg_fetch_assoc($sizeRes)) {
                        echo '<div class="price-badge">';
                        echo '<span class="size">' . ucfirst($size['size']) . '</span>: ';
                        echo '<span class="price">₹' . number_format($size['price'], 2) . '</span>';
                        echo '</div>';
                    }
                } else {
                    echo '<span class="text-muted">No pricing set</span>';
                }

                echo '</td>';
                echo '<td>';
       
                echo '<a href="?del=' . $item['mid'] . '" class="btn-action btn-delete" onclick="return confirmDelete(' . $item['mid'] . ')">';
                echo '<i class="fas fa-trash-alt"></i> Delete</a>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table></div>';
        } else {
            echo '<div class="empty-state">';
            echo '<i class="fas fa-utensils"></i>';
            echo '<h3>No Menu Items Found</h3>';
            echo '<p>Your menu is currently empty. Add your first item to get started.</p>';
            echo '<a href="add_menu_item.php" class="btn-add">';
            echo '<i class="fas fa-plus me-2"></i> Add Menu Item</a>';
            echo '</div>';
        }
    }

    // Handle deletion
    if (isset($_GET['del'])) {
        $mid = (int)$_GET['del'];
        pg_query($conn, "BEGIN");
        $delSizePrice = pg_query($conn, "DELETE FROM size_price WHERE mid = $mid");
        $delMenu = pg_query($conn, "DELETE FROM menu WHERE mid = $mid");

        if ($delSizePrice && $delMenu) {
            pg_query($conn, "COMMIT");
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Menu item deleted successfully.',
                    showConfirmButton: false,
                    timer: 1500,
                    background: '#f8f9fa',
                    backdrop: `
                        rgba(0,0,0,0.4)
                        url('/images/trash.gif')
                        left top
                        no-repeat
                    `
                }).then(() => {
                    window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                });
            </script>";
        } else {
            pg_query($conn, "ROLLBACK");
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to delete menu item.',
                    showConfirmButton: true,
                    background: '#f8f9fa'
                });
            </script>";
        }
    }

    showMenu();
    ?>
</div>

<footer class="footer">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> Hotel Aditya. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d4af37',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            background: '#f8f9fa'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?del=' + id;
            }
        });
        return false;
    }
</script>
</body>
</html>