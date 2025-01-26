<?php

$dsn = "mysql:host=127.0.0.1;dbname=whois;charset=utf8mb4";
$username = "root";
$password = "root";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>