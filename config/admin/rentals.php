
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_role(['admin', 'staff']);

$message = "";
$message_type = "info";

// Process rental actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    try {
        $conn->begin_transaction();

        if ($action === 'start') {

            $reservation_id = filter_var(
                $_POST['reservation_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$reservation_id) {
                throw new Exception("Invalid reservation.");
            }

            // Lock the reservation
            $stmt = $conn->prepare(
                "SELECT motorcycle_id, start_date, end_date, status
                 FROM reservations
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $reservation = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$reservation || $reservation['status'] !== 'approved') {
                throw new Exception("Only approved reservations can start.");
            }

            if ($reservation['start_date'] > date('Y-m-d')) {
                throw new Exception("The reservation start date has not arrived.");
            }

            $motorcycle_id = (int)$reservation['motorcycle_id'];

            // Lock motorcycle
            $stmt = $conn->prepare(
                "SELECT status FROM motorcycles WHERE id = ? FOR UPDATE"
            );

            $stmt->bind_param("i", $motorcycle_id);
            $stmt->execute();
            $bike = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$bike || $bike['status'] !== 'available') {
                throw new Exception("This motorcycle is not available.");
            }

            // Check if this reservation already has a rental
            $stmt = $conn->prepare(
                "SELECT id FROM rentals WHERE reservation_id = ? LIMIT 1"
            );

            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                throw new Exception("A rental already exists for this reservation.");
            }

            // Create rental record
            $stmt = $conn->prepare(
                "INSERT INTO rentals
                 (reservation_id, actual_start_date,
                  expected_return_date, status)
                 VALUES (?, CURDATE(), ?, 'ongoing')"
            );

            $stmt->bind_param(
                "is",
                $reservation_id,
                $reservation['end_date']
            );

            $stmt->execute();
            $stmt->close();

            // Mark motorcycle as rented
            $stmt = $conn->prepare(
                "UPDATE motorcycles SET status = 'rented' WHERE id = ?"
            );

            $stmt->bind_param("i", $motorcycle_id);
            $stmt->execute();
            $stmt->close();

            $message = "Rental started successfully!";

        } elseif ($action === 'return') {

            $rental_id = filter_var(
                $_POST['rental_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            $condition = trim($_POST['motorcycle_condition'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');

            if (!$rental_id || $condition === '') {
                throw new Exception("Please provide the motorcycle condition.");
            }

            // Lock rental record and get motorcycle ID
            $stmt = $conn->prepare(
                "SELECT rentals.status, reservations.motorcycle_id
                 FROM rentals
                 INNER JOIN reservations
                    ON rentals.reservation_id = reservations.id
                 WHERE rentals.id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $rental_id);
            $stmt->execute();
            $rental = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$rental || !in_array(
                $rental['status'],
                ['ongoing', 'overdue'],
                true
            )) {
                throw new Exception("This rental cannot be returned.");
            }

            $motorcycle_id = (int)$rental['motorcycle_id'];

            // Record return
            $stmt = $conn->prepare(
                "INSERT INTO returns
                 (rental_id, motorcycle_condition, remarks)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "iss",
                $rental_id,
                $condition,
                $remarks
            );

            $stmt->execute();
            $stmt->close();

            // Complete rental
            $stmt = $conn->prepare(
                "UPDATE rentals
                 SET actual_return_date = CURDATE(),
                     status = 'completed'
                 WHERE id = ?"
            );

            $stmt->bind_param("i", $rental_id);
            $stmt->execute();
            $stmt->close();

            // Make motorcycle available again
            $stmt = $conn->prepare(
                "UPDATE motorcycles
                 SET status = 'available'
                 WHERE id = ?"
            );

            $stmt->bind_param("i", $motorcycle_id);
            $stmt->execute();
            $stmt->close();

            $message = "Motorcycle return recorded successfully!";

        } else {
            throw new Exception("Invalid action.");
        }

        $conn->commit();
        $message_type = "success";

    } catch (Throwable $e) {

        $conn->rollback();
        $message = $e->getMessage();
        $message_type = "danger";
    }
}

// Get approved reservations that have not started a rental
$approved = $conn->query(
    "SELECT
        reservations.id,
        reservations.start_date,
        reservations.end_date,
        users.name AS customer_name,
        motorcycles.brand,
        motorcycles.model
     FROM reservations
     INNER JOIN users
        ON reservations.user_id = users.id
     INNER JOIN motorcycles
        ON reservations.motorcycle_id = motorcycles.id
     LEFT JOIN rentals
        ON reservations.id = rentals.reservation_id
     WHERE reservations.status = 'approved'
       AND rentals.id IS NULL
     ORDER BY reservations.start_date ASC"
);

// Get active rentals
$active = $conn->query(
    "SELECT
        rentals.id,
        rentals.actual_start_date,
        rentals.expected_return_date,
        rentals.status,
        users.name AS customer_name,
        motorcycles.brand,
        motorcycles.model
     FROM rentals
     INNER JOIN reservations
        ON rentals.reservation_id = reservations.id
     INNER JOIN users
        ON reservations.user_id = users.id
     INNER JOIN motorcycles
        ON reservations.motorcycle_id = motorcycles.id
     WHERE rentals.status IN ('ongoing', 'overdue')
     ORDER BY rentals.id DESC"
);

