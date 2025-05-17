<?php
include "assets/config/dbconnect.php";
// Define prices for extras
define('CHAPATI_PRICE', 10);
define('ROTI_PRICE', 20);

// Function to check if a table is occupied
function isTableOccupied($conn, $tableNumber) {
    $sql = "SELECT COUNT(*) as count FROM orders 
            WHERE table_no = $tableNumber 
            AND order_status IN ('pending', 'preparing', 'served')";
    $result = pg_query($conn, $sql);
    if (!$result) {
        error_log("PostgreSQL Error in isTableOccupied: " . pg_last_error($conn));
        return false; // Error in query
    }
    $row = pg_fetch_assoc($result);
    return $row['count'] > 0;
}

// Function to get table status
function getTableStatus($conn) {
    $tableStatus = [];
    for ($i = 1; $i <= 10; $i++) {
        $tableStatus[$i] = isTableOccupied($conn, $i);
    }
    return $tableStatus;
}

// Variable to store WhatsApp link for redirection
$whatsapp_redirect = '';

// Process order submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_order'])) {
    // Get form data
    $cust_name = pg_escape_string($conn, $_POST['customerName']);
    $cust_no = pg_escape_string($conn, $_POST['customerPhone']);
    $order_type = pg_escape_string($conn, $_POST['orderType']);
    $delivery_address = isset($_POST['customerAddress']) ? pg_escape_string($conn, $_POST['customerAddress']) : '';
    $whatsapp_link = isset($_POST['whatsappLink']) ? $_POST['whatsappLink'] : '';
    
    // Debug information
    error_log("Order submission received: " . json_encode($_POST));
    
    // Get cart items from JSON
    $cart_items = json_decode($_POST['cartItems'], true);
    
    if (!empty($cart_items)) {
        // Begin a transaction to ensure all items are inserted or none
        pg_query($conn, "BEGIN");
        
        try {
            // Calculate total order cost
            $total_order_cost = 0;
            foreach ($cart_items as $item) {
                $total_order_cost += $item['totalPrice'];
            }
            
            // Prepare base SQL query for the main order
            $order_sql = "INSERT INTO orders (cust_name, cust_no, order_type, order_status, ordercost";
            
            // Add table_no to column list only if it's a table order
            if ($order_type == 'tableorder' && isset($_POST['tableNumber']) && !empty($_POST['tableNumber'])) {
                $order_sql .= ", table_no";
            }
            
            // Add delivery_address to column list if it's a home order
            if ($order_type == 'homeorder' && !empty($delivery_address)) {
                $order_sql .= ", delivery_address";
            }
            
            $order_sql .= ") VALUES ('$cust_name', '$cust_no', '$order_type', 'pending', $total_order_cost";
            
            // Add table_no to values only if it's a table order
            if ($order_type == 'tableorder' && isset($_POST['tableNumber']) && !empty($_POST['tableNumber'])) {
                $table_no = (int)$_POST['tableNumber'];
                $order_sql .= ", $table_no";
            }
            
            // Add delivery_address to values if it's a home order
            if ($order_type == 'homeorder' && !empty($delivery_address)) {
                $order_sql .= ", '$delivery_address'";
            }
            
            $order_sql .= ") RETURNING oid";
            
            error_log("Executing SQL: $order_sql");
            $order_result = pg_query($conn, $order_sql);
            
            if (!$order_result) {
                throw new Exception("Error creating order: " . pg_last_error($conn));
            }
            
            // Get the new order ID
            $order_row = pg_fetch_assoc($order_result);
            $order_id = $order_row['oid'];
            
            // Insert each item into order_items table
            foreach ($cart_items as $item) {
                $mid = (int)$item['id'];
                $quantity = (int)$item['quantity'];
                $chapati = isset($item['chapati']) ? (int)$item['chapati'] : 0;
                $roti = isset($item['roti']) ? (int)$item['roti'] : 0;
                $price = (float)$item['totalPrice'];
                
                $item_sql = "INSERT INTO order_items (oid, mid, quantity, chapati, roti, price) 
                             VALUES ($order_id, $mid, $quantity, $chapati, $roti, $price)";
                
                error_log("Executing SQL: $item_sql");
                $item_result = pg_query($conn, $item_sql);
                
                if (!$item_result) {
                    throw new Exception("Error inserting order item: " . pg_last_error($conn));
                }
            }
            
            // Commit the transaction
            pg_query($conn, "COMMIT");
            
            // If it's a WhatsApp order and we have a WhatsApp link, set it for redirection
            if ($order_type == 'homeorder' && !empty($whatsapp_link)) {
                $whatsapp_redirect = $whatsapp_link;
                echo "<script>
                    localStorage.removeItem('hotelAdityaCart');
                    window.location.href = '$whatsapp_redirect';
                </script>";
            } else {
                echo "<script>
                    Swal.fire({
                        title: 'Order Placed!',
                        text: 'Thank you from Hotel Aditya! Your order has been placed successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        localStorage.removeItem('hotelAdityaCart');
                        window.location.href='index.php';
                    });
                </script>";
            }
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            pg_query($conn, "ROLLBACK");
            error_log("Order error: " . $e->getMessage());
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'Error placing order. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                title: 'Empty Cart',
                text: 'Your cart is empty. Please add items to your order.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

// Rest of your code remains the same...

function displayMenuItems($conn, $searchQuery = "") 
{
    if (!empty($searchQuery)) {
        $sql = "SELECT m.mid, m.pname, m.description, m.img, sp.size, sp.price, sp.quantity 
                FROM menu m
                LEFT JOIN size_price sp ON m.mid = sp.mid
                WHERE m.pname ILIKE '%" . pg_escape_string($conn, $searchQuery) . "%'
                ORDER BY m.pname";
    } else {
        $sql = "SELECT m.mid, m.pname, m.description, m.img, sp.size, sp.price, sp.quantity 
                FROM menu m
                LEFT JOIN size_price sp ON m.mid = sp.mid
                ORDER BY m.pname";
    }

    $result = pg_query($conn, $sql);

    if (!$result) {
        error_log("PostgreSQL Error in displayMenuItems: " . pg_last_error($conn));
        return "<div class='no-results'>Error fetching menu data.</div>";
    }

    if (pg_num_rows($result) == 0) {
        return "<div class='no-results'>No menu items found matching your search.</div>";
    }

    $menuItems = [];
    while ($row = pg_fetch_assoc($result)) {
        $mid = $row['mid'];
        if (!isset($menuItems[$mid])) {
            $menuItems[$mid] = [
                'mid' => $mid,
                'pname' => $row['pname'],
                'description' => $row['description'],
                'img' => $row['img'],
                'sizes' => []
            ];
        }
        
        if (!empty($row['size'])) {
            $menuItems[$mid]['sizes'][$row['size']] = [
                'price' => $row['price'],
                'quantity' => $row['quantity']
            ];
        }
    }

    $output = '';
    foreach ($menuItems as $item) {
        $fullPrice = isset($item['sizes']['Full']) ? '₹' . $item['sizes']['Full']['price'] : 'N/A';
        $fullQuantity = isset($item['sizes']['Full']) ? $item['sizes']['Full']['quantity'] : 'N/A';
        $halfPrice = isset($item['sizes']['Half']) ? '₹' . $item['sizes']['Half']['price'] : 'N/A';
        $halfQuantity = isset($item['sizes']['Half']) ? $item['sizes']['Half']['quantity'] : 'N/A';

        $output .= '
        <div class="menu-item" data-item-id="' . $item['mid'] . '" data-item-name="' . htmlspecialchars($item['pname']) . '" data-full-price="' . $fullPrice . '" data-half-price="' . $halfPrice . '">
            <div class="menu-card">
                <div class="menu-image">
                    <img src="' . htmlspecialchars($item['img']) . '" alt="' . htmlspecialchars($item['pname']) . '">
                    <div class="menu-rating">
                        <span>4.2 ★</span>
                    </div>
                </div>
                <div class="menu-details">
                    <h3>' . htmlspecialchars($item['pname']) . '</h3>
                    <p class="menu-desc">' . htmlspecialchars($item['description']) . '</p>
                    <div class="menu-pricing">
                        <div class="size-option">
                            <div class="size-label">Full</div>
                            <div class="price-tag">' . $fullPrice . '</div>
                            <div class="quantity-tag">Qty: ' . $fullQuantity . '</div>
                        </div>
                        <div class="size-option">
                            <div class="size-label">Half</div>
                            <div class="price-tag">' . $halfPrice . '</div>
                            <div class="quantity-tag">Qty: ' . $halfQuantity . '</div>
                        </div>
                    </div>
                    <div class="menu-actions">
                        <button class="order-btn add-to-cart-btn" onclick="addToCart(this)">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>';
    }

    return '<div class="menu-grid">' . $output . '</div>';
}

// Get table status
$tableStatus = getTableStatus($conn);

// Get search query if any
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
?> 