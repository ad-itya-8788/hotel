<?php
include 'assets/public/menu.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aditya - Menu</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        /* Cart Styles */
        .cart-icon {
            position: relative;
            cursor: pointer;
            margin-left: 20px;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ff6b6b;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .cart-preview {
            position: fixed;
            top: 70px;
            right: 20px;
            width: 300px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 100;
            display: none;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .cart-preview.show {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .cart-header {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-title {
            font-weight: 600;
            font-size: 16px;
            margin: 0;
        }
        
        .cart-items {
            padding: 10px 15px;
            max-height: 250px;
            overflow-y: auto;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .cart-item-details {
            font-size: 12px;
            color: #666;
        }
        
        .cart-item-price {
            font-weight: 500;
            margin-left: 10px;
        }
        
        .cart-item-remove {
            color: #ff6b6b;
            cursor: pointer;
            margin-left: 10px;
            font-size: 14px;
        }
        
        .cart-footer {
            padding: 12px 15px;
            border-top: 1px solid #eee;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .cart-actions button {
            flex: 1;
        }
        
        .cart-actions button:first-child {
            margin-right: 8px;
        }
        
        .empty-cart-message {
            padding: 20px;
            text-align: center;
            color: #666;
        }
        
        /* Order Form Styles */
        .order-btn.add-to-cart-btn {
            background-color: #4a6fa5;
            width: 100%;
        }
        
        .order-type-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .order-type-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .order-type-tab.active {
            border-bottom-color: #ff6b6b;
            color: #ff6b6b;
        }
        
        .order-form-section {
            display: none;
        }
        
        .order-form-section.active {
            display: block;
        }
        
        .table-selection {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .table-option {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .table-option:hover:not(.occupied) {
            background-color: #f9f9f9;
        }
        
        .table-option.selected:not(.occupied) {
            background-color: #ff6b6b;
            color: white;
            border-color: #ff6b6b;
        }
        
        .table-option.occupied {
            background-color: #f0f0f0;
            color: #999;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .table-number {
            font-size: 18px;
            font-weight: bold;
        }
        
        .table-status {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .extras-section {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .extras-title {
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .extras-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .extras-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .extras-item-name {
            font-weight: 500;
        }
        
        .quantity-control.small {
            display: flex;
            align-items: center;
        }
        
        .quantity-control.small .quantity-btn {
            width: 25px;
            height: 25px;
            font-size: 14px;
        }
        
        .quantity-control.small .quantity-input {
            width: 40px;
            height: 25px;
            font-size: 14px;
            text-align: center;
        }
        
        .cart-summary {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .cart-summary-title {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        
        .cart-summary-items {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 15px;
        }
        
        .cart-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-summary-item-name {
            font-weight: 500;
        }
        
        .cart-summary-item-details {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        
        .cart-summary-total {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        /* Debug Info */
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            display: none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table-selection {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .extras-options {
                grid-template-columns: 1fr;
            }
            
            .cart-preview {
                width: 90%;
                right: 5%;
                left: 5%;
            }
        }
        
        @media (max-width: 480px) {
            .table-selection {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .menu-actions {
                flex-direction: column;
            }
            
            .order-btn {
                width: 100%;
                margin: 5px 0;
            }
        }
        
        /* Extras pricing display */
        .extras-item-price {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        /* Enhanced UI Styles */
        .header {
            background: linear-gradient(90deg, #1a0d0d, #050d0f, #000000);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-confirm {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 10px 20px;
        }

        .btn-confirm:hover {
            background-color: #e05c5c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-cancel {
            background-color: #f0f0f0;
            color: #666;
            border: none;
            padding: 10px 20px;
        }

        .btn-cancel:hover {
            background-color: #e0e0e0;
        }

        .modal-content {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background-color: #f9f9f9;
            border-radius: 12px 12px 0 0;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        .error-message {
            color: #ff6b6b;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .menu-item {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .menu-item-image {
            height: 180px;
            object-fit: cover;
        }

        .menu-item-content {
            padding: 15px;
        }

        .menu-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .menu-item-price {
            color: #ff6b6b;
            font-weight: 600;
        }

        .menu-item-description {
            color: #666;
            margin: 8px 0;
        }

        .menu-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .order-btn {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order-btn:hover {
            background-color: #e05c5c;
        }

        /* Message to cook textarea */
        .message-to-cook {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px;
            width: 100%;
            min-height: 80px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .message-to-cook:focus {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
            outline: none;
        }

        /* Optional field indicator */
        .optional-field {
            font-size: 12px;
            color: #888;
            margin-left: 5px;
            font-weight: normal;
        }
    </style>
</head>
<body>

<!-- Header with Logo and Search -->
<header class="header">
    <div class="nav-container">
        <!-- Logo -->
        <a href="login.html" class="logo">
            <i class="fas fa-utensils"></i>
            Hotel Aditya
        </a>

        <!-- Search Box -->
        <div class="search-container">
            <input type="text" id="searchInput" class="search-box" placeholder="Search for dishes..." oninput="liveSearch()" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button class="search-btn" onclick="liveSearch()" aria-label="Search">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <!-- Track Order Link -->
        <a href="order-status.php"
   style="background-color: green; color: white; padding: 8px 16px; border: none; border-radius: 5px; text-decoration: none; font-weight: bold; margin-left: 20px; display: inline-block;"
   onmouseover="this.style.backgroundColor='#006400'"
   onmouseout="this.style.backgroundColor='green'">
   Track Order
</a>

        <!-- Cart Icon -->
        <div class="cart-icon" onclick="toggleCartPreview()">
            <i class="fas fa-shopping-cart fa-lg" style="color:white;"></i>
            <span class="cart-count">0</span>
        </div>
    </div>
</header>


    <!-- Cart Preview -->
    <div id="cartPreview" class="cart-preview">
        <div class="cart-header">
            <h3 class="cart-title">Your Cart</h3>
            <i class="fas fa-times" onclick="toggleCartPreview()"></i>
        </div>
        <div id="cartItems" class="cart-items">
            <div class="empty-cart-message">Your cart is empty</div>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span id="cartTotal">₹0.00</span>
            </div>
            <div class="cart-actions">
                <button class="btn btn-cancel" onclick="clearCart()">Clear</button>
                <button class="btn btn-confirm" onclick="openCheckoutModal()">Checkout</button>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Delicious Food For Every Mood</h1>
            <p class="hero-subtitle">Experience the perfect blend of traditional flavors at Hotel Aditya</p>
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="container">
        <h2 class="section-title">Our Menu</h2>
        
        <div id="menuResults">
            <?php echo displayMenuItems($conn, $searchQuery); ?>
        </div>
    </section>

    <!-- Add to Cart Modal -->
    <div id="addToCartModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-cart-plus"></i> Add to Cart</h3>
                <span class="close" aria-label="Close" onclick="closeAddToCartModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addToCartForm">
                    <div class="form-group">
                        <label>Item Size</label>
                        <div class="size-selector">
                            <input type="radio" id="sizeFull" name="itemSize" value="Full" class="size-radio" checked>
                            <label for="sizeFull" class="size-label">Full</label>
                            
                            <input type="radio" id="sizeHalf" name="itemSize" value="Half" class="size-radio">
                            <label for="sizeHalf" class="size-label">Half</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="itemQuantity">Quantity</label>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="decrementQuantity()">-</button>
                            <input type="number" id="itemQuantity" class="quantity-input" value="1" min="1" max="10" required>
                            <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
                        </div>
                    </div>
                    
                    <div class="extras-section">
                        <div class="extras-title">Add Extras</div>
                        <div class="extras-options">
                            <div class="extras-item">
                                <div>
                                    <span class="extras-item-name">Roti</span>
                                    <div class="extras-item-price">₹20 each</div>
                                </div>
                                <div class="quantity-control small">
                                    <button type="button" class="quantity-btn" onclick="decrementExtra('roti')">-</button>
                                    <input type="number" id="rotiQuantity" class="quantity-input" value="0" min="0" max="20">
                                    <button type="button" class="quantity-btn" onclick="incrementExtra('roti')">+</button>
                                </div>
                            </div>
                            <div class="extras-item">
                                <div>
                                    <span class="extras-item-name">Chapati</span>
                                    <div class="extras-item-price">₹10 each</div>
                                </div>
                                <div class="quantity-control small">
                                    <button type="button" class="quantity-btn" onclick="decrementExtra('chapati')">-</button>
                                    <input type="number" id="chapatiQuantity" class="quantity-input" value="0" min="0" max="20">
                                    <button type="button" class="quantity-btn" onclick="incrementExtra('chapati')">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-summary">
                        <h4>Item Summary</h4>
                        <div class="summary-item">
                            <span>Item:</span>
                            <span id="summaryItemName">-</span>
                        </div>
                        <div class="summary-item">
                            <span>Size:</span>
                            <span id="summaryItemSize">Full</span>
                        </div>
                        <div class="summary-item">
                            <span>Price:</span>
                            <span id="summaryItemPrice">-</span>
                        </div>
                        <div class="summary-item">
                            <span>Quantity:</span>
                            <span id="summaryItemQuantity">1</span>
                        </div>
                        <div class="summary-item">
                            <span>Extras:</span>
                            <span id="summaryExtras">-</span>
                        </div>
                        <div class="summary-item summary-total">
                            <span>Total:</span>
                            <span id="summaryTotal">-</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="cancelAddToCart" class="btn btn-cancel" onclick="closeAddToCartModal()">Cancel</button>
                <button id="confirmAddToCart" class="btn btn-confirm" onclick="confirmAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="checkoutModalTitle"><i class="fas fa-shopping-bag"></i> Complete Your Order</h3>
                <span class="close" aria-label="Close" onclick="closeCheckoutModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Order Type Tabs -->
                <div class="order-type-tabs">
                    <div class="order-type-tab active" id="parcelOrderTab" onclick="switchOrderType('parcel')">
                        <i class="fas fa-shopping-bag"></i> Parcel Order
                    </div>
                    <div class="order-type-tab" id="tableOrderTab" onclick="switchOrderType('tableorder')">
                        <i class="fas fa-utensils"></i> Table Order
                    </div>
                </div>
                
                <form id="checkoutForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" id="orderType" name="orderType" value="parcelorder">
                    <input type="hidden" id="cartItemsInput" name="cartItems" value="">
                    <input type="hidden" name="submit_order" value="1">
                    
                    <div class="form-group">
                        <label for="customerName">Your Name</label>
                        <input type="text" id="customerName" name="customerName" class="form-control" required>
                        <div class="error-message">Please enter your name</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="customerPhone">Your Mobile Number <span class="optional-field">(Optional)</span></label>
                        <input type="tel" id="customerPhone" name="customerPhone" class="form-control" pattern="[0-9]{10}">
                        <div class="error-message">Please enter a valid 10-digit mobile number</div>
                    </div>
                    
                    <!-- Parcel Order Fields -->
                    <div id="parcelOrderFields" class="order-form-section active">
                        <div class="form-group">
                            <label for="cookMessage">Message to Cook <span class="optional-field">(Optional)</span></label>
                            <textarea id="cookMessage" name="cookMessage" class="message-to-cook" rows="3" placeholder="Any special instructions for preparing your food..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Table Order Fields -->
                    <div id="tableOrderFields" class="order-form-section">
                        <div class="form-group">
                            <label>Select Table</label>
                            <div class="table-selection">
                                <?php for($i = 1; $i <= 10; $i++): 
                                    $isOccupied = $tableStatus[$i];
                                    $statusClass = $isOccupied ? 'occupied' : 'available';
                                    $statusText = $isOccupied ? 'Occupied' : 'Available';
                                ?>
                                <div class="table-option <?php echo $statusClass; ?>" data-table="<?php echo $i; ?>" onclick="<?php echo $isOccupied ? '' : 'selectTable(this)'; ?>">
                                    <div class="table-number">Table <?php echo $i; ?></div>
                                    <div class="table-status"><?php echo $statusText; ?></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="tableNumber" name="tableNumber" value="">
                        </div>
                        
                        <div class="form-group">
                            <label for="tableCookMessage">Message to Cook <span class="optional-field">(Optional)</span></label>
                            <textarea id="tableCookMessage" name="tableCookMessage" class="message-to-cook" rows="3" placeholder="Any special instructions for preparing your food..."></textarea>
                        </div>
                    </div>
 
                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <div class="cart-summary-title">
                            <span>Order Summary</span>
                            <span id="cartItemCount">0 items</span>
                        </div>
                        <div id="cartSummaryItems" class="cart-summary-items">
                            <!-- Cart items will be populated here -->
                        </div>
                        <div class="cart-summary-total">
                            <span>Total:</span>
                            <span id="checkoutTotal">₹0.00</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="cancelCheckout" class="btn btn-cancel" onclick="closeCheckoutModal()">Cancel</button>
                <button id="confirmCheckout" class="btn btn-confirm" onclick="submitOrder()">Place Order</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> Hotel Aditya. All Rights Reserved.</p>
        </div>
    </footer>
<script>
      // Define prices for extras
  const CHAPATI_PRICE = 10;
  const ROTI_PRICE = 20;

  // Cart functionality
  let cart = [];
  let currentItem = {
    id: '',
    name: '',
    fullPrice: '',
    halfPrice: '',
    selectedSize: 'Full',
    quantity: 1,
    unitPrice: 0,
    totalPrice: 0,
    roti: 0,
    chapati: 0
  };

  // Display menu items in animation one by one 
  document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach((item, index) => {
      item.style.setProperty('--item-index', index);
    });

    // Initialize cart from localStorage if available
    const savedCart = localStorage.getItem('hotelAdityaCart');
    if (savedCart) {
      cart = JSON.parse(savedCart);
      updateCartCount();
      updateCartPreview();
    }
  });

  // Search functionality
  function liveSearch() {
    const searchQuery = document.getElementById('searchInput').value;

    // Create an XMLHttpRequest object
    const xhr = new XMLHttpRequest();

    // Configure it: GET-request for the URL
    xhr.open('GET', '?search=' + encodeURIComponent(searchQuery), true);

    // Send the request
    xhr.send();

    // This will be called after the response is received
    xhr.onload = function() {
      if (xhr.status === 200) {
        // Extract the menu results from the response
        const parser = new DOMParser();
        const htmlDoc = parser.parseFromString(xhr.responseText, 'text/html');
        const menuResults = htmlDoc.getElementById('menuResults').innerHTML;

        // Update the menu results
        document.getElementById('menuResults').innerHTML = menuResults;

        // Reapply animation to new menu items
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach((item, index) => {
          item.style.setProperty('--item-index', index);
        });
      }
    };
  }

  // Toggle cart preview
  function toggleCartPreview() {
    const cartPreview = document.getElementById('cartPreview');
    cartPreview.classList.toggle('show');
  }

  // Add to cart functionality
  function addToCart(button) {
    // Get the menu item data
    const menuItem = button.closest('.menu-item');
    currentItem.id = menuItem.getAttribute('data-item-id');
    currentItem.name = menuItem.getAttribute('data-item-name');
    currentItem.fullPrice = menuItem.getAttribute('data-full-price');
    currentItem.halfPrice = menuItem.getAttribute('data-half-price');

    // Reset form values
    document.getElementById('sizeFull').checked = true;
    document.getElementById('itemQuantity').value = 1;
    document.getElementById('rotiQuantity').value = 0;
    document.getElementById('chapatiQuantity').value = 0;

    // Set default values
    currentItem.selectedSize = 'Full';
    currentItem.quantity = 1;
    currentItem.roti = 0;
    currentItem.chapati = 0;

    // Update summary
    updateItemSummary();

    // Show modal
    const modal = document.getElementById('addToCartModal');
    modal.style.display = 'block';
    setTimeout(() => {
      modal.classList.add('show');
    }, 10);
  }

  // Update item summary in add to cart modal
  function updateItemSummary() {
    document.getElementById('summaryItemName').textContent = currentItem.name;
    document.getElementById('summaryItemSize').textContent = currentItem.selectedSize;

    // Set price based on selected size
    if (currentItem.selectedSize === 'Full') {
      document.getElementById('summaryItemPrice').textContent = currentItem.fullPrice;
      currentItem.unitPrice = parseFloat(currentItem.fullPrice.replace('₹', '').replace('N/A', '0'));
    } else {
      document.getElementById('summaryItemPrice').textContent = currentItem.halfPrice;
      currentItem.unitPrice = parseFloat(currentItem.halfPrice.replace('₹', '').replace('N/A', '0'));
    }

    document.getElementById('summaryItemQuantity').textContent = currentItem.quantity;

    // Update extras
    let extrasText = '';
    let extrasPrice = 0;
    
    if (currentItem.roti > 0) {
      extrasText += `Roti x${currentItem.roti} (₹${currentItem.roti * ROTI_PRICE})`;
      extrasPrice += currentItem.roti * ROTI_PRICE;
    }
    
    if (currentItem.chapati > 0) {
      if (extrasText) extrasText += ', ';
      extrasText += `Chapati x${currentItem.chapati} (₹${currentItem.chapati * CHAPATI_PRICE})`;
      extrasPrice += currentItem.chapati * CHAPATI_PRICE;
    }

    document.getElementById('summaryExtras').textContent = extrasText || 'None';

    // Calculate total including extras
    currentItem.totalPrice = (currentItem.unitPrice * currentItem.quantity) + extrasPrice;
    document.getElementById('summaryTotal').textContent = '₹' + currentItem.totalPrice.toFixed(2);
  }

  // Close add to cart modal
  function closeAddToCartModal() {
    const modal = document.getElementById('addToCartModal');
    modal.classList.remove('show');
    setTimeout(() => {
      modal.style.display = 'none';
    }, 300);
  }

  // Confirm add to cart
  function confirmAddToCart() {
    // Create cart item
    const cartItem = {
      id: currentItem.id,
      name: currentItem.name,
      size: currentItem.selectedSize,
      quantity: currentItem.quantity,
      unitPrice: currentItem.unitPrice,
      totalPrice: currentItem.totalPrice,
      roti: currentItem.roti,
      chapati: currentItem.chapati,
      extrasCost: (currentItem.roti * ROTI_PRICE) + (currentItem.chapati * CHAPATI_PRICE)
    };

    // Add to cart
    cart.push(cartItem);

    // Save cart to localStorage
    localStorage.setItem('hotelAdityaCart', JSON.stringify(cart));

    // Update cart count
    updateCartCount();

    // Update cart preview
    updateCartPreview();

    // Close modal
    closeAddToCartModal();

    // Show success message with SweetAlert
    Swal.fire({
      title: 'Item Added!',
      text: `${currentItem.name} (${currentItem.selectedSize}) added to cart!`,
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    });
  }

  // Update cart count
  function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    cartCount.textContent = cart.length;
  }

  // Update cart preview
  function updateCartPreview() {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');

    if (cart.length === 0) {
      cartItems.innerHTML = '<div class="empty-cart-message">Your cart is empty</div>';
      cartTotal.textContent = '₹0.00';
      return;
    }

    let html = '';
    let total = 0;
    
    cart.forEach((item, index) => {
      let itemDetails = `${item.size} x ${item.quantity}`;
      if (item.roti > 0) {
        itemDetails += `, Roti x${item.roti} (₹${item.roti * ROTI_PRICE})`;
      }
      if (item.chapati > 0) {
        itemDetails += `, Chapati x${item.chapati} (₹${item.chapati * CHAPATI_PRICE})`;
      }

      html += `
      <div class="cart-item">
        <div class="cart-item-info">
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-details">${itemDetails}</div>
        </div>
        <div class="cart-item-price">₹${item.totalPrice.toFixed(2)}</div>
        <div class="cart-item-remove" onclick="removeFromCart(${index})">
          <i class="fas fa-times"></i>
        </div>
      </div>
      `;

      total += item.totalPrice;
    });

    cartItems.innerHTML = html;
    cartTotal.textContent = '₹' + total.toFixed(2);
  }

  // Remove item from cart
  function removeFromCart(index) {
    cart.splice(index, 1);

    // Save cart to localStorage
    localStorage.setItem('hotelAdityaCart', JSON.stringify(cart));

    // Update cart count
    updateCartCount();

    // Update cart preview
    updateCartPreview();

    // Show success message with SweetAlert
    Swal.fire({
      title: 'Item Removed',
      icon: 'info',
      timer: 1500,
      showConfirmButton: false
    });
  }

  // Clear cart
  function clearCart() {
    cart = [];

    // Save cart to localStorage
    localStorage.setItem('hotelAdityaCart', JSON.stringify(cart));

    // Update cart count
    updateCartCount();

    // Update cart preview
    updateCartPreview();

    // Close cart preview
    toggleCartPreview();

    // Show success message with SweetAlert
    Swal.fire({
      title: 'Cart Cleared',
      icon: 'info',
      timer: 1500,
      showConfirmButton: false
    });
  }

  // Quantity functions
  function incrementQuantity() {
    if (currentItem.quantity < 10) {
      currentItem.quantity++;
      document.getElementById('itemQuantity').value = currentItem.quantity;
      updateItemSummary();
    }
  }

  function decrementQuantity() {
    if (currentItem.quantity > 1) {
      currentItem.quantity--;
      document.getElementById('itemQuantity').value = currentItem.quantity;
      updateItemSummary();
    }
  }

  // Extras functions
  function incrementExtra(type) {
    const input = document.getElementById(type + 'Quantity');
    if (parseInt(input.value) < 20) {
      input.value = parseInt(input.value) + 1;
      if (type === 'roti') {
        currentItem.roti = parseInt(input.value);
      } else {
        currentItem.chapati = parseInt(input.value);
      }
      updateItemSummary();
    }
  }

  function decrementExtra(type) {
    const input = document.getElementById(type + 'Quantity');
    if (parseInt(input.value) > 0) {
      input.value = parseInt(input.value) - 1;
      if (type === 'roti') {
        currentItem.roti = parseInt(input.value);
      } else {
        currentItem.chapati = parseInt(input.value);
      }
      updateItemSummary();
    }
  }

  // Size selection
  document.getElementById('sizeFull').addEventListener('change', function() {
    if (this.checked) {
      currentItem.selectedSize = 'Full';
      updateItemSummary();
    }
  });

  document.getElementById('sizeHalf').addEventListener('change', function() {
    if (this.checked) {
      currentItem.selectedSize = 'Half';
      updateItemSummary();
    }
  });

  // Quantity input change
  document.getElementById('itemQuantity').addEventListener('change', function() {
    currentItem.quantity = parseInt(this.value) || 1;
    if (currentItem.quantity < 1) currentItem.quantity = 1;
    if (currentItem.quantity > 10) currentItem.quantity = 10;
    this.value = currentItem.quantity;
    updateItemSummary();
  });

  // Checkout functions
  function openCheckoutModal() {
    if (cart.length === 0) {
      Swal.fire({
        title: 'Empty Cart',
        text: 'Your cart is empty. Please add items before checkout.',
        icon: 'warning',
        confirmButtonText: 'OK'
      });
      return;
    }

    // Close cart preview
    const cartPreview = document.getElementById('cartPreview');
    if (cartPreview.classList.contains('show')) {
      toggleCartPreview();
    }

    // Reset form
    document.getElementById('checkoutForm').reset();

    // Set default order type
    document.getElementById('orderType').value = 'parcelorder';
    switchOrderType('parcel');

    // Update cart summary
    updateCheckoutSummary();

    // Show modal
    const modal = document.getElementById('checkoutModal');
    modal.style.display = 'block';
    setTimeout(() => {
      modal.classList.add('show');
    }, 10);
  }

  function closeCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    modal.classList.remove('show');
    setTimeout(() => {
      modal.style.display = 'none';
    }, 300);
  }

  function updateCheckoutSummary() {
    const cartSummaryItems = document.getElementById('cartSummaryItems');
    const checkoutTotal = document.getElementById('checkoutTotal');
    const cartItemCount = document.getElementById('cartItemCount');

    let html = '';
    let total = 0;
    
    cart.forEach((item) => {
      let itemDetails = `${item.size} x ${item.quantity}`;
      if (item.roti > 0) {
        itemDetails += `, Roti x${item.roti} (₹${item.roti * ROTI_PRICE})`;
      }
      if (item.chapati > 0) {
        itemDetails += `, Chapati x${item.chapati} (₹${item.chapati * CHAPATI_PRICE})`;
      }

      html += `
      <div class="cart-summary-item">
        <div>
          <div class="cart-summary-item-name">${item.name}</div>
          <div class="cart-summary-item-details">${itemDetails}</div>
        </div>
        <div>₹${item.totalPrice.toFixed(2)}</div>
      </div>
      `;

      total += item.totalPrice;
    });

    cartSummaryItems.innerHTML = html;
    checkoutTotal.textContent = '₹' + total.toFixed(2);
    cartItemCount.textContent = cart.length + (cart.length === 1 ? ' item' : ' items');
  }

  // Switch between order types (parcel or table)
  function switchOrderType(type) {
    // Update hidden input
    document.getElementById('orderType').value = type === 'parcel' ? 'parcelorder' : 'tableorder';

    // Update tabs
    document.getElementById('parcelOrderTab').classList.toggle('active', type === 'parcel');
    document.getElementById('tableOrderTab').classList.toggle('active', type === 'tableorder');

    // Update form sections
    document.getElementById('parcelOrderFields').classList.toggle('active', type === 'parcel');
    document.getElementById('tableOrderFields').classList.toggle('active', type === 'tableorder');

    // Update modal title
    const modalTitle = document.getElementById('checkoutModalTitle');
    if (type === 'parcel') {
      modalTitle.innerHTML = '<i class="fas fa-shopping-bag"></i> Parcel Order';
    } else {
      modalTitle.innerHTML = '<i class="fas fa-utensils"></i> Table Order';
    }

    // If table order, select first available table
    if (type === 'tableorder') {
      const firstAvailableTable = document.querySelector('.table-option:not(.occupied)');
      if (firstAvailableTable) {
        selectTable(firstAvailableTable);
      }
    }
  }

  function selectTable(tableElement) {
    // Skip if table is occupied
    if (tableElement.classList.contains('occupied')) {
      return;
    }

    // Remove selected class from all tables
    const tables = document.querySelectorAll('.table-option');
    tables.forEach(table => {
      table.classList.remove('selected');
    });

    // Add selected class to clicked table
    tableElement.classList.add('selected');

    // Update hidden input
    const tableNumber = tableElement.getAttribute('data-table');
    document.getElementById('tableNumber').value = tableNumber;
  }

  // Submit order function
  function submitOrder() {
    // Get order type
    const orderType = document.getElementById('orderType').value;

    // Custom validation for different order types
    if (orderType === 'tableorder') {
      // For table orders, make sure a table is selected
      if (!document.getElementById('tableNumber').value) {
        Swal.fire({
          title: 'Table Required',
          text: 'Please select a table for your order.',
          icon: 'warning',
          confirmButtonText: 'OK'
        });
        return;
      }
    }

    // Check required field (name)
    const nameField = document.getElementById('customerName');

    if (!nameField.value.trim()) {
      Swal.fire({
        title: 'Name Required',
        text: 'Please enter your name.',
        icon: 'warning',
        confirmButtonText: 'OK'
      });
      return;
    }

    // Phone validation - only if provided (since it's optional)
    const phoneField = document.getElementById('customerPhone');
    if (phoneField.value.trim() && !phoneField.checkValidity()) {
      Swal.fire({
        title: 'Invalid Phone Number',
        text: 'Please enter a valid 10-digit mobile number or leave it blank.',
        icon: 'warning',
        confirmButtonText: 'OK'
      });
      return;
    }

    // Set cart items in hidden input
    document.getElementById('cartItemsInput').value = JSON.stringify(cart);

    // Show loading message
    Swal.fire({
      title: 'Processing Order...',
      text: 'Please wait while we process your order.',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
        // Submit the form
        document.getElementById('checkoutForm').submit();
      }
    });
  }
</script>
</body>
</html>
