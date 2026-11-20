
<?php
require_once __DIR__ . "/../includes/auth.php";

require_role(['customer']);

$customer_name = $_SESSION['name'] ?? 'Customer';
$first_name = explode(' ', trim($customer_name))[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Customer Dashboard | MotoRent</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --dark: #111827;
            --orange: #f97316;
            --light: #f4f6fa;
            --muted: #8490a3;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--light);
            color: var(--dark);
        }

        /* NAVBAR */
        .navbar {
            background: #111827;
            padding: 17px 0;
        }

        .brand {
            font-size: 23px;
            font-weight: 800;
            color: white;
            text-decoration: none;
            letter-spacing: -1px;
        }

        .brand span {
            color: var(--orange);
        }

        .nav-link-custom {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-right: 25px;
            transition: .2s;
        }

        .nav-link-custom:hover {
            color: white;
        }

        .logout-btn {
            background: var(--orange);
            color: white;
            padding: 10px 17px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #ea580c;
            color: white;
        }

        /* MAIN CONTENT */
        .dashboard-container {
            max-width: 1200px;
            margin: auto;
            padding: 40px 20px;
        }

        .welcome-label {
            color: var(--orange);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .page-title {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -1.2px;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: var(--muted);
            font-size: 14px;
        }

        /* HERO */
        .hero {
            background: linear-gradient(120deg, #111827, #263449);
            border-radius: 20px;
            padding: 42px;
            color: white;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
            min-height: 260px;
            display: flex;
            align-items: center;
        }

        .hero::after {
            content: "\F45A";
            font-family: "bootstrap-icons";
            position: absolute;
            right: 45px;
            top: 15px;
            font-size: 180px;
            color: rgba(255,255,255,.06);
            transform: rotate(-12deg);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 560px;
        }

        .hero h2 {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 12px;
            color: white;
        }

        .hero p {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 25px;
        }

        .hero-btn {
            display: inline-block;
            background: var(--orange);
            color: white;
            text-decoration: none;
            padding: 13px 21px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            transition: .2s;
        }

        .hero-btn:hover {
            background: #ea580c;
            color: white;
            transform: translateY(-2px);
        }

        /* SECTION */
        .section-heading {
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .section-description {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 22px;
        }

        /* MOTORCYCLE GALLERY */
        .motorcycle-card {
            background: white;
            border: 1px solid #edf0f5;
            border-radius: 16px;
            overflow: hidden;
            height: 100%;
            transition: .25s;
        }

        .motorcycle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(15, 23, 42, .10);
        }

        .motorcycle-image {
            width: 100%;
            height: 210px;
            object-fit: contain;
            display: block;
            background: #ffffff;
            padding: 8px;
        }

        .motorcycle-card-body {
            padding: 18px;
        }

        .motorcycle-card h5 {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .motorcycle-card p {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 15px;
        }

        .motorcycle-btn {
            display: inline-block;
            background: var(--orange);
            color: white;
            text-decoration: none;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .motorcycle-btn:hover {
            background: #ea580c;
            color: white;
        }

        /* DASHBOARD CARDS */
        .feature-card {
            background: white;
            border: 1px solid #edf0f5;
            border-radius: 16px;
            padding: 25px;
            height: 100%;
            transition: .25s;
            text-decoration: none;
            color: var(--dark);
            display: block;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
            color: var(--dark);
            border-color: #fed7aa;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            margin-bottom: 20px;
        }

        .orange-icon {
            background: #fff1e8;
            color: #f97316;
        }

        .blue-icon {
            background: #eaf2ff;
            color: #2563eb;
        }

        .green-icon {
            background: #e8f8ef;
            color: #16a34a;
        }

        .feature-card h5 {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
            min-height: 40px;
            margin-bottom: 20px;
        }

        .card-link {
            color: var(--orange);
            font-size: 12px;
            font-weight: 800;
        }

        .card-link i {
            margin-left: 5px;
        }

        /* INFORMATION BANNER */
        .info-banner {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            padding: 20px;
            margin-top: 35px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .info-banner i {
            color: var(--orange);
            font-size: 25px;
        }

        .info-banner h6 {
            font-weight: 800;
            margin-bottom: 4px;
        }

        .info-banner p {
            font-size: 12px;
            color: #78716c;
            margin: 0;
        }

        /* FOOTER */
        footer {
            border-top: 1px solid #e5e7eb;
            margin-top: 45px;
            padding: 22px 0;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 25px 15px;
            }

            .hero {
                padding: 30px 23px;
                min-height: 250px;
            }

            .hero h2 {
                font-size: 25px;
            }

            .hero::after {
                right: 5px;
                font-size: 120px;
                top: 80px;
            }

            .page-title {
                font-size: 25px;
            }

            .nav-link-custom {
                display: none;
            }

            .motorcycle-image {
                height: 190px;
            }
        }

        @media (max-width: 480px) {
            .hero h2 {
                font-size: 23px;
            }

            .motorcycle-image {
                height: 180px;
            }
        }
    </style>
</head>

<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">

        <a class="brand" href="../index.php">
            MOTO<span>RENT</span>.
        </a>

        <div class="d-flex align-items-center">
            <a href="motorcycles.php" class="nav-link-custom">
                Motorcycles
            </a>

            <a href="my-reservations.php" class="nav-link-custom">
                My Reservations
            </a>

            <a href="profile.php" class="nav-link-custom">
                My Profile
            </a>

            <a href="../logout.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>

    </div>
</nav>

<!-- DASHBOARD CONTENT -->
<main class="dashboard-container">

    <!-- PAGE HEADER -->
    <div>
        <div class="welcome-label">Customer Portal</div>

        <h1 class="page-title">
            Welcome back, <?php echo e($first_name); ?>!
        </h1>

        <p class="page-subtitle">
            Manage your motorcycle reservations and explore your next ride.
        </p>
    </div>

    <!-- HERO BANNER -->
    <section class="hero">
        <div class="hero-content">

            <div class="welcome-label">
                YOUR NEXT JOURNEY STARTS HERE
            </div>

            <h2>
                Find Your Ride.<br>
                Enjoy the Journey.
            </h2>

            <p>
                Explore our available motorcycles, choose your preferred
                rental dates, and submit your reservation with ease.
            </p>

            <a href="motorcycles.php" class="hero-btn">
                Explore Motorcycles
                <i class="bi bi-arrow-right ms-2"></i>
            </a>

        </div>
    </section>

    <!-- MOTORCYCLE GALLERY -->
    <section class="mt-5">

        <h3 class="section-heading">Featured Motorcycles</h3>

        <p class="section-description">
            Explore some of the motorcycles featured in MotoRent.
            Visit Browse Motorcycles to check availability and rental prices.
        </p>

        <div class="row g-4">

            <!-- HONDA TMX 125 -->
            <div class="col-md-6 col-lg-4">
                <div class="motorcycle-card">

                    <img
                        src="/motorent/assets/images/motorcycles/Honda TMX 125.png"
                        class="motorcycle-image"
                        alt="Honda TMX 125"
                    >

                    <div class="motorcycle-card-body">
                        <h5>Honda TMX 125</h5>
                        <p>Honda Motorcycle</p>

                        <a href="motorcycles.php" class="motorcycle-btn">
                            View Motorcycle
                        </a>
                    </div>

                </div>
            </div>

            <!-- HONDA CLICK 125I -->
            <div class="col-md-6 col-lg-4">
                <div class="motorcycle-card">

                    <img
                        src="/motorent/assets/images/motorcycles/Honda Click 125i.png"
                        class="motorcycle-image"
                        alt="Honda Click 125i"
                    >

                    <div class="motorcycle-card-body">
                        <h5>Honda Click 125i</h5>
                        <p>Honda Motorcycle</p>

                        <a href="motorcycles.php" class="motorcycle-btn">
                            View Motorcycle
                        </a>
                    </div>

                </div>
            </div>

            <!-- YAMAHA MIO SPORTY -->
            <div class="col-md-6 col-lg-4">
                <div class="motorcycle-card">

                    <img
                        src="/motorent/assets/images/motorcycles/Yamaha Mio Sporty.jpg"
                        class="motorcycle-image"
                        alt="Yamaha Mio Sporty"
                    >

                    <div class="motorcycle-card-body">
                        <h5>Yamaha Mio Sporty</h5>
                        <p>Yamaha Motorcycle</p>

                        <a href="motorcycles.php" class="motorcycle-btn">
                            View Motorcycle
                        </a>
                    </div>

                </div>
            </div>

            <!-- YAMAHA AEROX 155 -->
            <div class="col-md-6 col-lg-4">
                <div class="motorcycle-card">

                    <img
                        src="/motorent/assets/images/motorcycles/Yamaha Aerox 155.jpg"
                        class="motorcycle-image"
                        alt="Yamaha Aerox 155"
                    >

                    <div class="motorcycle-card-body">
                        <h5>Yamaha Aerox 155</h5>
                        <p>Yamaha Motorcycle</p>

                        <a href="motorcycles.php" class="motorcycle-btn">
                            View Motorcycle
                        </a>
                    </div>

                </div>
            </div>

            <!-- SUZUKI BURGMAN STREET -->
            <div class="col-md-6 col-lg-4">
                <div class="motorcycle-card">

                    <img
                        src="/motorent/assets/images/motorcycles/Suzuki Burgman Street.jpg"
                        class="motorcycle-image"
                        alt="Suzuki Burgman Street"
                    >

                    <div class="motorcycle-card-body">
                        <h5>Suzuki Burgman Street</h5>
                        <p>Suzuki Motorcycle</p>

                        <a href="motorcycles.php" class="motorcycle-btn">
                            View Motorcycle
                        </a>
                    </div>

                </div>
            </div>

            <!-- HONDA BEAT -->
            <div class="col-md-6 col-lg-4">
                <div class="motorcycle-card">

                    <img
                        src="/motorent/assets/images/motorcycles/Honda-BeAT-White.webp"
                        class="motorcycle-image"
                        alt="Honda BeAT"
                    >

                    <div class="motorcycle-card-body">
                        <h5>Honda BeAT</h5>
                        <p>Honda Motorcycle</p>

                        <a href="motorcycles.php" class="motorcycle-btn">
                            View Motorcycle
                        </a>
                    </div>

                </div>
            </div>

        </div>

        <div class="text-center mt-4">
            <a href="motorcycles.php" class="hero-btn">
                Browse All Motorcycles
                <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>

    </section>

    <!-- QUICK ACCESS -->
    <section class="mt-5">

        <h3 class="section-heading">Your Dashboard</h3>

        <p class="section-description">
            Access your MotoRent account and rental activities.
        </p>

        <div class="row g-4">

            <!-- BROWSE MOTORCYCLES -->
            <div class="col-md-4">

                <a href="motorcycles.php" class="feature-card">

                    <div class="feature-icon orange-icon">
                        <i class="bi bi-bicycle"></i>
                    </div>

                    <h5>Browse Motorcycles</h5>

                    <p>
                        Explore available motorcycles and find the
                        right ride for your next trip.
                    </p>

                    <div class="card-link">
                        Explore bikes
                        <i class="bi bi-arrow-right"></i>
                    </div>

                </a>

            </div>

            <!-- MY RESERVATIONS -->
            <div class="col-md-4">

                <a href="my-reservations.php" class="feature-card">

                    <div class="feature-icon blue-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>

                    <h5>My Reservations</h5>

                    <p>
                        View your reservation details, rental dates,
                        and reservation status.
                    </p>

                    <div class="card-link">
                        View reservations
                        <i class="bi bi-arrow-right"></i>
                    </div>

                </a>

            </div>

            <!-- MY PROFILE -->
            <div class="col-md-4">

                <a href="profile.php" class="feature-card">

                    <div class="feature-icon green-icon">
                        <i class="bi bi-person-circle"></i>
                    </div>

                    <h5>My Profile</h5>

                    <p>
                        Manage your personal information and
                        update your account password.
                    </p>

                    <div class="card-link">
                        Manage profile
                        <i class="bi bi-arrow-right"></i>
                    </div>

                </a>

            </div>

        </div>

    </section>

    <!-- INFORMATION BANNER -->
    <div class="info-banner">

        <i class="bi bi-info-circle-fill"></i>

        <div>
            <h6>Need help with your reservation?</h6>

            <p>
                Check My Reservations to see the latest status
                of your submitted rental requests.
            </p>
        </div>

    </div>

    <!-- FOOTER -->
    <footer>
        © <?php echo date('Y'); ?> MotoRent.
        Motorcycle Rental Management System.
        All rights reserved.
    </footer>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
