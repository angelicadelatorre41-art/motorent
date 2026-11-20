
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_role(['admin', 'staff']);

$message = "";
$message_type = "info";

// Process payment form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $rental_id = filter_var(
        $_POST['rental_id'] ?? '',
        FILTER_VALIDATE_INT
    );

    $amount = filter_var(
        $_POST['amount'] ?? '',
        FILTER_VALIDATE_FLOAT
    );

    $payment_method = $_POST['payment_method'] ?? '';
    $status = $_POST['status'] ?? '';

    $allowed_methods = ['Cash', 'GCash', 'Bank Transfer'];
    $allowed_statuses = ['unpaid', 'partial', 'paid'];

    try {

        if (
            !$rental_id ||
            $amount === false ||
            $amount < 0 ||
            !in_array($payment_method, $allowed_methods, true) ||
            !in_array($status, $allowed_statuses, true)
        ) {
            throw new Exception("Please enter valid payment information.");
        }

        if ($status === 'paid' && $amount <= 0) {
            throw new Exception("Paid status requires an amount greater than zero.");
        }

        if ($status === 'unpaid' && $amount != 0) {
            throw new Exception("Unpaid status must have an amount of zero.");
        }

        // Check that the rental exists
        $stmt = $conn->prepare(
            "SELECT id FROM rentals WHERE id = ?"
        );

        $stmt->bind_param("i", $rental_id);
        $stmt->execute();

        $rental = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$rental) {
            throw new Exception("Rental record not found.");
        }

        // Check if a payment record already exists
        $stmt = $conn->prepare(
            "SELECT id FROM payments
             WHERE rental_id = ?
             LIMIT 1"
        );

        $stmt->bind_param("i", $rental_id);
        $stmt->execute();

        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {

            // Update existing payment
            $stmt = $conn->prepare(
                "UPDATE payments
                 SET amount = ?,
                     payment_method = ?,
                     status = ?,
                     payment_date = CURRENT_TIMESTAMP
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "dssi",
                $amount,
                $payment_method,
                $status,
                $existing['id']
            );

        } else {

            // Create new payment
            $stmt = $conn->prepare(
                "INSERT INTO payments
                 (rental_id, amount, payment_method, status)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "idss",
                $rental_id,
                $amount,
                $payment_method,
                $status
            );
        }

        $stmt->execute();
        $stmt->close();

        $message = "Payment information saved successfully!";
        $message_type = "success";

    } catch (Throwable $e) {

        $message = $e->getMessage();
        $message_type = "danger";
    }
}

