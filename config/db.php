<?php
//Step 1: Define connection credentials
$host = "localhost";
$user = "root";
$password = "";
$database = "mempulse_db";

//Step 2: Create connection object
$conn = mysqli_connect($host, $user, $password, $database);

//step 3: check for connection errors
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully!";
?>