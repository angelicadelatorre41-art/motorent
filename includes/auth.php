
<?php
// Start a secure session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => false, // Local XAMPP uses HTTP
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Protect pages that require an account
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /motorent/login.php");
        exit;
    }
}

// Protect pages by account role
function require_role($roles) {
    require_login();

    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        exit("Access denied. You do not have permission to view this page.");
    }
}

// Create a CSRF token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Verify submitted CSRF token
function verify_csrf() {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submitted) ||
        !is_string($stored) ||
        $stored === '' ||
        !hash_equals($stored, $submitted)
    ) {
        http_response_code(403);
        exit("Invalid form request. Please go back and try again.");
    }
}

// Escape text before displaying it in HTML
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
