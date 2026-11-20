
<?php
require_once __DIR__ . "/config/database.php";

// Motorcycle image folder
$imageFolder = __DIR__ . "/assets/images/motorcycles/";
$imageUrl = "/motorent/assets/images/motorcycles/";

$defaultImage = "Honda-BeAT-White.webp";

// Get available motorcycles
$motorcycles = [];

$sql = "SELECT id, brand, model, rental_price, image, description
        FROM motorcycles
        WHERE status = 'available'
        ORDER BY id DESC
        LIMIT 6";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $motorcycles[] = $row;
    }
}

// Escape text before displaying it
function displayText($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Get the correct image for each motorcycle
function getMotorcycleImage($bike, $imageFolder, $imageUrl, $defaultImage) {

    $brand = strtolower(trim($bike['brand'] ?? ''));
    $model = strtolower(trim($bike['model'] ?? ''));

    $motorcycle = $brand . ' ' . $model;

    // Match motorcycle names with the actual image filenames
    $imageMap = [
        'tmx' => 'Honda TMX 125.png',
        'click' => 'Honda Click 125i.png',
        'mio' => 'Yamaha Mio Sporty.jpg',
        'aerox' => 'Yamaha Aerox 155.jpg',
        'burgman' => 'Suzuki Burgman Street.jpg',
        'beat' => 'Honda-BeAT-White.webp'
    ];

    $imageName = '';

    // Find the matching motorcycle image
    foreach ($imageMap as $keyword => $filename) {
        if (strpos($motorcycle, $keyword) !== false) {
            $imageName = $filename;
            break;
        }
    }

    // If no model matches, try the image filename stored in the database
    if ($imageName === '') {
        $databaseImage = basename(trim($bike['image'] ?? ''));

        if (
            $databaseImage !== '' &&
            is_file($imageFolder . $databaseImage)
        ) {
            $imageName = $databaseImage;
        }
    }

    // Return the matching image if the file exists
    if (
        $imageName !== '' &&
        is_file($imageFolder . $imageName)
    ) {
        return $imageUrl . rawurlencode($imageName);
    }

    // Use default image only when no matching image exists
    if (is_file($imageFolder . $defaultImage)) {
        return $imageUrl . rawurlencode($defaultImage);
    }

    return '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MotoRent | Motorcycle Rental</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        html {
            scroll-behavior: smooth;
        }

        body {
            overflow-x: hidden;
        }

        .motorcycles-section {
            padding: 80px 0;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-heading h2 {
            font-size: 2.3rem;
            font-weight: 800;
            margin: 12px 0;
            color: #101d36;
        }

        .motorcycle-card {
            height: 100%;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.07);
            transition: 0.3s ease;
        }

        .motorcycle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        .motorcycle-image-wrapper {
            width: 100%;
            height: 220px;
            padding: 12px;
            background: #f3f3f3;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .motorcycle-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .motorcycle-info {
            padding: 20px;
        }

        .motorcycle-info h4 {
            font-size: 1.15rem;
            font-weight: 750;
            color: #101d36;
            margin: 12px 0;
        }

        .motorcycle-info p {
            font-size: 0.9rem;
            color: #697386;
            min-height: 45px;
            line-height: 1.5;
        }

        .available-label {
            display: inline-block;
            padding: 5px 10px;
            background: #e6f7ed;
            color: #16834a;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }

        .price-row h5 {
            color: #e68a00;
            font-weight: 800;
            font-size: 1.15rem;
            margin: 4px 0 0;
        }

        .price-row small {
            color: #777;
        }

        .reserve-btn {
            background: #101d36;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 8px 13px;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .reserve-btn:hover {
            background: #e68a00;
            color: #fff;
        }

        .empty-state {
            background: #f5f6f8;
            padding: 45px 20px;
            border-radius: 15px;
            text-align: center;
        }

        .empty-state i {
            font-size: 3rem;
            color: #e68a00;
        }

        .about-image {
            width: 100%;
            max-height: 420px;
            object-fit: contain;
        }

        .about-section {
            padding: 50px 0;
        }

        .contact-section {
            padding: 45px 0;
            text-align: center;
        }

        .footer {
            padding: 30px 0;
            text-align: center;
        }

        @media (max-width: 767px) {

            .motorcycle-image-wrapper {
                height: 200px;
            }

            .section-heading h2 {
                font-size: 1.8rem;
            }

        }

/* ABOUT SECTION */

.ride-n-go-about {
    width: 100%;
    padding: 65px 0;
    background: #f3f5f9;
}

.ride-n-go-about .container {
    max-width: 1200px;
    margin: 0 auto;
}

.about-heading {
    text-align: center;
    margin-bottom: 30px;
}

.about-heading span {
    color: #e68a00;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
}

.about-heading h2 {
    color: #101d36;
    font-size: 30px;
    font-weight: 800;
    margin: 10px 0;
}

.about-heading p {
    color: #687080;
    max-width: 650px;
    margin: 0 auto;
    line-height: 1.7;
}

.about-poster-wrapper {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto;
    overflow: hidden;
    border-radius: 16px;
    background: #101d36;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.about-poster {
    width: 100%;
    height: auto;
    display: block;
    object-fit: contain;
}

@media (max-width: 767px) {
    .ride-n-go-about {
        padding: 40px 15px;
    }

    .about-heading h2 {
        font-size: 23px;
    }

    .about-poster-wrapper {
        border-radius: 10px;
    }
}

    </style>

</head>

<body>

<!-- NAVIGATION -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">

    <div class="container">

        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-bicycle"></i> MotoRent
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link" href="#home">Home</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#motorcycles">Motorcycles</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#about">About</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>

                <li class="nav-item ms-lg-2">
                    <a href="login.php" class="btn btn-outline-light btn-sm">
                        Login
                    </a>
                </li>

                <li class="nav-item ms-lg-2">
                    <a href="register.php" class="btn btn-warning btn-sm">
                        Register
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- HERO SECTION -->
<section class="hero" id="home">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-7 hero-content">

                <span class="hero-label">
                    YOUR RIDE, YOUR JOURNEY
                </span>

                <h1>
                    Find Your Perfect
                    <span>Motorcycle Ride</span>
                </h1>

                <p>
                    Explore our available motorcycles and enjoy a
                    convenient rental experience with MotoRent.
                    Choose your motorcycle and start your journey.
                </p>

                <div class="d-flex flex-wrap gap-3">

                    <a href="#motorcycles" class="btn btn-warning btn-lg">
                        Explore Motorcycles
                    </a>

                    <a href="register.php" class="btn btn-outline-light btn-lg">
                        Get Started
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- FEATURES SECTION -->
<section class="features-section">

    <div class="container">

        <div class="row text-center g-4">

            <div class="col-md-4">

                <div class="feature-box">

                    <i class="bi bi-bicycle"></i>

                    <h5>Quality Motorcycles</h5>

                    <p>
                        Choose from our available motorcycle collection.
                    </p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="feature-box">

                    <i class="bi bi-calendar-check"></i>

                    <h5>Easy Reservations</h5>

                    <p>
                        Submit your motorcycle rental request online.
                    </p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="feature-box">

                    <i class="bi bi-shield-check"></i>

                    <h5>Convenient Service</h5>

                    <p>
                        Manage your rental information in one place.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- MOTORCYCLE COLLECTION -->
<section class="motorcycles-section" id="motorcycles">

    <div class="container">

        <div class="section-heading">

            <span>OUR COLLECTION</span>

            <h2>Available Motorcycles</h2>

            <p>
                Choose a motorcycle for your next journey.
            </p>

        </div>

        <div class="row g-4">

            <?php if (!empty($motorcycles)): ?>

                <?php foreach ($motorcycles as $bike): ?>

                    <?php

                    $brand = displayText($bike['brand']);
                    $model = displayText($bike['model']);

                    $description = displayText(
                        $bike['description'] ?? ''
                    );

                    $image = getMotorcycleImage(
                        $bike,
                        $imageFolder,
                        $imageUrl,
                        $defaultImage
                    );

                    $price = number_format(
                        (float)$bike['rental_price'],
                        2
                    );

                    ?>

                    <div class="col-md-6 col-lg-4">

                        <div class="motorcycle-card">

                            <!-- MOTORCYCLE IMAGE -->
                            <div class="motorcycle-image-wrapper">

                                <?php if ($image !== ''): ?>

                                    <img
                                        src="<?php echo displayText($image); ?>"
                                        alt="<?php echo $brand . ' ' . $model; ?>"
                                        class="motorcycle-image"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="text-center text-muted">
                                        <i class="bi bi-image fs-1"></i>
                                        <p>Image unavailable</p>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <!-- MOTORCYCLE DETAILS -->
                            <div class="motorcycle-info">

                                <span class="available-label">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Available
                                </span>

                                <h4>
                                    <?php echo $brand . ' ' . $model; ?>
                                </h4>

                                <p>
                                    <?php
                                    echo $description !== ''
                                        ? $description
                                        : 'A motorcycle available for rental.';
                                    ?>
                                </p>

                                <div class="price-row">

                                    <div>

                                        <small>Rental price</small>

                                        <h5>
                                            ₱<?php echo $price; ?>
                                            <small>/ day</small>
                                        </h5>

                                    </div>

                                    <a
                                        href="login.php"
                                        class="reserve-btn"
                                    >
                                        Reserve
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12">

                    <div class="empty-state">

                        <i class="bi bi-bicycle"></i>

                        <h4>No motorcycles available yet</h4>

                        <p>
                            Please check back later.
                        </p>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>




<!-- ABOUT SECTION -->
<section class="ride-n-go-about" id="about">

    <div class="container">

        <div class="about-heading">
            <span>ABOUT RIDE N' GO</span>
            <h2>Your Ride, Your Journey</h2>
            <p>
                Discover convenient motorcycle rentals with
                a collection of motorcycles ready for your next journey.
            </p>
        </div>

        <div class="about-poster-wrapper">
            <img
                src="/motorent/assets/images/ride-n-go-cover.png"
                alt="Ride N' Go Motorcycle Rental Management System"
                class="about-poster"
            >
        </div>

    </div>

</section>




<

<!-- CONTACT SECTION -->
<section class="contact-section" id="contact">

    <div class="container">

        <span class="section-label">
            GET IN TOUCH
        </span>

        <h2>Ready for Your Next Ride?</h2>

        <p>
            Register for an account to explore motorcycles
            and submit a rental reservation.
        </p>

        <a href="register.php" class="btn btn-warning btn-lg">
            Register Now
        </a>

    </div>

</section>


<!-- FOOTER -->
<footer class="footer">

    <div class="container">

        <h4>
            <i class="bi bi-bicycle"></i> MotoRent
        </h4>

        <p>
            Motorcycle Rental Management System
        </p>

        <p class="copyright">
            &copy; <?php echo date("Y"); ?> MotoRent.
            All Rights Reserved.
        </p>

    </div>

</footer>


<!-- BOOTSTRAP JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- CUSTOM JAVASCRIPT -->
<script src="assets/js/script.js"></script>

</body>
</html>
