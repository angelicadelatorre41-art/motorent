
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin', 'staff']);

// ========================================
// DASHBOARD STATISTICS
// ========================================

$totalBikes = 0;
$availableBikes = 0;
$rentedBikes = 0;
$totalCustomers = 0;
$totalReservations = 0;
$pendingReservations = 0;
$activeRentals = 0;
$totalPayments = 0;

// Total motorcycles
$result = $conn->query("SELECT COUNT(*) AS total FROM motorcycles");
if ($result) {
    $totalBikes = (int) $result->fetch_assoc()['total'];
}

// Available motorcycles
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM motorcycles WHERE status = 'available'"
);
if ($result) {
    $availableBikes = (int) $result->fetch_assoc()['total'];
}

// Rented motorcycles
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM motorcycles WHERE status = 'rented'"
);
if ($result) {
    $rentedBikes = (int) $result->fetch_assoc()['total'];
}

// Total customers
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"
);
if ($result) {
    $totalCustomers = (int) $result->fetch_assoc()['total'];
}

// Total reservations
$result = $conn->query("SELECT COUNT(*) AS total FROM reservations");
if ($result) {
    $totalReservations = (int) $result->fetch_assoc()['total'];
}

// Pending reservations
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM reservations WHERE status = 'pending'"
);
if ($result) {
    $pendingReservations = (int) $result->fetch_assoc()['total'];
}

// Active rentals
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM rentals
     WHERE status IN ('ongoing', 'overdue')"
);
if ($result) {
    $activeRentals = (int) $result->fetch_assoc()['total'];
}

// Recorded payments
$result = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM payments"
);
if ($result) {
    $totalPayments = (float) $result->fetch_assoc()['total'];
}

// ========================================
// MOTORCYCLE IMAGES
// Uses your existing image filenames
// ========================================

$motorcycles = [
    [
        'brand' => 'Honda',
        'model' => 'TMX 125',
        'image' => 'Honda TMX 125.png'
    ],
    [
        'brand' => 'Honda',
        'model' => 'Click 125i',
        'image' => 'Honda Click 125i.png'
    ],
    [
        'brand' => 'Yamaha',
        'model' => 'Mio Sporty',
        'image' => 'Yamaha Mio Sporty.jpg'
    ],
    [
        'brand' => 'Yamaha',
        'model' => 'Aerox 155',
        'image' => 'Yamaha Aerox 155.jpg'
    ],
    [
        'brand' => 'Suzuki',
        'model' => 'Burgman Street',
        'image' => 'Suzuki Burgman Street.jpg'
    ],
    [
        'brand' => 'Honda',
        'model' => 'BeAT',
        'image' => 'Honda-BeAT-White.webp'
    ]
];

