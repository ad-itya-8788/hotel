<?php
include 'active.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Digital Menu Card</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #0f0f0f;
            color: #fff;
        }

        header {
            padding: 20px 40px;
            background: rgba(0, 0, 0, 0.8);
            border-bottom: 2px solid #adff2f;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            font-size: 1.5rem;
            color: #ffcc00;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffcc00;
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 30px 20px;
        }

        .dashboard-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .dashboard-title h2 {
            font-size: 2rem;
            color: #ffcc00;
            margin-bottom: 10px;
        }

        .dashboard-title p {
            color: #aaa;
            font-size: 0.95rem;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .menu-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #ffcc00;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: 0.4s;
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 0 15px rgba(232, 95, 26, 0.8);
        }

        .menu-icon {
            font-size: 2.5rem;
            color: #ffcc00;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .menu-card p {
            font-size: 0.9rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .menu-btn {
            display: inline-block;
            padding: 10px 20px;
            border: 2px solid #ffcc00;
            color: #ffcc00;
            border-radius: 25px;
            text-decoration: none;
            transition: 0.3s;
        }

        .menu-btn:hover {
            background: #ffcc00;
            color: #111;
        }

        .gallery-section {
            margin-top: 50px;
        }

        .gallery-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .gallery-title h3 {
            font-size: 1.6rem;
            color: #ffcc00;
            text-transform: uppercase;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            padding: 0 10px;
        }

        .gallery-item {
            border: 2px solid #ffcc00;
            padding: 5px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            text-align: center;
            transition: 0.3s;
        }

        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px #ffcc00aa;
        }

        .gallery-item img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #888;
            border-top: 1px solid #333;
            margin-top: 40px;
        }
          .custom-btn:hover {
        background-color: #bb2d3b; /* Darker red on hover */
        color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    </style>

</head>

<body>

    <header>
        <h1>Hotel Aditya - Menu Management
</h1>
        <div class="admin-info">
            <div class="admin-avatar">A</div>
            <span>Aditya</span>
<button class="btn btn-danger custom-btn" onclick="window.location.href='logout.php'" style="background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer;">
    Logout
</button>        </div>

    </header>


    
    <div class="container">
        <div class="dashboard-title">
            <h2>Welcome, Admin!</h2>
            <p>Effortlessly manage your restaurant's digital menu with our easy-to-use dashboard.</p>
        </div>


        

        <div class="menu-grid">
            <div class="menu-card">
                <div class="menu-icon"><i class="fas fa-utensils"></i></div>
                <h3>Orders</h3>
                <p>Check Out Orders.</p>
                <a href="orders.php" class="menu-btn">Orders</a>
            </div>
            <div class="menu-card">
                <div class="menu-icon"><i class="fas fa-utensils"></i></div>
                <h3>View Full Menu</h3>
                <p>Browse all dishes currently available in the menu.</p>
                <a href="index.php" class="menu-btn">View Menu</a>
            </div>

            <div class="menu-card">
                <div class="menu-icon"><i class="fas fa-concierge-bell"></i></div>
                <h3>Add or Update Items</h3>
                <p>Add new dishes, update prices, or change availability status.</p>
                <a href="insertform.php" class="menu-btn">Manage Items</a>
            </div>

            <div class="menu-card">
                <div class="menu-icon"><i class="fas fa-user-check"></i></div>
                <h3>Delete Menu Items</h3>
                <p>Remove outdated or unavailable dishes from the menu list.</p>
                <a href="delete.php" class="menu-btn">Delete Items</a>
            </div>
        </div>

        <!-- Gallery Section -->
        <div class="gallery-section">
            <div class="gallery-title">
                <h3>Popular Menu Items</h3>
            </div>
            <div class="gallery-grid">
                <div class="gallery-item" data-title="Seasonal Fruit Platter">
                    <img src="photos/fruit.png" alt="Fruit Dish">
                </div>
                <div class="gallery-item" data-title="Grilled Salmon">
                    <img src="photos/fish.png" alt="Fish Dish">
                </div>
                <div class="gallery-item" data-title="Gourmet Burger">
                    <img src="photos/burger.png" alt="Burger">
                </div>
                <div class="gallery-item" data-title="Wood-fired Pizza">
                    <img src="photos/pizza.png" alt="Pizza">
                </div>
                <div class="gallery-item" data-title="Herb Chicken">
                    <img src="photos/chicken.png" alt="Chicken Dish">
                </div>
            </div>
        </div>
    </div>

    <footer>
        &copy; 2025 Digital Menu Card System. All rights reserved.
    </footer>

</body>

</html>