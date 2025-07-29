<?php
// Start session
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHL - Excellence. Simply delivered.</title>
    <style>
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        /* Global Styles */
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: #d40511;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        /* Header Styles */
        header {
            background-color: #ffcc00;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #d40511;
        }
        
        .logo span {
            color: #d40511;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: #333;
            font-weight: bold;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: #d40511;
            text-decoration: none;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://logistics.dhl/content/dam/dhl/global/core/images/teaser-image-main/glo-core-header-default-1440x440.jpg');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }
        
        .hero-content {
            max-width: 800px;
            padding: 0 20px;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        /* Tracking Form */
        .tracking-container {
            max-width: 600px;
            margin: -70px auto 50px;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }
        
        .tracking-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #d40511;
        }
        
        .tracking-form {
            display: flex;
            flex-direction: column;
        }
        
        .tracking-form input {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .tracking-form button {
            padding: 15px;
            background-color: #d40511;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .tracking-form button:hover {
            background-color: #b00;
        }
        
        /* Services Section */
        .services {
            max-width: 1200px;
            margin: 0 auto 50px;
            padding: 0 20px;
        }
        
        .services h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .service-card {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .service-icon {
            font-size: 40px;
            margin-bottom: 20px;
            color: #d40511;
        }
        
        .service-card h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            color: #ffcc00;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section ul li a {
            color: #ddd;
            transition: color 0.3s;
        }
        
        .footer-section ul li a:hover {
            color: #ffcc00;
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 30px auto 0;
            padding: 20px 20px 0;
            border-top: 1px solid #444;
            text-align: center;
            font-size: 14px;
            color: #ddd;
        }
        
        /* Error Message */
        .error-message {
            background-color: #ffebee;
            color: #d40511;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 15px;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0 10px;
            }
            
            .hero {
                height: 400px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .tracking-container {
                margin-top: -50px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">DHL</div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#">Services</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="admin_login.php">Admin</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Excellence. Simply delivered.</h1>
            <p>DHL connects people in over 220 countries and territories worldwide. Driven by the power of more than 380,000 employees, we deliver integrated services and tailored solutions for managing and transporting letters, goods and information.</p>
        </div>
    </section>

    <!-- Tracking Section -->
    <section class="tracking-container">
        <h2>Track Your Shipment</h2>
        <div class="error-message" id="error-message"></div>
        <form class="tracking-form" id="tracking-form">
            <input type="text" id="tracking-number" placeholder="Enter your tracking number" required>
            <button type="submit">Track Shipment</button>
        </form>
    </section>

    <!-- Services Section -->
    <section class="services">
        <h2>Our Services</h2>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">📦</div>
                <h3>Express Shipping</h3>
                <p>Fast, time-definite delivery of parcels and documents to more than 220 countries and territories.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🚚</div>
                <h3>Freight Transport</h3>
                <p>Standardized transport solutions for heavy or palletized freight by road, rail, air and ocean.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🏭</div>
                <h3>Supply Chain Solutions</h3>
                <p>Customized solutions for the entire supply chain, from planning and sourcing to production and distribution.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>About DHL</h3>
                <ul>
                    <li><a href="#">Company Information</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Press Center</a></li>
                    <li><a href="#">Sustainability</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Track a Shipment</a></li>
                    <li><a href="#">Get a Quote</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Legal</h3>
                <ul>
                    <li><a href="#">Terms of Use</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                    <li><a href="#">Fraud Awareness</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <ul>
                    <li><a href="#">Facebook</a></li>
                    <li><a href="#">Twitter</a></li>
                    <li><a href="#">LinkedIn</a></li>
                    <li><a href="#">Instagram</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> DHL International GmbH. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Form validation and submission
        document.getElementById('tracking-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const trackingNumber = document.getElementById('tracking-number').value.trim();
            const errorMessage = document.getElementById('error-message');
            
            // Simple validation
            if (!trackingNumber) {
                errorMessage.textContent = 'Please enter a tracking number';
                errorMessage.style.display = 'block';
                return;
            }
            
            // Redirect to tracking page with the tracking number
            window.location.href = 'track.php?tracking=' + encodeURIComponent(trackingNumber);
        });
    </script>
</body>
</html>
