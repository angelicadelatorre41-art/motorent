
<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/auth.php";

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'customer') {
        header("Location: customer/dashboard.php");
    } else {
        header("Location: admin/dashboard.php");
    }
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {

            // Create a new session ID after login
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Send customer and staff/admin to their dashboards
            if ($user['role'] === 'customer') {
                header("Location: customer/dashboard.php");
            } else {
                header("Location: admin/dashboard.php");
            }

            exit;

        } else {
            $error = "Incorrect email or password.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login | MotoRent</title>

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
                            Login to your account
                        </p>
                    </div>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger">
                            <?php echo e($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">

                        <input type="hidden" name="csrf_token"
                               value="<?php echo e(csrf_token()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   required
                                   value="<?php echo e($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   required>
                        </div>

                        <button type="submit"
                                class="btn btn-warning w-100 fw-bold">
                            Login
                        </button>

                    </form>

                    <p class="text-center mt-4 mb-0">
                        Don't have an account?
                        <a href="register.php">Register here</a>
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
