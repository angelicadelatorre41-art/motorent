
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_role(['customer']);

$error = "";
$success = "";
$bike = null;
$motorcycle_id = 0;

// Get motorcycle ID from the URL
if (isset($_GET['motorcycle_id'])) {
    $motorcycle_id = filter_var(
        $_GET['motorcycle_id'],
        FILTER_VALIDATE_INT
    );
} elseif (isset($_POST['motorcycle_id'])) {
    $motorcycle_id = filter_var(
        $_POST['motorcycle_id'],
        FILTER_VALIDATE_INT
    );
}

if (!$motorcycle_id || $motorcycle_id < 1) {
    exit("Invalid motorcycle selected.");
}

// Process reservation form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    // Validate date format
    $start = DateTime::createFromFormat('!Y-m-d', $start_date);
    $end = DateTime::createFromFormat('!Y-m-d', $end_date);

    if (
        !$start || !$end ||
        $start->format('Y-m-d') !== $start_date ||
        $end->format('Y-m-d') !== $end_date
    ) {
        $error = "Please enter valid rental dates.";

    } elseif (
        $start_date < date('Y-m-d') ||
        $end_date < $start_date
    ) {
        $error = "Please select valid future rental dates.";

    } else {
        try {
            $conn->begin_transaction();

            // Lock the motorcycle row while checking availability
            $stmt = $conn->prepare(
                "SELECT brand, model, rental_price, status
                 FROM motorcycles
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $motorcycle_id);
            $stmt->execute();

            $bike = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$bike || $bike['status'] !== 'available') {
                throw new Exception(
                    "This motorcycle is no longer available."
                );
            }

            // Check for overlapping pending or approved reservations
            $stmt = $conn->prepare(
                "SELECT id
                 FROM reservations
                 WHERE motorcycle_id = ?
                 AND status IN ('pending', 'approved')
                 AND start_date <= ?
                 AND end_date >= ?
                 LIMIT 1"
            );

            $stmt->bind_param(
                "iss",
                $motorcycle_id,
                $end_date,
                $start_date
            );

            $stmt->execute();
            $conflict = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($conflict) {
                throw new Exception(
                    "This motorcycle already has a reservation
                     during those dates. Please choose another date."
                );
            }

            // Calculate rental days (inclusive of start and end dates)
            $days = $start->diff($end)->days + 1;

            // Calculate total price on the server
            $total_price = $days * (float)$bike['rental_price'];

            // Save reservation to the database
            $stmt = $conn->prepare(
                "INSERT INTO reservations
                (user_id, motorcycle_id, start_date, end_date,
                 total_price, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );

            $stmt->bind_param(
                "iissd",
                $_SESSION['user_id'],
                $motorcycle_id,
                $start_date,
                $end_date,
                $total_price
            );

            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $success = "Your reservation has been submitted successfully! 
                        Status: Pending approval.";

        } catch (Throwable $e) {

            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Get motorcycle information for display
$stmt = $conn->prepare(
    "SELECT brand, model, rental_price, status, description
     FROM motorcycles
     WHERE id = ?"
);

$stmt->bind_param("i", $motorcycle_id);
$stmt->execute();

$bike = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bike) {
    exit("Motorcycle not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Reserve Motorcycle | MotoRent</title>

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
            <a href="motorcycles.php"
               class="btn btn-outline-light btn-sm">
                Back to Motorcycles
            </a>

            <a href="../logout.php"
               class="btn btn-warning btn-sm">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">

                    <h2 class="fw-bold mb-3">
                        Reserve Motorcycle
                    </h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo e($success); ?>
                            <br><br>
                            <a href="motorcycles.php"
                               class="btn btn-success btn-sm">
                                Browse More Motorcycles
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo e($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="bg-light rounded p-3 mb-4">

                        <h4 class="fw-bold">
                            <?php echo e($bike['brand']); ?>
                            <?php echo e($bike['model']); ?>
                        </h4>

                        <p class="text-secondary mb-2">
                            <?php echo e($bike['description'] ?? ''); ?>
                        </p>

                        <h5 class="text-warning fw-bold">
                            ₱<?php
                                echo number_format(
                                    (float)$bike['rental_price'],
                                    2
                                );
                            ?>
                            <small class="text-secondary">
                                / day
                            </small>
                        </h5>

                        <span class="badge bg-<?php
                            echo $bike['status'] === 'available'
                                ? 'success'
                                : 'secondary';
                        ?>">
                            <?php echo e(ucfirst($bike['status'])); ?>
                        </span>

                    </div>

                    <?php if (!$success): ?>

                        <form method="POST">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?php echo e(csrf_token()); ?>">

                            <input type="hidden"
                                   name="motorcycle_id"
                                   value="<?php echo (int)$motorcycle_id; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Rental Start Date
                                </label>

                                <input type="date"
                                       name="start_date"
                                       class="form-control"
                                       min="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Rental End Date
                                </label>

                                <input type="date"
                                       name="end_date"
                                       class="form-control"
                                       min="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>

                            <div class="alert alert-info">
                                <strong>Reminder:</strong>
                                Your reservation will be submitted
                                for admin approval. The total price
                                will be calculated using the number
                                of rental days.
                            </div>

                            <button type="submit"
                                    class="btn btn-warning w-100 fw-bold"
                                    <?php echo $bike['status'] !== 'available'
                                        ? 'disabled'
                                        : ''; ?>>
                                Submit Reservation
                            </button>

                        </form>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

</div>

</body>
</html>
