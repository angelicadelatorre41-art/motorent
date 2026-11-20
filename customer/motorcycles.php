
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_role(['customer']);

// Get available motorcycles
$result = $conn->query(
    "SELECT * FROM motorcycles
     WHERE status = 'available'
     ORDER BY id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Available Motorcycles | MotoRent</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <span class="text-warning">Moto</span>Rent
        </a>

        <div>
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

    <h2 class="fw-bold">Available Motorcycles</h2>

    <p class="text-secondary">
        Choose a motorcycle for your next ride.
    </p>

    <div class="row g-4 mt-2">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($bike = $result->fetch_assoc()): ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body">

                            <span class="badge bg-success mb-3">
                                Available
                            </span>

                            <h4 class="fw-bold">
                                <?php echo e($bike['brand']); ?>
                                <?php echo e($bike['model']); ?>
                            </h4>

                            <p class="text-secondary">
                                <?php echo e($bike['description'] ?? 'Available for rental.'); ?>
                            </p>

                            <hr>

                            <h5 class="text-warning fw-bold">
                                ₱<?php echo number_format(
                                    (float)$bike['rental_price'],
                                    2
                                ); ?>
                                <small class="text-secondary fs-6">
                                    / day
                                </small>
                            </h5>

                            <a href="reserve.php?motorcycle_id=<?php
                                echo (int)$bike['id'];
                            ?>"
                               class="btn btn-warning w-100 mt-3">
                                Reserve This Motorcycle
                            </a>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12">
                <div class="alert alert-info">
                    No motorcycles are currently available.
                </div>
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
