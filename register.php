
<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/auth.php";

if (isset($_SESSION['user_id'])) {
    header("Location: customer/dashboard.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check whether email already exists
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            // Hash password before saving
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Public registration always creates a customer
            $role = "customer";

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $hashed_password,
                $role
            );

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Register | MotoRent</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">

            <div class="card shadow border-0">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <h2 class="fw-bold">
                            <span class="text-warning">Moto</span>Rent
                        </h2>
                        <p class="text-secondary">
                            Create your customer account
                        </p>
                    </div>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger">
                            <?php echo e($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success !== ''): ?>
                        <div class="alert alert-success">
                            <?php echo e($success); ?>
                            <a href="login.php">Log in here</a>.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">

                        <input type="hidden" name="csrf_token"
                               value="<?php echo e(csrf_token()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   maxlength="100"
                                   required
                                   value="<?php echo e($_POST['name'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   maxlength="150"
                                   required
                                   value="<?php echo e($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   minlength="8"
                                   required>
                            <small class="text-secondary">
                                Use at least 8 characters.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password"
                                   name="confirm_password"
                                   class="form-control"
                                   minlength="8"
                                   required>
                        </div>

                        <button type="submit"
                                class="btn btn-warning w-100 fw-bold">
                            Create Account
                        </button>

                    </form>

                    <p class="text-center mt-4 mb-0">
                        Already have an account?
                        <a href="login.php">Login</a>
                    </p>

                    <p class="text-center mt-3 mb-0">
                        <a href="index.php" class="text-secondary">
                            Back to Homepage
                        </a>
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
