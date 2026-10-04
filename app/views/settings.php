<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$user_id = $_SESSION['user_id'];

$pageTitle = "Settings"; 
$currentPage = "settings";
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="pt-16 pl-0 md:pl-72 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/../includes/header-data.php'; ?>    
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <!-- Page-specific content goes here -->
    </main>

</body>
</html>
