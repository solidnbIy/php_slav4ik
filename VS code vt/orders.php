<?php
require_once 'config.php';
checkAuth();

// Добавление заказа
if ($_POST['action'] === 'add') {
    $order_number = 'ORD-' . date('Ymd') . '-' . rand(100, 999);
    $stmt = $conn->prepare("INSERT INTO orders (order_number, client_id, created_by_user_id, order_date, status_id, total_amount, final_amount) VALUES (?, ?, ?, CURDATE(), 1, 0, 0)");
    $stmt->bind_param("sii", $order_number, $_POST['client_id'], $_SESSION['user_id']);
    $stmt->execute();
    header("Location: orders.php");
    exit;
}

// Обновление статуса
if ($_GET['update_status']) {
    $stmt = $conn->prepare("UPDATE orders SET status_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $_GET['status'], $_GET['update_status']);
    $stmt->execute();
    header("Location: orders.php");
    exit;
}

// Удаление заказа
if ($_GET['delete']) {
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: orders.php");
    exit;
}

$orders = $conn->query("
    SELECT o.*, c.last_name, c.first_name, c.phone, os.name as status_name 
    FROM orders o 
    JOIN clients c ON o.client_id = c.id 
    JOIN order_statuses os ON o.status_id = os.id 
    ORDER BY o.id DESC
");

$clients = $conn->query("SELECT id, last_name, first_name, phone FROM clients");
$statuses = $conn->query("SELECT * FROM order_statuses");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Заказы - Ателье</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn { display: inline-block; padding: 8px 15px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .add-form { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .add-form select, .add-form input { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 5px; }
        .status-new { color: #ff9800; font-weight: bold; }
        .status-work { color: #2196f3; font-weight: bold; }
        .status-ready { color: #4caf50; font-weight: bold; }
        .status-done { color: #9e9e9e; }
        .back-btn { background: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Заказы</h1>
        <a href="index.php" class="btn back-btn">← На главную</a>
        
        <div class="add-form">
            <h3>➕ Новый заказ</h3>
            <form method="post">
                <input type="hidden" name="action" value="add">
                <select name="client_id" required>
                    <option value="">Выберите клиента</option>
                    <?php while($client = $clients->fetch_assoc()): ?>
                        <option value="<?= $client['id'] ?>"><?= $client['last_name'] ?> <?= $client['first_name'] ?> (<?= $client['phone'] ?>)</option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn">Создать заказ</button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr><th>№ заказа</th><th>Клиент</th><th>Телефон</th><th>Дата</th><th>Статус</th><th>Сумма</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php while($row = $orders->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['order_number'] ?></td>
                    <td><?= $row['last_name'] ?> <?= $row['first_name'] ?></td>
                    <td><?= $row['phone'] ?></td>
                    <td><?= $row['order_date'] ?></td>
                    <td class="status-<?= strtolower(str_replace(' ', '', $row['status_name'])) ?>"><?= $row['status_name'] ?></td>
                    <td><?= number_format($row['final_amount'], 2) ?> ₽</td>
                    <td>
                        <select onchange="if(confirm('Изменить статус?')) location.href='?update_status=<?= $row['id'] ?>&status='+this.value">
                            <option value="">Изменить статус</option>
                            <?php 
                            $statuses2 = $conn->query("SELECT * FROM order_statuses");
                            while($status = $statuses2->fetch_assoc()): ?>
                                <option value="<?= $status['id'] ?>" <?= $status['id'] == $row['status_id'] ? 'selected' : '' ?>><?= $status['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить заказ?')">🗑️</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
