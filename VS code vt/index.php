<?php
require_once 'config.php';
checkAuth();

$clients_count = $conn->query("SELECT COUNT(*) FROM clients")->fetch_row()[0];
$orders_count = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$employees_count = $conn->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetch_row()[0];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ателье - Главная</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f5f7fa; }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .stat-card {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
        }
        .menu {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 0 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .menu-item {
            background: white;
            padding: 40px 20px;
            text-align: center;
            text-decoration: none;
            color: #333;
            border-radius: 10px;
            transition: 0.3s;
            font-size: 18px;
            font-weight: bold;
        }
        .menu-item:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏪 Ателье</h1>
        <div>
            <span>👋 <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
            <a href="logout.php" class="logout-btn">🚪 Выйти</a>
        </div>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $clients_count ?></div>
            <div>Клиентов</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $orders_count ?></div>
            <div>Заказов</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $employees_count ?></div>
            <div>Сотрудников</div>
        </div>
    </div>
    
    <div class="menu">
        <a href="clients.php" class="menu-item">👥 Клиенты</a>
        <a href="orders.php" class="menu-item">📦 Заказы</a>
        <a href="employees.php" class="menu-item">👔 Сотрудники</a>
    </div>
</body>
</html>