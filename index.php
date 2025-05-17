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
    </style>
</head>
<body>

    <!-- Header with Logo and Search -->
    <header class="header">
        <div class="nav-container">
            <a href="login.html" class="logo">
                <i class="fas fa-utensils"></i>
                Hotel Aditya
            </a>
            <div class="search-container">
                <input type="text" id="searchInput" class="search-box" placeholder="Search for dishes..." oninput="liveSearch()" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button class="search-btn" onclick="liveSearch()" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <div class="cart-icon" onclick="toggleCartPreview()">
<i style="color:white;" class="fas fa-shopping-cart fa-lg"></i>
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
                <h3 class="modal-title"><i class="fas fa-cart-pls"></i> Add to Cart</h3>
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
                <h3 class="modal-title" id="checkoutModalTitle"><i class="fas fa-shopping-cart"></i> Complete Your Order</h3>
                <span class="close" aria-label="Close" onclick="closeCheckoutModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Order Type Tabs -->
                <div class="order-type-tabs">
                    <div class="order-type-tab active" id="whatsappOrderTab" onclick="switchOrderType('whatsapp')">
                        <i class="fab fa-whatsapp"></i> WhatsApp Order
                    </div>
                    <div class="order-type-tab" id="tableOrderTab" onclick="switchOrderType('tableorder')">
                        <i class="fas fa-utensils"></i> Table Order
                    </div>
                </div>
                
                <form id="checkoutForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" id="orderType" name="orderType" value="homeorder">
                    <input type="hidden" id="cartItemsInput" name="cartItems" value="">
                    <input type="hidden" name="submit_order" value="1">
                    <input type="hidden" id="whatsappLinkInput" name="whatsappLink" value="">
                    
                    <div class="form-group">
                        <label for="customerName">Your Name</label>
                        <input type="text" id="customerName" name="customerName" class="form-control" required>
                        <div class="error-message">Please enter your name</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="customerPhone">Your Mobile Number</label>
                        <input type="tel" id="customerPhone" name="customerPhone" class="form-control" required pattern="[0-9]{10}">
                        <div class="error-message">Please enter a valid 10-digit mobile number</div>
                    </div>
                    
                    <!-- WhatsApp Order Fields -->
                    <div id="whatsappOrderFields" class="order-form-section active">
                        <div class="form-group">
                            <label for="customerAddress">Delivery Address</label>
                            <!-- Removed required attribute - will be controlled by JavaScript -->
                            <textarea id="customerAddress" name="customerAddress" class="form-control" rows="2"></textarea>
                            <div class="error-message">Please enter your delivery address</div>
                        </div>
                    </div>
                    
                   <!-- Table Order Fields -->
<div id="tableOrderFields" class="order-form-section">
    <div class="form-group">
        <label>Select Table</label>
        <div class="table-selection">
            <?php for($i = 1; $i <= 10; $i++): 
                $isOccupied = $tableStatus[$i];
                $statusClass = $isOccupied ? 'occupied' : 'available';  // Set class accordingly
                $statusText = $isOccupied ? 'Occupied' : 'Available';  // Fix status text
            ?>
            <div class="table-option <?php echo $statusClass; ?>" data-table="<?php echo $i; ?>" onclick="<?php echo $isOccupied ? '' : 'selectTable(this)'; ?>">
                <div class="table-number">Table <?php echo $i; ?></div>
                <div class="table-status"><?php echo $statusText; ?></div>
            </div>
            <?php endfor; ?>
        </div>
        <input type="hidden" id="tableNumber" name="tableNumber" value="">
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
   <script src="assets/js/index.js"></script>
</body>
</html>