// Get completed rentals
$completed = $conn->query(
    "SELECT
        rentals.id,
        rentals.actual_start_date,
        rentals.expected_return_date,
        rentals.actual_return_date,
        users.name AS customer_name,
        motorcycles.brand,
        motorcycles.model
     FROM rentals
     INNER JOIN reservations
        ON rentals.reservation_id = reservations.id
     INNER JOIN users
        ON reservations.user_id = users.id
     INNER JOIN motorcycles
        ON reservations.motorcycle_id = motorcycles.id
     WHERE rentals.status = 'completed'
     ORDER BY rentals.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Rental Management | MotoRent</title>

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

            <a href="reservations.php" class="btn btn-outline-light btn-sm">
                Reservations
            </a>

            <a href="../logout.php" class="btn btn-warning btn-sm">
                Logout
            </a>
        </div>

    </div>
</nav>

<div class="container py-5">

    <h2 class="fw-bold">Rental Management</h2>

    <p class="text-secondary">
        Start rentals and manage motorcycle returns.
    </p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo e($message_type); ?>">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <!-- Approved Reservations -->
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <h4 class="fw-bold mb-3">Approved Reservations</h4>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Motorcycle</th>
                            <th>Start Date</th>
                            <th>Expected Return</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($approved->num_rows > 0): ?>

                        <?php while ($row = $approved->fetch_assoc()): ?>

                            <tr>
                                <td>#<?php echo (int)$row['id']; ?></td>

                                <td><?php echo e($row['customer_name']); ?></td>

                                <td>
                                    <?php echo e($row['brand'] . ' ' . $row['model']); ?>
                                </td>

                                <td><?php echo e($row['start_date']); ?></td>

                                <td><?php echo e($row['end_date']); ?></td>

                                <td>
                                    <?php if ($row['start_date'] <= date('Y-m-d')): ?>

                                        <form method="POST">
                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?php echo e(csrf_token()); ?>">

                                            <input type="hidden"
                                                   name="reservation_id"
                                                   value="<?php echo (int)$row['id']; ?>">

                                            <button type="submit"
                                                    name="action"
                                                    value="start"
                                                    class="btn btn-success btn-sm"
                                                    onclick="return confirm('Start this rental?');">
                                                Start Rental
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <span class="text-secondary">
                                            Waiting for start date
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center">
                                No approved reservations waiting for rental.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>
        </div>
    </div>

    <!-- Active Rentals -->
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <h4 class="fw-bold mb-3">Active Rentals</h4>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>Rental ID</th>
                            <th>Customer</th>
                            <th>Motorcycle</th>
                            <th>Start Date</th>
                            <th>Expected Return</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($active->num_rows > 0): ?>

                        <?php while ($row = $active->fetch_assoc()): ?>

                            <tr>
                                <td>#<?php echo (int)$row['id']; ?></td>

                                <td><?php echo e($row['customer_name']); ?></td>

                                <td>
                                    <?php echo e($row['brand'] . ' ' . $row['model']); ?>
                                </td>

                                <td><?php echo e($row['actual_start_date']); ?></td>

                                <td><?php echo e($row['expected_return_date']); ?></td>

                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo e(ucfirst($row['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <button type="button"
                                            class="btn btn-warning btn-sm"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#return<?php echo (int)$row['id']; ?>">
                                        Record Return
                                    </button>
                                </td>
                            </tr>

                            <tr class="collapse" id="return<?php echo (int)$row['id']; ?>">
                                <td colspan="7">

                                    <form method="POST" class="p-3 bg-light rounded">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo e(csrf_token()); ?>">

                                        <input type="hidden"
                                               name="rental_id"
                                               value="<?php echo (int)$row['id']; ?>">

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                Motorcycle Condition
                                            </label>

                                            <select name="motorcycle_condition"
                                                    class="form-select"
                                                    required>
                                                <option value="">Select condition</option>
                                                <option value="Good">Good</option>
                                                <option value="Needs inspection">Needs inspection</option>
                                                <option value="Damaged">Damaged</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                Remarks
                                            </label>

                                            <textarea name="remarks"
                                                      class="form-control"
                                                      rows="2"
                                                      placeholder="Enter return notes"></textarea>
                                        </div>

                                        <button type="submit"
                                                name="action"
                                                value="return"
                                                class="btn btn-success"
                                                onclick="return confirm('Record this motorcycle return?');">
                                            Confirm Return
                                        </button>

                                    </form>

                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center">
                                No active rentals.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>
        </div>
    </div>

    <!-- Completed Rentals -->
    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <h4 class="fw-bold mb-3">Completed Rentals</h4>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>Rental ID</th>
                            <th>Customer</th>
                            <th>Motorcycle</th>
                            <th>Start Date</th>
                            <th>Expected Return</th>
                            <th>Actual Return</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($completed->num_rows > 0): ?>

                        <?php while ($row = $completed->fetch_assoc()): ?>

                            <tr>
                                <td>#<?php echo (int)$row['id']; ?></td>

                                <td><?php echo e($row['customer_name']); ?></td>

                                <td>
                                    <?php echo e($row['brand'] . ' ' . $row['model']); ?>
                                </td>

                                <td><?php echo e($row['actual_start_date']); ?></td>

                                <td><?php echo e($row['expected_return_date']); ?></td>

                                <td><?php echo e($row['actual_return_date']); ?></td>

                                <td>
                                    <span class="badge bg-success">
                                        Completed
                                    </span>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center">
                                No completed rentals yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
