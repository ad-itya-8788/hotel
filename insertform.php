<?php
include 'assets/config/dbconnect.php';

// Initialize response
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
        if (pg_num_rows($check) > 0) throw new Exception("Item already exists");
        
        // Handle image upload
        if (!isset($_FILES['image'])) throw new Exception("Image is required");
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) throw new Exception("Image upload error");
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
            
            if (!$menuInsert) throw new Exception("Failed to add menu item");
            
            $mid = pg_fetch_result($menuInsert, 0, 0);
            if (!$mid) throw new Exception("Failed to get menu ID");
            
            // Insert sizes
            $sizesInsert = pg_query_params($conn,
                "INSERT INTO size_price (mid, size, price, quantity) VALUES ($1, 'Full', $2, $3), ($1, 'Half', $4, $5)",
                [$mid, $fullPrice, $fullQuantity, $halfPrice, $halfQuantity]
            );
            
            if (!$sizesInsert) throw new Exception("Failed to add sizes");
            
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
    
    // Return JSON response for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// Get menu item counts for display
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
    <title>Menu Management | Hotel Aditya</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #d4af37;
            --secondary-color: #1a1a1a;
            --accent-color: #8b0000;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }
        
        .header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #333 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
                        font-family:Tahoma;

        border-bottom:2px solid green;
        }
        
        .header-title {
            font-weight: 600;
            margin: 0;
        }
        
        .header-title .gold {
            color: var(--primary-color);
        }
        
        .item-count {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-top: 10px;
            display: inline-block;
        }
        
        .item-count .veg-count {
            color: #28a745;
        }
        
        .item-count .nonveg-count {
            color: #dc3545;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 50px;
        }
        
        .form-section {
            margin-bottom: 25px;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .section-title {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .form-control, .form-select {
            padding: 10px 15px;
            border-radius: 6px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.25);
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            padding: 10px 25px;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .btn-submit:hover {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-reset {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .image-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px dashed #ced4da;
            display: none;
            margin-top: 10px;
        }
        
        .footer {
            background: var(--secondary-color);
            color: white;
            padding: 15px 0;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .alert {
            border-radius: 8px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <h1 class="header-title"><span class="gold">Hotel Aditya</span> - Menu Management</h1>
       
        </div>
    </header>

    <!-- Main Form -->
    <div class="container">
        
        <?php if (!empty($response['message']) && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])): ?>
            <div class="alert alert-<?php echo $response['success'] ? 'success' : 'danger'; ?> alert-dismissible fade show mb-4" role="alert">
                <i class="fas <?php echo $response['success'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($response['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
       

        <div style="display: flex; justify-content: center; margin: 30px 0;">
  <div style="background: linear-gradient(to right, #ffffff, #f0f0f0); padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 300px;">
    <h5 style="text-align: center; margin-bottom: 15px; font-weight: bold; color: #333;">Total Item Count</h5>
    <div style="display: flex; justify-content: space-between; font-size: 16px;">
      <span class="veg-count text-success">
        <i class="fas fa-leaf me-1"></i> <?php echo htmlspecialchars($vc); ?> Veg
      </span>
      <span class="text-muted">|</span>
      <span class="nonveg-count text-danger">
        <i class="fas fa-drumstick-bite me-1"></i> <?php echo htmlspecialchars($nvc); ?> Non-Veg
      </span>
    </div>
  </div>
</div>

        
            <form id="menuForm" method="post" enctype="multipart/form-data">
                <!-- General Information -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-info-circle me-2"></i>Item Details</h3>
                    
                    <div class="mb-3">
                        <label for="pname" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="pname" name="pname" placeholder="Enter item name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cid" class="form-label">Category</label>
                            <select class="form-select" id="cid" name="cid" required>
                                <option value="" selected disabled>Select category</option>
                                <option value="1">Vegetarian</option>
                                <option value="2">Non-Vegetarian</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" placeholder="Enter description" required>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing Information -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-tags me-2"></i>Pricing</h3>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fullPrice" class="form-label">Full Size Price (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="fullPrice" name="fullPrice" placeholder="Enter price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fullQuantity" class="form-label">Full Size Quantity</label>
                            <input type="text" class="form-control" id="fullQuantity" name="fullQuantity" placeholder="Enter quantity" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="halfPrice" class="form-label">Half Size Price (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="halfPrice" name="halfPrice" placeholder="Enter price" required>
                        </div>
                        <div class="col-md-6">
                            <label for="halfQuantity" class="form-label">Half Size Quantity</label>
                            <input type="text" class="form-control" id="halfQuantity" name="halfQuantity" placeholder="Enter quantity" required>
                        </div>
                    </div>
                </div>
                
                <!-- Image Upload -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-camera me-2"></i>Item Image</h3>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Upload Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/gif" required>
                        <small class="text-muted">Recommended: 800x600px JPG/PNG (Max 2MB)</small>
                    </div>
                    <img id="imagePreview" src="#" alt="Preview" class="image-preview">
                </div>
                
                <!-- Form Buttons -->
                <div class="d-flex justify-content-center gap-3 mt-4">
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
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });

        // Form submission with AJAX and SweetAlert
        document.getElementById('menuForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            submitBtn.disabled = true;
            
            // Client-side validation
            const fullPrice = parseFloat(form.elements['fullPrice'].value);
            const halfPrice = parseFloat(form.elements['halfPrice'].value);
            
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
            
            // AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#d4af37',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        form.reset();
                        document.getElementById('imagePreview').style.display = 'none';
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
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'An error occurred while processing your request',
                    icon: 'error',
                    confirmButtonColor: '#d4af37'
                });
            })
            .finally(() => {
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            });
        });
        
        // Reset image preview when form is reset
        document.getElementById('menuForm').addEventListener('reset', function() {
            document.getElementById('imagePreview').style.display = 'none';
        });
    </script>
</body>
</html>