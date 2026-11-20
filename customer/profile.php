
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['customer']);

$userId = (int) $_SESSION['user_id'];
$success = '';
$error = '';

// Get current customer information
$stmt = $conn->prepare(
    "SELECT name, email, password FROM users WHERE id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    exit("Customer account not found.");
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid name and email address.";
        } else {
            $stmt = $conn->prepare(
                "SELECT id FROM users WHERE email = ? AND id != ?"
            );
            $stmt->bind_param("si", $email, $userId);
            $stmt->execute();
            $duplicate = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($duplicate) {
                $error = "That email address is already being used.";
            } else {
                $stmt = $conn->prepare(
                    "UPDATE users SET name = ?, email = ? WHERE id = ?"
                );
                $stmt->bind_param("ssi", $name, $email, $userId);

                if ($stmt->execute()) {
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;

                    $success = "Profile updated successfully.";
                    $user['name'] = $name;
                    $user['email'] = $email;
                } else {
                    $error = "Unable to update your profile.";
                }

                $stmt->close();
            }
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $user['password'])) {
            $error = "Your current password is incorrect.";
        } elseif (strlen($newPassword) < 8) {
            $error = "Your new password must have at least 8 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "The new passwords do not match.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );
            $stmt->bind_param("si", $hashedPassword, $userId);

            if ($stmt->execute()) {
                $success = "Password changed successfully.";
                $user['password'] = $hashedPassword;
            } else {
                $error = "Unable to change your password.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>My Profile | MotoRent</title>

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

        .profile-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }

        .profile-icon {
            font-size: 55px;
            color: #0d6efd;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-bicycle"></i> MotoRent
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

    <div class="mb-4">
        <h2 class="fw-bold">My Profile</h2>
        <p class="text-muted">View and manage your customer account.</p>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Profile Information -->
        <div class="col-lg-6">
            <div class="profile-card h-100">

                <div class="text-center mb-4">
                    <i class="bi bi-person-circle profile-icon"></i>
                    <h4 class="mt-2">Account Information</h4>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token"
                           value="<?= e(csrf_token()) ?>">

                    <input type="hidden" name="action"
                           value="update_profile">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name"
                               class="form-control"
                               value="<?= e($user['name']) ?>"
                               maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email"
                               class="form-control"
                               value="<?= e($user['email']) ?>"
                               maxlength="100" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Save Profile
                    </button>
                </form>

            </div>
        </div>

        <!-- Change Password -->
        <div class="col-lg-6">
            <div class="profile-card h-100">

                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock profile-icon"></i>
                    <h4 class="mt-2">Change Password</h4>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token"
                           value="<?= e(csrf_token()) ?>">

                    <input type="hidden" name="action"
                           value="change_password">

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password"
                               class="form-control" minlength="8" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password"
                               class="form-control" minlength="8" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-key"></i> Change Password
                    </button>
                </form>

            </div>
        </div>

    </div>

</div>

</body>
</html>