$imageFolder = '/motorent/assets/images/motorcycles/';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Admin Dashboard | Ride N' Go</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f3f5f9;
            font-family: Arial, sans-serif;
            color: #17233d;
        }

        /* NAVIGATION */

        .navbar {
            background-color: #101d36;
            padding: 13px 0;
        }

        .navbar-brand {
            color: #ffc107 !important;
            font-weight: bold;
            font-size: 22px;
        }

        /* MAIN DASHBOARD */

        .dashboard-container {
            max-width: 1400px;
            margin: auto;
            padding: 35px 20px;
        }

        .dashboard-title {
            font-weight: 700;
            color: #101d36;
        }

        /* STATISTICS */

        .stat-card {
            border: none;
            border-radius: 12px;
            color: white;
            padding: 22px;
            min-height: 135px;
            height: 100%;
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        }

        .stat-card h3 {
            font-size: 27px;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 5px;
        }

        .stat-card p {
            margin-bottom: 0;
            font-size: 14px;
        }

        /* DASHBOARD SECTIONS */

        .dashboard-section {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        .section-title {
            color: #101d36;
            font-weight: 700;
            margin-bottom: 22px;
        }

        /* MOTORCYCLE CARDS */

        .motorcycle-card {
            border: 1px solid #e6e9ef;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            background: white;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .motorcycle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.10);
        }

        .motorcycle-image-wrapper {
            width: 100%;
            height: 210px;
            padding: 12px;
            background: #f1f2f4;
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

        .motorcycle-card .card-body {
            padding: 18px;
        }

        .motorcycle-card h5 {
            font-size: 17px;
            font-weight: 700;
            color: #101d36;
            margin-bottom: 7px;
        }

        .motorcycle-card p {
            font-size: 13px;
            color: #777;
            margin-bottom: 0;
        }

        .motorcycle-label {
            display: inline-block;
            background: #e5f8ed;
            color: #13834c;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        /* MANAGEMENT BUTTONS */

        .management-btn {
            padding: 19px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 10px;
        }

        .management-btn i {
            margin-right: 8px;
        }

        /* FOOTER */

        footer {
            color: #777;
            font-size: 13px;
        }

        /* MOBILE */

        @media (max-width: 767px) {
            .dashboard-container {
                padding: 25px 14px;
            }

            .stat-card {
                min-height: 115px;
                padding: 17px;
            }

            .motorcycle-image-wrapper {
                height: 190px;
            }

            .dashboard-section {
                padding: 17px;
            }
        }
    </style>
</head>

<body>

<!-- ========================================
     NAVIGATION BAR
========================================= -->

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">

        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-bicycle"></i> Ride N' Go Admin
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">

            <span class="text-white">
                Welcome, <?= e($_SESSION['name'] ?? 'Admin') ?>
            </span>

            <a href="../logout.php" class="btn btn-warning btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>

        </div>
    </div>
</nav>


<!-- ========================================
     MAIN DASHBOARD
========================================= -->

<main class="dashboard-container">

    <div class="mb-4">
        <h2 class="dashboard-title">Admin Dashboard</h2>

        <p class="text-muted">
            Manage motorcycles, customers, reservations, rentals, and payments.
        </p>
    </div>


    <!-- ========================================
         STATISTICS CARDS
    ========================================= -->

    <div class="row g-3 mb-5">

        <div class="col-6 col-lg-3">
            <div class="stat-card bg-primary">
                <i class="bi bi-bicycle fs-2"></i>
                <h3><?= $totalBikes ?></h3>
                <p>Total Motorcycles</p>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card bg-success">
                <i class="bi bi-check-circle fs-2"></i>
                <h3><?= $availableBikes ?></h3>
                <p>Available Motorcycles</p>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card bg-warning text-dark">
                <i class="bi bi-motorcycle fs-2"></i>
                <h3><?= $rentedBikes ?></h3>
                <p>Rented Motorcycles</p>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card bg-info text-dark">
                <i class="bi bi-people fs-2"></i>
                <h3><?= $totalCustomers ?></h3>
                <p>Total Customers</p>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card bg-secondary">
                <i class="bi bi-calendar-check fs-2"></i>
                <h3><?= $totalReservations ?></h3>
                <p>Total Reservations</p>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card bg-danger">
                <i class="bi bi-hourglass-split fs-2"></i>
                <h3><?= $pendingReservations ?></h3>
                <p>Pending Reservations</p>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card bg-dark">
                <i class="bi bi-key fs-2"></i>
                <h3><?= $activeRentals ?></h3>
                <p>Active Rentals</p>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card bg-success">
                <i class="bi bi-cash-stack fs-2"></i>
                <h3>₱<?= number_format($totalPayments, 2) ?></h3>
                <p>Recorded Payments</p>
            </div>
        </div>

    </div>


    <!-- ========================================
         ONE MOTORCYCLE FLEET SECTION
    ========================================= -->

    <section class="dashboard-section mb-5">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

            <div>
                <h3 class="section-title mb-1">
                    <i class="bi bi-motorcycle"></i>
                    Motorcycle Fleet
                </h3>

                <p class="text-muted mb-0">
                    Browse the six motorcycles in your rental collection.
                </p>
            </div>

            <a href="motorcycles.php" class="btn btn-outline-primary">
                <i class="bi bi-gear"></i> Manage Motorcycles
            </a>

        </div>

        <div class="row g-4">

            <?php foreach ($motorcycles as $bike): ?>

                <?php
                    $imagePath = $imageFolder . rawurlencode($bike['image']);
                    $motorcycleName = $bike['brand'] . ' ' . $bike['model'];
                ?>

                <div class="col-12 col-sm-6 col-lg-4">

                    <div class="card motorcycle-card">

                        <div class="motorcycle-image-wrapper">

                            <img
                                src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($motorcycleName, ENT_QUOTES, 'UTF-8') ?>"
                                class="motorcycle-image"
                                loading="lazy"
                                onerror="this.onerror=null; this.src='/motorent/assets/images/motorcycles/Honda-BeAT-White.webp';"
                            >

                        </div>

                        <div class="card-body">

                            <span class="motorcycle-label">
                                <i class="bi bi-check-circle-fill"></i>
                                Motorcycle
                            </span>

                            <h5>
                                <?= htmlspecialchars($motorcycleName, ENT_QUOTES, 'UTF-8') ?>
                            </h5>

                            <p>
                                <?= htmlspecialchars($bike['brand'], ENT_QUOTES, 'UTF-8') ?>
                                Motorcycle
                            </p>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- ========================================
         MANAGEMENT MENU
    ========================================= -->

    <section class="dashboard-section mb-4">

        <h3 class="section-title">
            <i class="bi bi-gear"></i>
            Management Menu
        </h3>

        <div class="row g-3">

            <div class="col-12 col-md-6 col-lg-4">
                <a href="motorcycles.php"
                   class="btn btn-primary management-btn w-100">
                    <i class="bi bi-bicycle"></i>
                    Manage Motorcycles
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="reservations.php"
                   class="btn btn-warning management-btn w-100">
                    <i class="bi bi-calendar-check"></i>
                    Manage Reservations
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="rentals.php"
                   class="btn btn-info management-btn w-100">
                    <i class="bi bi-key"></i>
                    Manage Rentals
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="payments.php"
                   class="btn btn-success management-btn w-100">
                    <i class="bi bi-cash-stack"></i>
                    Manage Payments
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="reports.php"
                   class="btn btn-dark management-btn w-100">
                    <i class="bi bi-bar-chart"></i>
                    View Reports
                </a>
            </div>

        </div>

    </section>


    <!-- FOOTER -->

    <footer class="text-center mt-5">
        <p>
            &copy; <?= date('Y') ?>
            Ride N' Go Motorcycle Rental Management System
        </p>
    </footer>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