// Get rental and payment records
$result = $conn->query(
    "SELECT
        rentals.id AS rental_id,
        rentals.status AS rental_status,
        users.name AS customer_name,
        motorcycles.brand,
        motorcycles.model,
        reservations.total_price,
        payments.amount,
        payments.payment_method,
        payments.status AS payment_status,
        payments.payment_date
     FROM rentals
     INNER JOIN reservations
        ON rentals.reservation_id = reservations.id
     INNER JOIN users
        ON reservations.user_id = users.id
     INNER JOIN motorcycles
        ON reservations.motorcycle_id = motorcycles.id
     LEFT JOIN payments
        ON rentals.id = payments.rental_id
     ORDER BY rentals.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Payment Management | MotoRent</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">

        <a class="navbar-brand fw-bold" href="dashboard.php">
            <span class="text-warning">Moto</span>Rent Admin
        </a>

        <div>
            <a href="dashboard.php"
               class="btn btn-outline-light btn-sm">
                Dashboard
            </a>

            <a href="rentals.php"
               class="btn btn-outline-light btn-sm">
                Rentals
            </a>

            <a href="../logout.php"
               class="btn btn-warning btn-sm">
                Logout
            </a>
        </div>

    </div>
</nav>

<div class="container py-5">

    <h2 class="fw-bold">Payment Management</h2>

    <p class="text-secondary">
        Record and manage customer rental payments.
    </p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo e($message_type); ?>">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <h4 class="fw-bold mb-3">Rental Payment Records</h4>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>Rental ID</th>
                            <th>Customer</th>
                            <th>Motorcycle</th>
                            <th>Rental Total</th>
                            <th>Amount Paid</th>
                            <th>Method</th>
                            <th>Payment Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result->num_rows > 0): ?>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    #<?php echo (int)$row['rental_id']; ?>
                                </td>

                                <td>
                                    <?php echo e($row['customer_name']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo e($row['brand'] . ' ' . $row['model']);
                                    ?>
                                </td>

                                <td>
                                    ₱<?php echo number_format(
                                        (float)$row['total_price'],
                                        2
                                    ); ?>
                                </td>

                                <td>
                                    <?php if ($row['amount'] !== null): ?>
                                        ₱<?php echo number_format(
                                            (float)$row['amount'],
                                            2
                                        ); ?>
                                    <?php else: ?>
                                        ₱0.00
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo e($row['payment_method'] ?? 'Not recorded'); ?>
                                </td>

                                <td>
                                    <?php
                                    $payment_status = $row['payment_status'] ?? 'unpaid';

                                    $badge = 'danger';

                                    if ($payment_status === 'paid') {
                                        $badge = 'success';
                                    } elseif ($payment_status === 'partial') {
                                        $badge = 'warning';
                                    }
                                    ?>

                                    <span class="badge bg-<?php echo $badge; ?>">
                                        <?php echo e(ucfirst($payment_status)); ?>
                                    </span>
                                </td>

                                <td>

                                    <button type="button"
                                            class="btn btn-primary btn-sm"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#payment<?php echo (int)$row['rental_id']; ?>">
                                        Record Payment
                                    </button>

                                </td>

                            </tr>

                            <tr class="collapse"
                                id="payment<?php echo (int)$row['rental_id']; ?>">

                                <td colspan="8">

                                    <form method="POST"
                                          class="bg-light rounded p-3">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo e(csrf_token()); ?>">

                                        <input type="hidden"
                                               name="rental_id"
                                               value="<?php echo (int)$row['rental_id']; ?>">

                                        <div class="row g-3">

                                            <div class="col-md-4">

                                                <label class="form-label fw-bold">
                                                    Amount Paid (₱)
                                                </label>

                                                <input type="number"
                                                       name="amount"
                                                       class="form-control"
                                                       min="0"
                                                       step="0.01"
                                                       value="<?php echo e($row['amount'] ?? '0.00'); ?>"
                                                       required>

                                            </div>

                                            <div class="col-md-4">

                                                <label class="form-label fw-bold">
                                                    Payment Method
                                                </label>

                                                <select name="payment_method"
                                                        class="form-select"
                                                        required>

                                                    <option value="Cash"
                                                        <?php echo ($row['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>
                                                        Cash
                                                    </option>

                                                    <option value="GCash"
                                                        <?php echo ($row['payment_method'] ?? '') === 'GCash' ? 'selected' : ''; ?>>
                                                        GCash
                                                    </option>

                                                    <option value="Bank Transfer"
                                                        <?php echo ($row['payment_method'] ?? '') === 'Bank Transfer' ? 'selected' : ''; ?>>
                                                        Bank Transfer
                                                    </option>

                                                </select>

                                            </div>

                                            <div class="col-md-4">

                                                <label class="form-label fw-bold">
                                                    Payment Status
                                                </label>

                                                <select name="status"
                                                        class="form-select"
                                                        required>

                                                    <option value="unpaid"
                                                        <?php echo ($row['payment_status'] ?? 'unpaid') === 'unpaid' ? 'selected' : ''; ?>>
                                                        Unpaid
                                                    </option>

                                                    <option value="partial"
                                                        <?php echo ($row['payment_status'] ?? '') === 'partial' ? 'selected' : ''; ?>>
                                                        Partial
                                                    </option>

                                                    <option value="paid"
                                                        <?php echo ($row['payment_status'] ?? '') === 'paid' ? 'selected' : ''; ?>>
                                                        Paid
                                                    </option>

                                                </select>

                                            </div>

                                            <div class="col-12">

                                                <button type="submit"
                                                        class="btn btn-success">
                                                    Save Payment
                                                </button>

                                            </div>

                                        </div>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-4">
                                No rental records found.
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
