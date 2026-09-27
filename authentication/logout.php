<?php
// 1. Initialize access to the active session data layer
session_start();

// 2. Unset all internal session state context arrays 
$_SESSION = array();

// 3. Destroy the session cookie on the user client web browser if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Wipe session storage data on the server completely
session_destroy();

// 5. Redirect cleanly back to the authentication portal login form view
header("Location: login.php");
exit;
?>
