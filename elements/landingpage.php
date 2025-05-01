<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacred Heart of Jesus Parish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gotham:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Gotham', sans-serif;
            padding-top: 70px; /* Adjust padding to prevent overlap with fixed navbar */
        }
        .navbar {
            background-color: #CD5C08;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: fixed; /* Make the header fixed at the top */
            top: 0;
            width: 100%;
            z-index: 1000; /* Ensure the navbar stays above other content */
        }
        .navbar-nav .nav-link {
            color: #333;
            font-weight: 600;
        }
        .navbar-brand {
            font-weight: 700;
            color: #333;
        }
        .hero {
            background: url('imgs/logo.png') center center/cover no-repeat;
            height: 80vh;
            position: relative;
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .hero h1 {
            color: #fff;
            font-size: 48px;
            text-align: left;
            font-weight: 700;
        }
        .hero p {
            color: #fff;
            font-size: 20px;
            text-align: left;
        }
        .btn-hero {
            background-color:#E85C0D;
            color: #fff;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px;
            text-align: center;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-hero:hover {
            background-color: #CD5C08;
        }
        .services {
            padding: 60px 0;
        }
        .services h2 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 36px;
        }
        .card {
            border: none;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-10px);
        }
        .about-section {
            padding: 60px 0;
            background-color: #f8f9fa;
        }
        .about-section h2 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 36px;
        }
        .about-section p {
            font-size: 18px;
            line-height: 1.8;
        }
        .footer {
            background-color: #E85C0D;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        /* Contact Section */
        .contact-section {
            background-color: #e9ecef; /* Light background for contrast */
            padding: 60px 0;
        }

        .contact-info i {
            color: #E85C0D; /* Icon color to match theme */
        }

        .contact-info h3 {
            margin-top: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #343a40;
        }

        .contact-info p {
            margin-top: 10px;
            font-size: 18px;
            color: #333;
        }

        .contact-info p:last-of-type {
            font-size: 16px;
            color: #555;
        }

        .contact-info i {
            display: block;
            margin-bottom: 20px;
        }

        .map-container {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
     <!-- Navbar -->
     <nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="imgs/mainlogo.png" alt="Logo" style="width: 50px; height:50px; margin-right: 10px;">
            Sacred Heart of Jesus Parish
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#services">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-primary text-white ms-lg-3" href="log-in.php">Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>


<section class="hero" style="background: url('imgs/logo.png') center center/cover no-repeat;">
    <div class="hero-overlay">
        <div class="container">
            <div class="row align-items-center">
                <!-- Left Side - Text and Button -->
                <div class="col-md-6">
                    <h1>Welcome to Sacred Heart of Jesus Parish</h1>
                    <p>A place of faith, community, and spiritual guidance.</p>
                    <p>Don Bosco-Mati, Davao Oriental.</p>
                    <a href="#services" class="btn-hero">Discover More</a>
                </div>
                <!-- Right Side - Space for Image -->
                <div class="col-md-6 d-none d-md-block">
                    <!-- Placeholder for the person image (already in the background) -->
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services" id="services">
    <div class="container">
        <h2>Our Services</h2>
        <div class="row">
            <!-- Baptism Card -->
            <div class="col-md-4">
                <a href="log-in.php" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <img src="imgs/baptism.png" class="card-img-top" alt="Baptism">
                        <div class="card-body">
                            <h5 class="card-title">Baptism</h5>
                            <p class="card-text">Join our baptism service to welcome new members into the church.</p>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Weddings Card -->
            <div class="col-md-4">
                <a href="log-in.php" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <img src="imgs/wedding.png" class="card-img-top" alt="Weddings">
                        <div class="card-body">
                            <h5 class="card-title">Weddings</h5>
                            <p class="card-text">Celebrate the sacrament of marriage in the heart of our parish.</p>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Funeral & Burial Card -->
            <div class="col-md-4">
                <a href="log-in.php" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <img src="imgs/funeral.png" class="card-img-top" alt="Funeral Services">
                        <div class="card-body">
                            <h5 class="card-title">Funeral & Burial Blessings</h5>
                            <p class="card-text">We offer funeral and burial blessings to honor the lives of those we love.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section" id="about" style="background-color: #f8f9fa; padding: 60px 0;">
    <div class="container">
        <h2 class="text-center mb-5" style="font-size: 36px; font-weight: bold; color: #343a40;">About Us</h2>
        <div class="row d-flex align-items-center">
            <!-- Left Column for Image -->
            <div class="col-lg-6 col-md-12 mb-4 mb-lg-0">
                <img src="imgs/about3.png" alt="About Us Image" class="img-fluid rounded shadow" style="width: 100%; height: auto;">
            </div>
            <!-- Right Column for Text -->
            <div class="col-lg-6 col-md-12 d-flex align-items-center">
                <div style="padding: 20px;">
                    <p style="font-size: 18px; line-height: 1.8; color: #333;">
                        Our mission is to foster a community of faith, compassion, and love, guiding people on their spiritual journey and serving those in need. Through the sacraments, teachings, and services, we aim to bring the love of Christ to our parishioners and the community.
                    </p>
                    <p style="font-size: 18px; line-height: 1.8; color: #333;">
                        Our vision is to be a vibrant and inclusive parish that embraces the values of the Gospel, empowering individuals to grow in faith, deepen their relationship with God, and make a positive impact in their community and the world.
                    </p>
                    <h3 style="font-size: 28px; font-weight: bold; color: #343a40; margin-top: 30px;">Purpose of the System</h3>
                    <p style="font-size: 18px; line-height: 1.8; color: #333;">
                        The Sacred Heart of Jesus Parish system has been designed to enhance communication, accessibility, and service management for our community. This system allows parishioners to easily request services such as baptism, weddings, funeral blessings, and other sacramental services directly online. Our system streamlines the process, ensuring that the parish can efficiently respond to and manage these requests while maintaining clear and organized records.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Contact Section -->
<section class="contact-section" id="contact" style="background-color: #e9ecef; padding: 60px 0;">
    <div class="container">
        <h2 class="text-center mb-5" style="font-size: 36px; font-weight: bold; color: #343a40;">Contact Us</h2>
        <div class="row justify-content-center">
            <!-- Contact Details -->
            <div class="col-md-8 text-center">
                <div class="contact-info">
                    <!-- Location Icon -->
                    <i class="fas fa-map-marker-alt fa-3x" style="color: #E85C0D;"></i>
                    <h3 class="mt-4" style="font-size: 24px; font-weight: bold; color: #343a40;">Our Address</h3>
                    <p style="font-size: 18px; color: #333; margin-top: 10px;">
                        Don Bosco-Mati, Davao Oriental
                    </p>
                    <p style="font-size: 16px; color: #555;">
                        You can visit us for masses, confessions, or any spiritual guidance. We are located in the heart of Don Bosco, serving the community with faith and compassion.
                    </p>
                </div>
            </div>
        </div>

        <!-- OpenStreetMap Embed -->
        <div class="row justify-content-center mt-5">
            <div class="col-md-10">
                <div class="map-container" style="border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
                    <iframe
                        width="100%"
                        height="400"
                        frameborder="0"
                        scrolling="no"
                        marginheight="0"
                        marginwidth="0"
                        src="https://www.openstreetmap.org/export/embed.html?bbox=126.26139282715%2C6.9482789501876&layer=mapnik&marker=6.9482789501876%2C126.26139282715"
                        style="border: 1px solid black">
                    </iframe>
                    <br/>
                    <small><a href="https://www.openstreetmap.org/?mlat=6.9482789501876&mlon=126.26139282715#map=15/6.9483/126.2614">View Larger Map</a></small>
                </div>
            </div>
        </div>
    </div>
</section>


    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Sacred Heart of Jesus Parish. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
