<?php
$host = getenv('MEMPULSE_DB_HOST') ?: 'localhost';
$user = getenv('MEMPULSE_DB_USER') ?: 'root';
$password = getenv('MEMPULSE_DB_PASSWORD') ?: '';
$database = getenv('MEMPULSE_DB_NAME') ?: 'mempulse_db';

//Connection object
$conn = mysqli_connect($host, $user, $password, $database);

//Checking connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

//Defining base url
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('MEMPULSE_APP_BASE_URL') ?: '/mempulse/app/');
}
?>