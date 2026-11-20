
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_role(['admin', 'staff']);

$message = "";
$message_type = "info";

// Process approve or reject action
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $reservation_id = filter_var(
        $_POST['reservation_id'] ?? '',
        FILTER_VALIDATE_INT
    );

    $action = $_POST['action'] ?? '';

    if (!$reservation_id || !in_array($action, ['approve', 'reject'], true)) {
        $message = "Invalid reservation request.";
        $message_type = "danger";

    } else {

        try {
            $conn->begin_transaction();

            // Get and lock the reservation
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

            if (!$reservation) {
                throw new Exception("Reservation not found.");
            }

            if ($reservation['status'] !== 'pending') {
                throw new Exception(
                    "This reservation has already been processed."
                );
            }

            if ($action === 'approve') {

                $motorcycle_id = (int)$reservation['motorcycle_id'];

                // Lock the motorcycle record
                $stmt = $conn->prepare(
                    "SELECT id
                     FROM motorcycles
                     WHERE id = ?
                     FOR UPDATE"
                );

                $stmt->bind_param("i", $motorcycle_id);
                $stmt->execute();

                $motorcycle = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$motorcycle) {
                    throw new Exception("Motorcycle not found.");
                }

                // Check for other overlapping pending or approved reservations
                $stmt = $conn->prepare(
                    "SELECT id
                     FROM reservations
                     WHERE motorcycle_id = ?
                     AND id != ?
                     AND status IN ('pending', 'approved')
                     AND start_date <= ?
                     AND end_date >= ?
                     LIMIT 1"
                );

                $stmt->bind_param(
                    "iiss",
                    $motorcycle_id,
                    $reservation_id,
                    $reservation['end_date'],
                    $reservation['start_date']
                );

                $stmt->execute();
                $conflict = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($conflict) {
                    throw new Exception(
                        "Cannot approve. Another pending or approved
                         reservation overlaps these dates. Resolve
                         the other reservation first."
                    );
                }

                $new_status = 'approved';

            } else {
                $new_status = 'rejected';
            }

            // Update reservation status
            $stmt = $conn->prepare(
                "UPDATE reservations
                 SET status = ?
                 WHERE id = ? AND status = 'pending'"
            );

            $stmt->bind_param(
                "si",
                $new_status,
                $reservation_id
            );

            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                throw new Exception(
                    "Reservation could not be updated."
                );
            }

            $stmt->close();

            $conn->commit();

            $message = "Reservation successfully " . $new_status . ".";
            $message_type = "success";

        } catch (Throwable $e) {

            $conn->rollback();

            $message = $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Get all reservations
$result = $conn->query(
    "SELECT
        reservations.id,
        reservations.start_date,
        reservations.end_date,
        reservations.total_price,
        reservations.status,
        users.name AS customer_name,
        motorcycles.brand,
        motorcycles.model
     FROM reservations
     INNER JOIN users
        ON reservations.user_id = users.id
     INNER JOIN motorcycles
        ON reservations.motorcycle_id = motorcycles.id
     ORDER BY reservations.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Manage Reservations | MotoRent</title>

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

    <h2 class="fw-bold">Manage Reservations</h2>

    <p class="text-secondary">
        Review and manage customer motorcycle reservations.
    </p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo e($message_type); ?>">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mt-4">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Motorcycle</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result->num_rows > 0): ?>

                        <?php while ($reservation = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    #<?php echo (int)$reservation['id']; ?>
                                </td>

                                <td>
                                    <?php echo e($reservation['customer_name']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo e($reservation['brand'] . ' ' .
                                          $reservation['model']);
                                    ?>
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

                                <td>

                                    <?php if ($status === 'pending'): ?>

                                        <form method="POST" class="d-flex gap-2">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?php echo e(csrf_token()); ?>">

                                            <input type="hidden"
                                                   name="reservation_id"
                                                   value="<?php echo (int)$reservation['id']; ?>">

                                            <button type="submit"
                                                    name="action"
                                                    value="approve"
                                                    class="btn btn-success btn-sm"
                                                    onclick="return confirm('Approve this reservation?');">
                                                Approve
                                            </button>

                                            <button type="submit"
                                                    name="action"
                                                    value="reject"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Reject this reservation?');">
                                                Reject
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span class="text-secondary">
                                            No action
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-4">
                                No reservations found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

</body>
</html>
