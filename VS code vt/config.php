<?php
session_start();

$host = 'localhost';
$user = 'admin';
$password = 'admin';
$database = 'ccbd';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function checkRole($required_role = null) {
    if ($required_role && $_SESSION['role'] !== $required_role && $_SESSION['role'] !== 'director') {
        die("❌ Доступ запрещён");
    }
}
?>