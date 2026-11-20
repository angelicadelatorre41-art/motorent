
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin', 'staff']);

// Get report statistics
function getCount($conn, $sql) {
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    return (int) $result->fetch_assoc()['total'];
}

$totalBikes = getCount($conn, "SELECT COUNT(*) AS total FROM motorcycles");

$availableBikes = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM motorcycles WHERE status = 'available'"
);

$rentedBikes = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM motorcycles WHERE status = 'rented'"
);

$maintenanceBikes = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM motorcycles WHERE status = 'maintenance'"
);

$totalCustomers = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"
);

$totalReservations = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM reservations"
);

$pendingReservations = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM reservations WHERE status = 'pending'"
);

$approvedReservations = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM reservations WHERE status = 'approved'"
);

$rejectedReservations = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM reservations WHERE status = 'rejected'"
);

$totalRentals = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM rentals"
);

$activeRentals = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM rentals WHERE status IN ('ongoing', 'overdue')"
);

$completedRentals = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM rentals WHERE status = 'completed'"
);

// Get payment summary
$paymentResult = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM payments"
);

$totalPayments = $paymentResult
    ? (float) $paymentResult->fetch_assoc()['total']
    : 0;

// Recent reservations
$recentReservations = $conn->query(
    "SELECT r.id, u.name AS customer_name,
            m.brand, m.model, r.start_date, r.end_date,
            r.total_price, r.status
     FROM reservations r
     INNER JOIN users u ON r.user_id = u.id
     INNER JOIN motorcycles m ON r.motorcycle_id = m.id
     ORDER BY r.id DESC
     LIMIT 10"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Reports | MotoRent</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #212529;
        }

        .navbar-brand {
            color: #ffc107 !important;
            font-weight: bold;
        }

        .report-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            height: 100%;
        }

        .report-card h3 {
            font-weight: bold;
            margin-top: 10px;
        }

        .section-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }

        @media print {
            .navbar, .no-print, footer {
                display: none !important;
            }

            body {
                background: white;
            }

            .report-card, .section-box {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-bicycle"></i> MotoRent Admin
        </a>

        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                Dashboard
            </a>

            <a href="../logout.php" class="btn btn-warning btn-sm">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">System Reports</h2>
            <p class="text-muted mb-0">
                Summary of MotoRent system records.
            </p>
        </div>

        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Print Report
        </button>
    </div>

    <h4 class="mb-3">Motorcycle Summary</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-bicycle fs-2 text-primary"></i>
                <h3><?= $totalBikes ?></h3>
                <p class="text-muted mb-0">Total Motorcycles</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-check-circle fs-2 text-success"></i>
                <h3><?= $availableBikes ?></h3>
                <p class="text-muted mb-0">Available</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-key fs-2 text-warning"></i>
                <h3><?= $rentedBikes ?></h3>
                <p class="text-muted mb-0">Rented</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-tools fs-2 text-danger"></i>
                <h3><?= $maintenanceBikes ?></h3>
                <p class="text-muted mb-0">Under Maintenance</p>
            </div>
        </div>
    </div>

    <h4 class="mb-3">Customer and Reservation Summary</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-people fs-2 text-primary"></i>
                <h3><?= $totalCustomers ?></h3>
                <p class="text-muted mb-0">Total Customers</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-calendar-check fs-2 text-info"></i>
                <h3><?= $totalReservations ?></h3>
                <p class="text-muted mb-0">Total Reservations</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                <h3><?= $pendingReservations ?></h3>
                <p class="text-muted mb-0">Pending Reservations</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-check-circle fs-2 text-success"></i>
                <h3><?= $approvedReservations ?></h3>
                <p class="text-muted mb-0">Approved Reservations</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-x-circle fs-2 text-danger"></i>
                <h3><?= $rejectedReservations ?></h3>
                <p class="text-muted mb-0">Rejected Reservations</p>
            </div>
        </div>
    </div>

    <h4 class="mb-3">Rental and Payment Summary</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-key fs-2 text-primary"></i>
                <h3><?= $totalRentals ?></h3>
                <p class="text-muted mb-0">Total Rentals</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-arrow-repeat fs-2 text-warning"></i>
                <h3><?= $activeRentals ?></h3>
                <p class="text-muted mb-0">Active Rentals</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-check2-all fs-2 text-success"></i>
                <h3><?= $completedRentals ?></h3>
                <p class="text-muted mb-0">Completed Rentals</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="report-card">
                <i class="bi bi-cash-stack fs-2 text-success"></i>
                <h3>₱<?= number_format($totalPayments, 2) ?></h3>
                <p class="text-muted mb-0">Recorded Payments</p>
            </div>
        </div>
    </div>

    <div class="section-box mb-4">
        <h4 class="mb-3">Recent Reservations</h4>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Motorcycle</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Price</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($recentReservations && $recentReservations->num_rows > 0): ?>
                        <?php while ($row = $recentReservations->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td><?= e($row['customer_name']) ?></td>
                                <td>
                                    <?= e($row['brand'] . ' ' . $row['model']) ?>
                                </td>
                                <td><?= e($row['start_date']) ?></td>
                                <td><?= e($row['end_date']) ?></td>
                                <td>
                                    ₱<?= number_format((float) $row['total_price'], 2) ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = 'secondary';

                                    if ($row['status'] === 'approved') {
                                        $statusClass = 'success';
                                    } elseif ($row['status'] === 'pending') {
                                        $statusClass = 'warning';
                                    } elseif ($row['status'] === 'rejected') {
                                        $statusClass = 'danger';
                                    } elseif ($row['status'] === 'cancelled') {
                                        $statusClass = 'secondary';
                                    }
                                    ?>

                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= e(ucfirst($row['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                No reservations found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="text-center text-muted mt-4">
        MotoRent Motorcycle Rental Management System
        <br>
        Report generated: <?= date('F d, Y h:i A') ?>
    </footer>

</div>

</body>
</html>
