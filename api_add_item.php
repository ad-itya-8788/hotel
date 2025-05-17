<?php
header('Content-Type: application/json');

include 'assets/config/dbconnect.php';

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Get and validate inputs
    $productName = trim($_POST['pname'] ?? '');
    $categoryId = (int)($_POST['cid'] ?? 0);
    $fullPrice = (float)($_POST['fullPrice'] ?? 0);
    $halfPrice = (float)($_POST['halfPrice'] ?? 0);
    $fullQuantity = trim($_POST['fullQuantity'] ?? '');
    $halfQuantity = trim($_POST['halfQuantity'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Basic validation
    if (empty($productName)) throw new Exception("Item name is required");
    if ($categoryId < 1 || $categoryId > 2) throw new Exception("Invalid category");
    if ($fullPrice <= 0 || $halfPrice <= 0) throw new Exception("Prices must be greater than 0");
    if (empty($fullQuantity) || empty($halfQuantity)) throw new Exception("Quantity is required");
    if (empty($description)) throw new Exception("Description is required");
    
    // Check if product exists
    $check = pg_query_params($conn, "SELECT 1 FROM menu WHERE pname = $1 AND cid = $2", [$productName, $categoryId]);
    if (!$check) throw new Exception("Database error checking for existing item");
    if (pg_num_rows($check) > 0) throw new Exception("Item already exists");
    
    // Handle image upload
    if (!isset($_FILES['image'])) throw new Exception("Image is required");
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Image upload error: " . $_FILES['image']['error']);
    }
    if ($_FILES['image']['size'] > 2097152) throw new Exception("Image must be less than 2MB");
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($_FILES['image']['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) throw new Exception("Only JPG, PNG, GIF allowed");
    
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $imgName = uniqid() . '.' . $ext;
    $imgPath = 'uploads/' . $imgName;
    
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $imgPath)) {
        throw new Exception("Failed to save image");
    }
    
    // Add category prefix to description
    $description = ($categoryId == 1 ? "Veg: " : "Non-Veg: ") . $description;
    
    // Begin transaction
    pg_query($conn, "BEGIN");
    
    try {
        // Insert menu item
        $menuInsert = pg_query_params($conn, 
            "INSERT INTO menu (pname, cid, description, avl, img) VALUES ($1, $2, $3, true, $4) RETURNING mid",
            [$productName, $categoryId, $description, $imgPath]
        );
        
        if (!$menuInsert) throw new Exception("Failed to add menu item: " . pg_last_error($conn));
        
        $mid = pg_fetch_result($menuInsert, 0, 0);
        if (!$mid) throw new Exception("Failed to get menu ID");
        
        // Insert sizes
        $sizesInsert = pg_query_params($conn,
            "INSERT INTO size_price (mid, size, price, quantity) VALUES ($1, 'Full', $2, $3), ($1, 'Half', $4, $5)",
            [$mid, $fullPrice, $fullQuantity, $halfPrice, $halfQuantity]
        );
        
        if (!$sizesInsert) throw new Exception("Failed to add sizes: " . pg_last_error($conn));
        
        pg_query($conn, "COMMIT");
        
        $response = [
            'success' => true,
            'message' => "Item added successfully!"
        ];
        
    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        @unlink($imgPath);
        throw $e;
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
exit();