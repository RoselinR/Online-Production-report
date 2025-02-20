<?php
// Database connection using PDO
$host = "localhost";
$dbname = "trg"; // Replace with your MySQL database name
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;port=3377;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>