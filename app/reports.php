<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

$pageTitle = "Reports"; 
$currentPage = "reports";
?>
<!DOCTYPE html>
<html lang="en">
    <?php include __DIR__ . '/includes/head.php';?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '\includes\sidebar.php'; ?>

     <main class="pl-72 pt-12 transition-all duration-300 ease-in-out">
        
        <?php include __DIR__ . '\includes\header-data.php'; ?>
        <?php include __DIR__ . '\includes\header.php'; ?>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
