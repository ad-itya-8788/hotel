<?php
// Database connection at the top
include 'assets/config/dbconnect.php';

// DEBUG: Turn on error reporting during development
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'active.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$env_path = __DIR__ . '/.env';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        putenv(trim($line));
    }
}


// Get environment variables with proper error handling
$cloud_name = getenv('cloud_name');
$api_key = getenv('api_key');
$api_secret = getenv('api_secret'); 

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMessage = "Invalid CSRF token.";
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => $errorMessage]));
    }
    
    // Check if it's an AJAX request expecting JSON response
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Initialize response array
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Validate and sanitize inputs
        $productName = trim($_POST['pname'] ?? '');
        $categoryId = (int)($_POST['cid'] ?? 0);
        $fullPrice = (float)($_POST['fullPrice'] ?? 0);
        $halfPrice = (float)($_POST['halfPrice'] ?? 0);
        $fullQuantity = trim($_POST['fullQuantity'] ?? '');
        $halfQuantity = trim($_POST['halfQuantity'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        // 1. Check if any required fields are empty
        if (empty($productName) || empty($description) || 
            $fullPrice <= 0 || $halfPrice <= 0 || 
            empty($fullQuantity) || empty($halfQuantity) || 
            $categoryId < 1 || $categoryId > 2) {
            throw new Exception("All fields are required and must be valid.");
        }
        
        // 2. Check if the product already exists using prepared statement
        $checkProductQuery = "SELECT 1 FROM menu WHERE pname = $1 AND cid = $2";
        $result = pg_prepare($conn, "check_product", $checkProductQuery);
        $result = pg_execute($conn, "check_product", [$productName, $categoryId]);
        
        if (pg_num_rows($result) > 0) {
            throw new Exception("Item already exists in the menu.");
        }
        
        // Validate image upload
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Image upload failed or no image was selected.");
        }
        
        // Validate image file
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
        finfo_close($fileInfo);
        
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception("Only JPG, PNG, and GIF images are allowed.");
        }
        
        // Check file size (max 2MB)
        if ($_FILES['image']['size'] > 2097152) {
            throw new Exception("Image size must be less than 2MB.");
        }
        
        // Cloudinary upload - Using the working example approach
        $file = $_FILES['image']['tmp_name'];
        
        // Prepare file for upload
        $data = base64_encode(file_get_contents($file));
        
        // Timestamp
        $timestamp = time();
        
        // Create signature as per Cloudinary docs
        $signature_string = "timestamp=$timestamp$api_secret";
        $signature = sha1($signature_string);
        
        // Cloudinary upload URL
        $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
        
        // Prepare POST fields
        $post_fields = [
            'file' => 'data:image/jpeg;base64,' . $data,
            'api_key' => $api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
        
        // Initialize cURL
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        
        // Execute request
        $response_cloudinary = curl_exec($ch);
        curl_close($ch);
        
        // Decode response
        $response_data = json_decode($response_cloudinary, true);
        
        if (!isset($response_data['secure_url'])) {
            throw new Exception("Failed to upload image to Cloudinary: " . $response_cloudinary);
        }
        
        // Get the secure URL from Cloudinary
        $imageUrl = $response_data['secure_url'];
        
        // Modify description based on selected category
        $description = ($categoryId == 1 ? "Veg: " : "Non-Veg: ") . $description;
        
        // Begin transaction
        pg_query($conn, "BEGIN");
        
        try {
            // Insert the menu item using prepared statement with the full Cloudinary URL
            $insertMenuQuery = "INSERT INTO menu (pname, cid, description, avl, img) VALUES ($1, $2, $3, true, $4)";
            $result = pg_prepare($conn, "insert_menu", $insertMenuQuery);
            $result = pg_execute($conn, "insert_menu", [$productName, $categoryId, $description, $imageUrl]);
            
            if (!$result) {
                throw new Exception("Error adding menu item: " . pg_last_error($conn));
            }
            
            // Retrieve the last inserted 'mid'
            $midResult = pg_query($conn, "SELECT currval(pg_get_serial_sequence('menu', 'mid')) AS mid");
            if (!$midResult) {
                throw new Exception("Error getting menu ID: " . pg_last_error($conn));
            }
            $mid = pg_fetch_result($midResult, 0, 'mid');
            
            // Insert size and price information
            $insertSizesQuery = "INSERT INTO size_price (mid, size, price, quantity) VALUES ($1, 'Full', $2, $3), ($1, 'Half', $4, $5)";
            $result = pg_prepare($conn, "insert_sizes", $insertSizesQuery);
            $result = pg_execute($conn, "insert_sizes", [$mid, $fullPrice, $fullQuantity, $halfPrice, $halfQuantity]);
            
            if (!$result) {
                throw new Exception("Error adding size and price information: " . pg_last_error($conn));
            }
            
            // Commit transaction
            pg_query($conn, "COMMIT");
            
            $successMessage = "Menu item added successfully!";
            $response = ['success' => true, 'message' => $successMessage];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            pg_query($conn, "ROLLBACK");
            throw $e;
        }
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        $response = ['success' => false, 'message' => $errorMessage];
    }
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Get menu item counts for display using prepared statements
$nonvegQuery = "SELECT COUNT(*) FROM menu WHERE cid = 2";
$vegQuery = "SELECT COUNT(*) FROM menu WHERE cid = 1";

