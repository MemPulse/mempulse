<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Check if the Terms checkbox was checked
    if (!isset($_POST['terms'])) {
        die("You must agree to the Terms & Conditions.");
    }

    // 2. Grab inputs
    $first_name = trim($_POST['first-name']); 
    $last_name  = trim($_POST['last-name']);  
    $email      = trim($_POST['email']);
    $password   = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    // 3. Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        die("All required fields must be filled out.");
    }

    if (strlen($password) < 8) {
        die("Password must be at least 8 characters long.");
    }

    if (!hash_equals($password, $confirm_password)) {
        die("The passwords do not match. Please go back and try again.");
    }

    // --- NEW EMAIL VALIDATION START --- //
    
    // Check if the email format is perfectly valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Error: Invalid email format.");
    }

    // Extract the domain (everything after the @ symbol)
    $domain = substr(strrchr($email, "@"), 1);

    // Check if the domain has a valid Mail Exchange (MX) record
    if (!checkdnsrr($domain, "MX")) {
        die("Error: The email domain does not exist or cannot receive mail.");
    }
    
    // --- NEW EMAIL VALIDATION END --- //


    // 4. Check for existing email (Prepare -> Bind -> Execute)
    $check_sql  = "SELECT id FROM users WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    
    mysqli_stmt_bind_param($check_stmt, "s", $email); // "s" means 1 string parameter
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt); // Required to get row count in MySQLi
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        die("Error: This email is already registered.");
    }
    mysqli_stmt_close($check_stmt);

    // 5. Securely hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // 6. Insert new user (Prepare -> Bind -> Execute)
    $insert_sql  = "INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    
    // "ssss" means 4 string parameters matching the 4 variables
    mysqli_stmt_bind_param($insert_stmt, "ssss", $first_name, $last_name, $email, $hashed_password);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $new_user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($insert_stmt);
        mysqli_close($conn);

        // Demo-only confirmation: the next page offers a temporary button to simulate approval.
        session_start();
        $_SESSION['demo_pending_user_id'] = $new_user_id;
        $_SESSION['demo_approval_token'] = bin2hex(random_bytes(32));
        header("Location: registration-pending.php");
        exit;
    } else {
        die("System error occurred during registration.");
    }

}
?>