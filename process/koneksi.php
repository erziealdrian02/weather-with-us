<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "weather_gw";

// Create connection
$connect = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}
