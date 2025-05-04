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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #d4af37;
            --secondary-color: #1a1a1a;
            --accent-color: #8b0000;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            padding-top: 70px;
            padding-bottom: 70px;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #333 100%);
            color: white;
            padding: 15px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .admin-title {
            font-family:Tahoma;
            font-weight:bold;
            font-size: 1.8rem;
        }

        .admin-title .gold {
            color: var(--primary-color);
        }

        .main-content {
            padding: 30px;
        }

        .menu-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .menu-table th {
            background: var(--secondary-color);
            color: white;
            padding: 15px;
        }

        .menu-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .btn-back {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
        }

        .footer {
            background: var(--secondary-color);
            color: white;
            padding: 15px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        @media (max-width: 768px) {
            .menu-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .admin-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="container">
        <h1 class="admin-title"><span class="gold">Hotel Aditya</span> - Menu Management</h1>
    </div>
</header>

<div class="container main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-utensils me-2"></i>Current Menu Items</h2>
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
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
            echo '<div class="table-responsive">';
            echo '<table class="menu-table table">';
            echo '<thead><tr><th>Image</th><th>Name</th><th>Description</th><th>Size & Price</th><th>Action</th></tr></thead><tbody>';

            while ($item = pg_fetch_assoc($res)) {
                $sizeQuery = "SELECT size, price FROM size_price WHERE mid = " . $item['mid'];
                $sizeRes = pg_query($conn, $sizeQuery);

                echo '<tr>';
                echo '<td><img src="' . $item['img'] . '" alt="' . $item['pname'] . '" class="item-img"></td>';
                echo '<td>' . $item['pname'] . '</td>';
                echo '<td>' . $item['description'] . '</td>';
                echo '<td>';

                if ($sizeRes) {
                    while ($size = pg_fetch_assoc($sizeRes)) {
                        echo '<span class="badge bg-light text-dark me-2">';
                        echo ucfirst($size['size']) . ': ₹' . $size['price'];
                        echo '</span>';
                    }
                } else {
                    echo 'N/A';
                }

                echo '</td>';
                echo '<td>';
                echo '<a href="?del=' . $item['mid'] . '" class="btn-delete" onclick="return confirm(\'Are you sure you want to delete this item?\')">';
                echo '<i class="fas fa-trash-alt me-1"></i> Delete</a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table></div>';
        } else {
            echo '<div class="alert alert-info">No menu items found.</div>';
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
                    timer: 1500
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
                    showConfirmButton: true
                });
            </script>";
        }
    }

    showMenu();
    ?>
</div>

<footer class="footer">
    <div class="container">
        <p class="mb-0" style="text-align:center;">&copy; <?php echo date('Y'); ?> Hotel Aditya. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
