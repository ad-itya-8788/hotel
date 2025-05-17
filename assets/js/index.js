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

  // Set initial required state for address field
  const addressField = document.getElementById('customerAddress');
  if (addressField) {
    addressField.required = true; // Default to WhatsApp order
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
  document.getElementById('orderType').value = 'homeorder';
  switchOrderType('whatsapp');

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

  // FIXED: Updated switchOrderType function to toggle required attribute on address field
  function switchOrderType(type) {
    // Update hidden input
    document.getElementById('orderType').value = type === 'whatsapp' ? 'homeorder' : 'tableorder';

  // Update tabs
  document.getElementById('whatsappOrderTab').classList.toggle('active', type === 'whatsapp');
  document.getElementById('tableOrderTab').classList.toggle('active', type === 'tableorder');

  // Update form sections
  document.getElementById('whatsappOrderFields').classList.toggle('active', type === 'whatsapp');
  document.getElementById('tableOrderFields').classList.toggle('active', type === 'tableorder');

  // IMPORTANT: Toggle the required attribute based on order type
  const addressField = document.getElementById('customerAddress');
  if (addressField) {
    addressField.required = (type === 'whatsapp');
    }

  // Update modal title
  const modalTitle = document.getElementById('checkoutModalTitle');
  if (type === 'whatsapp') {
    modalTitle.innerHTML = '<i class="fab fa-whatsapp"></i> WhatsApp Order';
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

  // FIXED: Updated submitOrder function to save order to database and then redirect to WhatsApp
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
    } else if (orderType === 'homeorder') {
        // For WhatsApp orders, make sure address is provided
        const addressField = document.getElementById('customerAddress');
  if (!addressField.value.trim()) {
    Swal.fire({
      title: 'Address Required',
      text: 'Please enter your delivery address.',
      icon: 'warning',
      confirmButtonText: 'OK'
    });
  return;
        }
    }

  // Check other required fields (name and phone)
  const nameField = document.getElementById('customerName');
  const phoneField = document.getElementById('customerPhone');

  if (!nameField.value.trim()) {
    Swal.fire({
      title: 'Name Required',
      text: 'Please enter your name.',
      icon: 'warning',
      confirmButtonText: 'OK'
    });
  return;
    }

  if (!phoneField.value.trim() || !phoneField.checkValidity()) {
    Swal.fire({
      title: 'Valid Phone Number Required',
      text: 'Please enter a valid 10-digit mobile number.',
      icon: 'warning',
      confirmButtonText: 'OK'
    });
  return;
    }

  // Set cart items in hidden input
  document.getElementById('cartItemsInput').value = JSON.stringify(cart);

  // If it's a WhatsApp order, create the WhatsApp message and set it in the hidden input
  if (orderType === 'homeorder') {
        const customerName = document.getElementById('customerName').value;
  const customerPhone = document.getElementById('customerPhone').value;
  const customerAddress = document.getElementById('customerAddress').value;

  // Create WhatsApp message
  let whatsappMsg = "Hello, I would like to place an order:\n\n";
  whatsappMsg += "*Customer Details:*\n";
  whatsappMsg += "Name: " + customerName + "\n";
  whatsappMsg += "Phone: " + customerPhone + "\n";
  whatsappMsg += "Address: " + customerAddress + "\n\n";

  whatsappMsg += "*Order Details:*\n";

  let total = 0;
        cart.forEach((item, index) => {
    let itemTotal = item.unitPrice * item.quantity;
  let extrasTotal = 0;

  whatsappMsg += `${index + 1}. ${item.name} (${item.size}) x ${item.quantity} - ₹${(item.unitPrice * item.quantity).toFixed(2)}\n`;
            
            if (item.roti > 0) {
                const rotiCost = item.roti * ROTI_PRICE;
  whatsappMsg += `   - Roti x${item.roti} - ₹${rotiCost.toFixed(2)}\n`;
  extrasTotal += rotiCost;
            }
            
            if (item.chapati > 0) {
                const chapatiCost = item.chapati * CHAPATI_PRICE;
  whatsappMsg += `   - Chapati x${item.chapati} - ₹${chapatiCost.toFixed(2)}\n`;
  extrasTotal += chapatiCost;
            }
            
            if (extrasTotal > 0) {
    whatsappMsg += `   Item Total: ₹${(itemTotal + extrasTotal).toFixed(2)}\n`;
            }

  total += itemTotal + extrasTotal;
        });

  whatsappMsg += "\n*Total: ₹" + total.toFixed(2) + "*\n\n";
  whatsappMsg += "Thank you!";

  // Encode the WhatsApp message
  const encodedMsg = encodeURIComponent(whatsappMsg);
  const whatsappLink = "https://wa.me/917066201454?text=" + encodedMsg;

  // Set the WhatsApp link in the hidden input
  document.getElementById('whatsappLinkInput').value = whatsappLink;
    }

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