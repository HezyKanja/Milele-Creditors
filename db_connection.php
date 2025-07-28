<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "milele_db"; // Change to your actual DB name

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