$nvcResult = pg_query($conn, $nonvegQuery);
$vcResult = pg_query($conn, $vegQuery);

$nvc = pg_fetch_result($nvcResult, 0, 0);
$vc = pg_fetch_result($vcResult, 0, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Hostel Hungers</title>

    <!-- Favicon -->
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/235/235861.png" type="image/png">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Manage and update the Hostel Hungers food menu. Add, edit, or remove homemade, healthy meals made for hostel students.">
    <meta name="keywords" content="Hostel Hungers, Menu Management, Hostel Food, Admin Panel, Homemade Food, Student Meals, Healthy Food Delivery">
    <meta name="author" content="Hostel Hungers Team">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#FF6F00">

    <!-- Open Graph / Social Media Tags -->
    <meta property="og:title" content="Menu Management - Hostel Hungers">
    <meta property="og:description" content="Easily manage the menu for Hostel Hungers. Homemade, healthy, and tasty meals delivered to hostel doors.">
    <meta property="og:image" content="https://cdn-icons-png.flaticon.com/512/235/235861.png">
    <meta property="og:url" content="https://hostelhungers.onrender.com">
    <meta name="twitter:card" content="summary_large_image">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #d4af37;
            --secondary-color: #1a1a1a;
            --accent-color: #8b0000;
            --light-bg: #f8f9fa;
            --border-radius: 10px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

 
      
        
        
        
        .item-count {
            padding: 12px 25px;
            font-size: 1rem;
            margin-top: 15px;
            display: inline-block;
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .item-count .veg-count {
            color: #4cd137;
            font-weight: 600;
        }
        
        .item-count .nonveg-count {
            color: #ff6b6b;
            font-weight: 600;
        }
        
        /* Elegant Form Container */
        .form-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 40px;
            margin-bottom: 60px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        /* Beautiful Form Sections */
        .form-section {
            margin-bottom: 30px;
            padding: 30px;
            border-radius: var(--border-radius);
            background: var(--light-bg);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            position: relative;
        }
        
        .form-section:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            transform: translateY(-3px);
        }
        
        .section-title {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 25px;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            position: relative;
            padding-bottom: 12px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        .section-title i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 1.3rem;
        }
        
        /* Stylish Form Controls */
        .form-label {
            font-weight: 500;
            margin-bottom: 10px;
            color: #444;
            font-size: 0.95rem;
        }
        
        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            font-size: 0.95rem;
            transition: var(--transition);
            box-shadow: none;
            background-color: #fff;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.15);
        }
        
        .form-control::placeholder {
            color: #aaa;
            opacity: 1;
        }
        
        .input-group {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #e0e0e0;
            color: #777;
        }
        
        /* Attractive Buttons */
        .btn {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
            letter-spacing: 0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: var(--transition);
            z-index: -1;
        }
        
        .btn:hover::before {
            left: 0;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, #e5c158 100%);
            color: var(--secondary-color);
            border: none;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #e5c158 0%, var(--primary-color) 100%);
            color: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }
        
        .btn-reset {
            background-color: #6c757d;
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-reset:hover {
            background-color: #5a6268;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }
        
        /* Beautiful Image Preview */
        .image-preview-container {
            margin-top: 20px;
            text-align: center;
        }
        
        .image-preview {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px dashed #ced4da;
            display: none;
            margin: 0 auto;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
        }
        
        .image-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }
        
        /* Stylish Custom File Upload */
        .custom-file-upload {
            display: block;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 10px;
            background: var(--light-bg);
            border: 2px dashed #ced4da;
            padding: 25px 20px;
            text-align: center;
            transition: var(--transition);
        }
        
        .custom-file-upload:hover {
            border-color: var(--primary-color);
            background: rgba(212, 175, 55, 0.05);
        }
        
        .custom-file-upload i {
            font-size: 2.5rem;
            color: #aaa;
            margin-bottom: 15px;
            transition: var(--transition);
        }
        
        .custom-file-upload:hover i {
            color: var(--primary-color);
            transform: translateY(-5px);
        }
        
        .custom-file-upload span {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
            font-size: 1.1rem;
        }
        
        .custom-file-upload small {
            display: block;
            color: #777;
        }
        
        .custom-file-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        /* Price Input Styling */
        .price-input-group {
            position: relative;
        }
        
        .price-input-group .form-control {
            padding-left: 35px;
        }
        
        .price-input-group::before {
            content: '₹';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            z-index: 10;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Elegant Footer */
        .footer {
            background: linear-gradient(90deg,rgb(26, 13, 13),rgb(5, 13, 15),rgb(0, 0, 0)); /* Gradient added */
            color: white;
            padding: 25px 0;
            text-align: center;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }
        
      
        .footer p {
            position: relative;
            z-index: 1;
        }
        
        /* Beautiful Alerts */
        .alert {
            border-radius: 10px;
            font-weight: 500;
            padding: 18px 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 25px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .header-title {
                font-size: 1.8rem;
            }
            
            .btn {
                padding: 10px 20px;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-section {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        .form-section:nth-child(1) { animation-delay: 0.1s; }
        .form-section:nth-child(2) { animation-delay: 0.3s; }
        .form-section:nth-child(3) { animation-delay: 0.5s; }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .btn-submit:hover {
            animation: pulse 1s infinite;
        }
        
        /* Gold accent for form elements */
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
        }
        
        /* Floating labels effect */
        .floating-label {
            position: relative;
            margin-bottom: 20px;
        }
        
        .floating-label .form-control {
            height: 50px;
            padding-top: 20px;
        }
        
        .floating-label .form-label {
            position: absolute;
            top: 0;
            left: 15px;
            height: 100%;
            padding: 1rem 0.75rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .15s ease-in-out, transform .15s ease-in-out;
            color: #777;
        }
        
        .floating-label .form-control:focus ~ .form-label,
        .floating-label .form-control:not(:placeholder-shown) ~ .form-label {
            opacity: .65;
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
            color: var(--primary-color);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin:20px;
            padding:0px;
            text-decoration: none;
            display: flex;
            align-items: center;
            white-space: nowrap; /* Prevent logo text from wrapping */
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

        .navbar {
        padding: 0px; 
        background: linear-gradient(90deg,rgb(26, 13, 13),rgb(5, 13, 15),rgb(0, 0, 0)); /* Gradient added */
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid green;
        margin-bottom: 10px;
    }

    </style>
</head>
<body>

    <nav class="navbar " style="border-bottom:3px solid green; margin-bottom:10px;">
    <a href="login.html" class="logo">
                <i class="fas fa-utensils" style="color:red;"></i>
                Hotel Aditya
            </a>
            <div class="item-count">
                <span class="veg-count"><i class="fas fa-leaf me-2"></i> <?php echo htmlspecialchars($vc); ?> Veg</span>
                <span class="mx-4">|</span>
                <span class="nonveg-count"><i class="fas fa-drumstick-bite me-2"></i> <?php echo htmlspecialchars($nvc); ?> Non-Veg</span>
            </div>
</nav>



   
    <!-- Main Form -->
    <div class="container">
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form id="menuForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <!-- General Information -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-info-circle"></i>Item Details</h3>
                    
                    <div class="mb-4">
                        <label for="pname" class="form-label">Item Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-utensils"></i></span>
                            <input type="text" class="form-control" id="pname" name="pname" placeholder="Enter item name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="cid" class="form-label">Category</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                                <select class="form-select" id="cid" name="cid" required>
                                    <option value="" selected disabled>Select category</option>
                                    <option value="1">Vegetarian</option>
                                    <option value="2">Non-Vegetarian</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="description" class="form-label">Description</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <input type="text" class="form-control" id="description" name="description" placeholder="Enter description" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing Information -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-tags"></i>Pricing</h3>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="fullPrice" class="form-label">Full Size Price (₹)</label>
                            <div class="price-input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="fullPrice" name="fullPrice" placeholder="Enter price" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="fullQuantity" class="form-label">Full Size Quantity</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-balance-scale"></i></span>
                                <input type="text" class="form-control" id="fullQuantity" name="fullQuantity" placeholder="Enter quantity (e.g., 250g, 2 pieces)" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="halfPrice" class="form-label">Half Size Price (₹)</label>
                            <div class="price-input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="halfPrice" name="halfPrice" placeholder="Enter price" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="halfQuantity" class="form-label">Half Size Quantity</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-balance-scale"></i></span>
                                <input type="text" class="form-control" id="halfQuantity" name="halfQuantity" placeholder="Enter quantity (e.g., 125g, 1 piece)" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Image Upload -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-camera"></i>Item Image</h3>
                    
                    <div class="mb-4">
                        <label class="custom-file-upload">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Upload Image</span>
                            <small>Recommended: 800x600px JPG/PNG (Max 2MB)</small>
                            <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/gif" required>
                        </label>
                    </div>
                    <div class="image-preview-container">
                        <img id="imagePreview" src="#" alt="Preview" class="image-preview">
                    </div>
                </div>
                
                <!-- Form Buttons -->
                <div class="d-flex justify-content-center gap-4 mt-5">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-plus-circle me-2"></i>Add Item
                    </button>
                    <button type="reset" class="btn btn-reset">
                        <i class="fas fa-undo me-2"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-0">© <?php echo date('Y'); ?> Hotel Aditya. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(event) {
            const output = document.getElementById('imagePreview');
            if (event.target.files && event.target.files[0]) {
                // Check file size
                if (event.target.files[0].size > 2097152) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Image size must be less than 2MB',
                        icon: 'error',
                        confirmButtonColor: '#d4af37'
                    });
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    output.src = e.target.result;
                    output.style.display = 'block';
                    
                    // Add animation to the preview
                    output.style.opacity = '0';
                    setTimeout(() => {
                        output.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        output.style.opacity = '1';
                        output.style.transform = 'scale(1.02)';
                        setTimeout(() => {
                            output.style.transform = 'scale(1)';
                        }, 300);
                    }, 100);
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });

        // Form submission with AJAX and SweetAlert
        document.getElementById('menuForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Client-side validation
            const fullPrice = parseFloat(document.getElementById('fullPrice').value);
            const halfPrice = parseFloat(document.getElementById('halfPrice').value);
            
            if (fullPrice <= 0 || halfPrice <= 0) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Prices must be greater than 0',
                    icon: 'error',
                    confirmButtonColor: '#d4af37'
                });
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
                return;
            }
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            submitBtn.disabled = true;
            
            // Show loading overlay
            Swal.fire({
                title: 'Uploading...',
                html: '<i class="fas fa-utensils fa-spin" style="font-size: 2rem; color: #d4af37;"></i><br><br>Adding your delicious item to the menu!',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Set the X-Requested-With header to indicate an AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#d4af37',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reset form after success
                                document.getElementById('menuForm').reset();
                                document.getElementById('imagePreview').style.display = 'none';
                                
                                // Reload page to update item counts
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#d4af37'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            
                            title: 'Error!',
                            text: 'Invalid response from server',
                            icon: 'error',
                            confirmButtonColor: '#d4af37'
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Server error occurred',
                        icon: 'error',
                        confirmButtonColor: '#d4af37'
                    });
                }
                
                // Restore button state
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            };
            
            xhr.onerror = function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Network error occurred',
                    icon: 'error',
                    confirmButtonColor: '#d4af37'
                });
                
                // Restore button state
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            };
            
            xhr.send(formData);
        });
        
        // Reset image preview when form is reset
        document.getElementById('menuForm').addEventListener('reset', function() {
            document.getElementById('imagePreview').style.display = 'none';
            
            // Show reset confirmation
            Swal.fire({
                position: 'top-end',
                icon: 'info',
                title: 'Form Reset',
                text: 'All fields have been cleared',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
                toast: true
            });
        });
        
        // Add animation to form sections on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add subtle hover effect to input fields
            const inputs = document.querySelectorAll('.form-control, .form-select');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-3px)';
                    this.parentElement.style.transition = 'transform 0.3s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
            
            // Add welcome message
            setTimeout(() => {
                Swal.fire({
                    title: 'Welcome to Menu Management',
                    text: 'Add your delicious items to Hostel Hungers menu',
                    icon: 'info',
                    confirmButtonColor: '#d4af37',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            }, 1000);
        });
    </script>
</body>
</html>