
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

require_role(['admin', 'staff']);

$message = "";
$error = "";

// ADD OR UPDATE MOTORCYCLE
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {

        $id = (int)($_POST['id'] ?? 0);
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $plate = strtoupper(trim($_POST['plate_number'] ?? ''));
        $price = $_POST['rental_price'] ?? '';
        $status = $_POST['status'] ?? '';
        $image = trim($_POST['image'] ?? '');
        $description = trim($_POST['description'] ?? '');

        $allowed_status = [
            'available',
            'rented',
            'maintenance'
        ];

        if (
            $brand === '' ||
            $model === '' ||
            $plate === '' ||
            !is_numeric($price) ||
            (float)$price <= 0 ||
            !in_array($status, $allowed_status, true)
        ) {
            $error = "Please enter valid motorcycle information.";
        } elseif (strlen($brand) > 100 ||
                  strlen($model) > 100 ||
                  strlen($plate) > 30) {
            $error = "Some fields exceed the allowed length.";
        } elseif ($image !== '' && !preg_match('/^[a-zA-Z0-9_\/.-]+$/', $image)) {
            $error = "Image must be a local file path, such as assets/images/bike.jpg.";
        } else {

            $price = (float)$price;

            if ($id > 0) {

                $stmt = $conn->prepare(
                    "UPDATE motorcycles
                     SET brand=?, model=?, plate_number=?,
                         rental_price=?, status=?, image=?,
                         description=?
                     WHERE id=?"
                );

                $stmt->bind_param(
                    "sssdsssi",
                    $brand,
                    $model,
                    $plate,
                    $price,
                    $status,
                    $image,
                    $description,
                    $id
                );

            } else {

                $stmt = $conn->prepare(
                    "INSERT INTO motorcycles
                    (brand, model, plate_number, rental_price,
                     status, image, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssdsss",
                    $brand,
                    $model,
                    $plate,
                    $price,
                    $status,
                    $image,
                    $description
                );
            }

            try {
                if ($stmt->execute()) {
                    $message = $id > 0
                        ? "Motorcycle updated successfully!"
                        : "Motorcycle added successfully!";
                } else {
                    $error = "Unable to save motorcycle.";
                }
            } catch (mysqli_sql_exception $exception) {
                $error = "The plate number may already exist.";
            }

            $stmt->close();
        }
    }

    // DELETE MOTORCYCLE
    if ($action === 'delete') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare(
            "DELETE FROM motorcycles WHERE id=?"
        );

        $stmt->bind_param("i", $id);

        try {
            if ($stmt->execute()) {
                $message = "Motorcycle deleted successfully!";
            }
        } catch (mysqli_sql_exception $exception) {
            $error = "This motorcycle has reservation records and cannot be deleted.";
        }

        $stmt->close();
    }
}

// GET MOTORCYCLE FOR EDITING
$edit_bike = null;

if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM motorcycles WHERE id=?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $edit_bike = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}

// GET ALL MOTORCYCLES
$result = $conn->query(
    "SELECT * FROM motorcycles ORDER BY id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Motorcycle Management | MotoRent</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand fw-bold">
            MotoRent Admin
        </a>

        <a href="../logout.php" class="btn btn-warning btn-sm">
            Logout
        </a>
    </div>
</nav>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Motorcycle Management</h2>
            <p class="text-secondary">
                Add and manage your motorcycle inventory.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-dark">
            Back to Dashboard
        </a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <!-- MOTORCYCLE FORM -->
    <div class="card shadow-sm border-0 mb-5">

        <div class="card-header bg-warning fw-bold">
            <?php echo $edit_bike ? 'Edit Motorcycle' : 'Add New Motorcycle'; ?>
        </div>

        <div class="card-body p-4">

            <form method="POST" action="motorcycles.php">

                <input type="hidden" name="csrf_token"
                       value="<?php echo e(csrf_token()); ?>">

                <input type="hidden" name="action" value="save">

                <input type="hidden" name="id"
                       value="<?php echo e($edit_bike['id'] ?? ''); ?>">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Motorcycle Brand</label>
                        <input type="text"
                               name="brand"
                               class="form-control"
                               maxlength="100"
                               value="<?php echo e($edit_bike['brand'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Motorcycle Model</label>
                        <input type="text"
                               name="model"
                               class="form-control"
                               maxlength="100"
                               value="<?php echo e($edit_bike['model'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Plate Number</label>
                        <input type="text"
                               name="plate_number"
                               class="form-control"
                               maxlength="30"
                               value="<?php echo e($edit_bike['plate_number'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Rental Price Per Day (₱)</label>
                        <input type="number"
                               name="rental_price"
                               class="form-control"
                               min="0.01"
                               step="0.01"
                               value="<?php echo e($edit_bike['rental_price'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Availability Status</label>

                        <select name="status" class="form-select" required>
                            <?php
                            $current_status = $edit_bike['status'] ?? 'available';
                            ?>

                            <option value="available"
                                <?php echo $current_status === 'available' ? 'selected' : ''; ?>>
                                Available
                            </option>

                            <option value="rented"
                                <?php echo $current_status === 'rented' ? 'selected' : ''; ?>>
                                Rented
                            </option>

                            <option value="maintenance"
                                <?php echo $current_status === 'maintenance' ? 'selected' : ''; ?>>
                                Maintenance
                            </option>

                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Image Path (Optional)</label>
                        <input type="text"
                               name="image"
                               class="form-control"
                               placeholder="assets/images/bike.jpg"
                               value="<?php echo e($edit_bike['image'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description"
                                  class="form-control"
                                  rows="3"><?php echo e($edit_bike['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-warning fw-bold">
                            <?php echo $edit_bike ? 'Update Motorcycle' : 'Add Motorcycle'; ?>
                        </button>

                        <?php if ($edit_bike): ?>
                            <a href="motorcycles.php"
                               class="btn btn-secondary">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <!-- MOTORCYCLE INVENTORY -->
    <div class="card shadow-sm border-0">

        <div class="card-header bg-dark text-white">
            Motorcycle Inventory
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Plate Number</th>
                            <th>Price / Day</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result->num_rows > 0): ?>

                        <?php while ($bike = $result->fetch_assoc()): ?>

                            <tr>
                                <td><?php echo e($bike['id']); ?></td>

                                <td><?php echo e($bike['brand']); ?></td>

                                <td><?php echo e($bike['model']); ?></td>

                                <td><?php echo e($bike['plate_number']); ?></td>

                                <td>
                                    ₱<?php echo number_format(
                                        (float)$bike['rental_price'],
                                        2
                                    ); ?>
                                </td>

                                <td>
                                    <?php if ($bike['status'] === 'available'): ?>
                                        <span class="badge bg-success">
                                            Available
                                        </span>
                                    <?php elseif ($bike['status'] === 'rented'): ?>
                                        <span class="badge bg-primary">
                                            Rented
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            Maintenance
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="motorcycles.php?edit=<?php echo e($bike['id']); ?>"
                                       class="btn btn-primary btn-sm">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="motorcycles.php"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this motorcycle?');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo e(csrf_token()); ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="delete">

                                        <input type="hidden"
                                               name="id"
                                               value="<?php echo e($bike['id']); ?>">

                                        <button type="submit"
                                                class="btn btn-danger btn-sm">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center">
                                No motorcycles found.
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
