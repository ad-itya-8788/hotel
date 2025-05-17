<?php
$data = include("assets/config/data.php");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard | Hotel Aditya Restaurant Management</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Hotel Aditya's admin dashboard for restaurant menu management. Easily manage orders, menu items, and restaurant operations.">
    <meta name="keywords" content="Hotel Aditya, restaurant management, admin dashboard, digital menu, food ordering system">
    <meta name="author" content="Hotel Aditya">
    <meta name="robots" content="noindex, nofollow"> <!-- Prevent indexing of admin pages -->
    
    <!-- Open Graph / Social Media Tags -->
    <meta property="og:title" content="Admin Dashboard | Hotel Aditya">
    <meta property="og:description" content="Comprehensive restaurant management system for Hotel Aditya.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://hoteladitya.com/admin">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #e63946;
            --secondary: #1d3557;
            --accent: #f1faee;
            --light: #a8dadc;
            --dark: #1d3557;
            --success: #2a9d8f;
            --warning: #e9c46a;
            --danger: #e76f51;
            --border-radius: 10px;
            --box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color:rgba(28, 43, 23, 0);
            color: #333;
            overflow-x: hidden;
        }
        
        /* Navbar Styling */
        .navbar {
            background: linear-gradient(90deg,rgb(26, 13, 13),rgb(5, 13, 15),rgb(0, 0, 0)); /* Gradient added */
            padding: 0.8rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid var(--primary);
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            color: white;
            font-size: 1.5rem;
        }
        
        .navbar-brand i {
            color: var(--primary);
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .navbar-brand:hover {
            color: var(--accent);
        }
        
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: var(--transition);
            border-radius: 5px;
            margin: 0 5px;
        }
        
        .navbar-nav .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .navbar-nav .nav-link.active {
            color: white;
            background-color: var(--primary);
        }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, rgb(11, 14, 17) 0%, var(--secondary) 50%, rgb(14, 20, 26) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid var(--primary);
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .welcome-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
            position: relative;
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        
        .dashboard-card:hover .card-icon {
            transform: scale(1.2);
        }
        
        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--secondary);
        }
        
        .card-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
            text-align: center;
        }
        
        .card-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }
        
        .card-btn:hover {
            background-color: var(--secondary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Stats Section */
        .stats-section {
            margin: 3rem 0;
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border-bottom: 3px solid var(--primary);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stats-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Popular Items Section */
        .popular-section {
            padding: 3rem 0;
            background-color: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            padding-bottom: 1rem;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary);
        }
        
        .popular-item {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        
        .popular-item:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .popular-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .popular-item:hover img {
            transform: scale(1.1);
        }
        
        .item-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 1rem;
            color: white;
        }
        
        .item-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .item-category {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        /* Quick Actions */
        .quick-actions {
            margin: 3rem 0;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition);
            text-decoration: none;
            color: var(--secondary);
            border-left: 4px solid var(--primary);
        }
        
        .action-btn:hover {
            transform: translateX(10px);
            background-color: var(--primary);
            color: white;
        }
        
        .action-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .action-text {
            font-weight: 500;
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--secondary) 0%, #2c3e50 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
            border-top: 3px solid var(--primary);
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .footer-content {
            position: relative;
            z-index: 1;
        }
        
        .social-links {
            margin-bottom: 1rem;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            margin: 0 5px;
            transition: var(--transition);
        }
        
        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }
        
        .copyright {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .welcome-title {
                font-size: 2rem;
            }
            
            .stats-number {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .welcome-section {
                padding: 2rem 0;
            }
            
            .welcome-title {
                font-size: 1.8rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
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
        
        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }



    </style>

</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-utensils"></i>
                <strong class="adi">Hotel  Aditya</strong>
                </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
               
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Welcome Section -->
    <section class="welcome-section text-center">
        <div class="container">
            <h1 class="welcome-title animate-fadeInUp">Welcome&nbsp;to &nbsp;Hotel &nbsp;Aditya &nbsp;Dashboard</h1>
            <p>Manage your Restaurant Online </p>
        </div>
    </section>


<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">

            <!-- Total Menu Items -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card animate-fadeInUp">
                    <div class="stats-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stats-number"><?php echo htmlspecialchars($data['total']); ?></div>
                    <div class="stats-label">Total Menu Items</div>
                </div>
            </div>

            <!-- Today's Orders -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card animate-fadeInUp delay-1">
                    <div class="stats-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stats-number"><?php echo htmlspecialchars($data['orders']); ?></div>
                    <div class="stats-label">Today's Orders</div>
                </div>
            </div>

            <!-- Veg Items -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card animate-fadeInUp delay-2">
                    <div class="stats-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="stats-number"><?php echo htmlspecialchars($data['veg']); ?></div>
                    <div class="stats-label">Total Veg Items In Menu</div>
                </div>
            </div>

            <!-- Non-Veg Items -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card animate-fadeInUp delay-3">
                    <div class="stats-icon">
                        <i class="fas fa-drumstick-bite"></i>
                    </div>
                    <div class="stats-number"><?php echo htmlspecialchars($data['nonveg']); ?></div>
                    <div class="stats-label">Total Non-Veg Items In Menu</div>
                </div>
            </div>

            <!-- Today's Revenue -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card animate-fadeInUp delay-4">
                    <div class="stats-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stats-number">₹<?php echo number_format($data['sales']); ?></div>
                    <div class="stats-label">Today's Revenue</div>
                </div>
            </div>

        </div>
    </div>
</section>


    <!-- Main Dashboard Cards -->
    <section class="dashboard-cards py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="dashboard-card animate-fadeInUp">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3 class="card-title">Orders</h3>
                            <p class="card-text">View and manage all customer orders in real-time.</p>
                            <a href="orders.php" class="card-btn">Manage Orders</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="dashboard-card animate-fadeInUp delay-1">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h3 class="card-title">View Menu</h3>
                            <p class="card-text">Browse all dishes currently available in your menu.</p>
                            <a href="index.php" class="card-btn">View Menu</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="dashboard-card animate-fadeInUp delay-2">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <h3 class="card-title">Add Items</h3>
                            <p class="card-text">Add new dishes or update existing menu items.</p>
                            <a href="insertform.php" class="card-btn">Add Items</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="dashboard-card animate-fadeInUp delay-3">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-trash-alt"></i>
                            </div>
                            <h3 class="card-title">Delete Items</h3>
                            <p class="card-text">Remove outdated or unavailable dishes from menu.</p>
                            <a href="delete.php" class="card-btn">Delete Items</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Items Section -->
    <section class="popular-section">
        <div class="container">
            <h2 class="section-title">Popular Menu Items</h2>
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="popular-item animate-fadeInUp">
                        <img src="photos/burger.png" alt="Gourmet Burger">
                        <div class="item-overlay">
                            <h4 class="item-title">Gourmet Burger</h4>
                            <div class="item-category">Non-Vegetarian</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="popular-item animate-fadeInUp delay-1">
                        <img src="photos/pizza.png" alt="Wood-fired Pizza">
                        <div class="item-overlay">
                            <h4 class="item-title">Wood-fired Pizza</h4>
                            <div class="item-category">Vegetarian</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="popular-item animate-fadeInUp delay-2">
                        <img src="photos/chicken.png" alt="Herb Chicken">
                        <div class="item-overlay">
                            <h4 class="item-title">Herb Chicken</h4>
                            <div class="item-category">Non-Vegetarian</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="popular-item animate-fadeInUp delay-1">
                        <img src="photos/fish.png" alt="Grilled Salmon">
                        <div class="item-overlay">
                            <h4 class="item-title">Grilled Salmon</h4>
                            <div class="item-category">Non-Vegetarian</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="popular-item animate-fadeInUp delay-2">
                        <img src="photos/fruit.png" alt="Seasonal Fruit Platter">
                        <div class="item-overlay">
                            <h4 class="item-title">Seasonal Fruit Platter</h4>
                            <div class="item-category">Vegetarian</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="popular-item animate-fadeInUp delay-3">
                        <img src="photos/burger.png" alt="Veggie Burger">
                        <div class="item-overlay">
                            <h4 class="item-title">Veggie Burger</h4>
                            <div class="item-category">Vegetarian</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <p class="copyright">&copy; <?php echo date('Y'); ?> Hotel Aditya. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
            
            // Add animation to elements when they come into view
            const animateOnScroll = function() {
                const elements = document.querySelectorAll('.dashboard-card, .stats-card, .popular-item, .action-btn');
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const windowHeight = window.innerHeight;
                    
                    if (elementPosition < windowHeight - 50) {
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }
                });
            };
            
            // Set initial state for animation
            const elementsToAnimate = document.querySelectorAll('.dashboard-card, .stats-card, .popular-item, .action-btn');
            elementsToAnimate.forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            });
            
            // Run animation on load and scroll
            window.addEventListener('load', animateOnScroll);
            window.addEventListener('scroll', animateOnScroll);
        });
    </script>
</body>

</html>