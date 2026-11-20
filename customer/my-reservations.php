
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_role(['customer']);

// Get reservations belonging to the logged-in customer
$stmt = $conn->prepare(
    "SELECT
        reservations.id,
        reservations.start_date,
        reservations.end_date,
        reservations.total_price,
        reservations.status,
        motorcycles.brand,
        motorcycles.model
     FROM reservations
     INNER JOIN motorcycles
        ON reservations.motorcycle_id = motorcycles.id
     WHERE reservations.user_id = ?
     ORDER BY reservations.id DESC"
);

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>My Reservations | MotoRent</title>

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
            <a href="dashboard.php"
               class="btn btn-outline-light btn-sm">
                Dashboard
            </a>

            <a href="motorcycles.php"
               class="btn btn-outline-light btn-sm">
                Motorcycles
            </a>

            <a href="../logout.php"
               class="btn btn-warning btn-sm">
                Logout
            </a>
        </div>

    </div>
</nav>

<div class="container py-5">

    <h2 class="fw-bold">My Reservations</h2>

    <p class="text-secondary">
        View your motorcycle reservation records and their status.
    </p>

    <div class="card border-0 shadow-sm mt-4">

        <div class="card-body">

            <?php if ($result->num_rows > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>Reservation ID</th>
                                <th>Motorcycle</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Total Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($reservation = $result->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$reservation['id']; ?>
                                    </td>

                                    <td class="fw-bold">
                                        <?php echo e($reservation['brand']); ?>
                                        <?php echo e($reservation['model']); ?>
                                    </td>

                                    <td>
                                        <?php echo e($reservation['start_date']); ?>
                                    </td>

                                    <td>
                                        <?php echo e($reservation['end_date']); ?>
                                    </td>

                                    <td>
                                        ₱<?php echo number_format(
                                            (float)$reservation['total_price'],
                                            2
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php
                                        $status = $reservation['status'];

                                        $badge = 'warning';

                                        if ($status === 'approved') {
                                            $badge = 'success';
                                        } elseif ($status === 'rejected') {
                                            $badge = 'danger';
                                        } elseif ($status === 'cancelled') {
                                            $badge = 'secondary';
                                        }
                                        ?>

                                        <span class="badge bg-<?php echo $badge; ?>">
                                            <?php echo e(ucfirst($status)); ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="alert alert-info mb-0">
                    You don't have any reservations yet.

                    <a href="motorcycles.php">
                        Browse available motorcycles
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>

<?php
$stmt->close();
?>
