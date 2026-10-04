<?php
//Connection credentials
$host = "localhost";
$user = "root";
$password = "";
$database = "mempulse_db";

//Connection object
$conn = mysqli_connect($host, $user, $password, $database);

//Checking connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

//Defining base url
if (!defined('BASE_URL')) {
    define('BASE_URL', '/mempulse/app/');
}
?>