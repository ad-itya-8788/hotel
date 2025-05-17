<?php
include 'assets/config/dbconnect.php';
$query = "SELECT oid, cust_name FROM orders WHERE order_status != 'complete'";
$result = pg_query($conn, $query);

$messages = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $messages[] = "<b>📦 Order Id:" . $row['oid'] . "</b> - <strong>" . $row['cust_name'] . "</strong>";
    }
} else {
    echo "Query failed.";
}

$messageString = implode("<br><br>", $messages); // Double line breaks for better separation
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Orders</title>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .swal2-popup-custom {
            animation: fadeInUp 0.5s ease-out;
            border-left: 5px solid #3498db;
        }
        
        .swal2-title-custom {
            color: #2c3e50;
            font-weight: 700;
        }
    </style>
</head>
<body>

<script>
    // Show SweetAlert2 popup with animations
    Swal.fire({
        title: '<span class="swal2-title-custom">⏳ Upcoming Order</span>',
        html: `<div style="text-align:left;max-height:60vh;overflow-y:auto;"><?php echo $messageString; ?></div>`,
        icon: 'info',
        background: '#f8f9fa',
        timer: 10000,
        timerProgressBar: true,
        showConfirmButton: true,
        position: 'center',
        customClass: {
            popup: 'swal2-popup-custom',
            title: 'swal2-title-custom'
        },
        showClass: {
            popup: 'animate__animated animate__fadeInUp'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutDown'
        },
        footer: '<span style="color:#7f8c8d">Auto-closes in 10 seconds</span>'
    });
</script>

<!-- Animate.css for additional animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

</body>
</html>