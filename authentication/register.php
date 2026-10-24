<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['terms'])) {
        die("You must agree to the Terms & Conditions.");
    }

    $first_name       = trim($_POST['first-name'] ?? '');
    $last_name        = trim($_POST['last-name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        die("All required fields must be filled out.");
    }

    if (strlen($password) < 8) {
        die("Password must be at least 8 characters long.");
    }

    if (!hash_equals($password, $confirm_password)) {
        die("The passwords do not match. Please go back and try again.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Error: Invalid email format.");
    }

    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, "MX")) {
        die("Error: The email domain does not exist or cannot receive mail.");
    }

    $check_sql  = "SELECT id FROM users WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        die("Error: This email is already registered.");
    }
    mysqli_stmt_close($check_stmt);

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $initial_status  = 'pending';

    $insert_sql  = "INSERT INTO users (first_name, last_name, email, password_hash, account_status) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "sssss", $first_name, $last_name, $email, $hashed_password, $initial_status);

    if (mysqli_stmt_execute($insert_stmt)) {
        $new_user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($insert_stmt);

        // Initialize default SRS user_settings row
        $settings_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO user_settings (user_id) VALUES (?)");
        if ($settings_stmt) {
            mysqli_stmt_bind_param($settings_stmt, "i", $new_user_id);
            mysqli_stmt_execute($settings_stmt);
            mysqli_stmt_close($settings_stmt);
        }

        mysqli_close($conn);

        session_start();
        $_SESSION['demo_pending_user_id'] = $new_user_id;
        $_SESSION['demo_approval_token']  = bin2hex(random_bytes(32));
        header("Location: registration-pending.php");
        exit;
    } else {
        die("System error occurred during registration.");
    }
}
?>