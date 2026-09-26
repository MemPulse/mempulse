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
    $password   = $_POST['password'];

    // 3. Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        die("All required fields must be filled out.");
    }

    if (strlen($password) < 8) {
        die("Password must be at least 8 characters long.");
    }

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
    mysqli_stmt_close($insert_stmt);
    mysqli_close($conn);
      // 7. Redirect smoothly to index.php upon success
        header("Location: ../app/index.php");
        exit;
    } else {
        die("System error occurred during registration.");
    }

}
?>
