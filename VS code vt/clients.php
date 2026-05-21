<?php
require_once 'config.php';
checkAuth();

// Добавление клиента
if ($_POST['action'] === 'add') {
    $stmt = $conn->prepare("INSERT INTO clients (last_name, first_name, middle_name, phone, email, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $_POST['last_name'], $_POST['first_name'], $_POST['middle_name'], $_POST['phone'], $_POST['email'], $_POST['address']);
    $stmt->execute();
    header("Location: clients.php");
    exit;
}

// Удаление клиента
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: clients.php");
    exit;
}

$clients = $conn->query("SELECT * FROM clients ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Клиенты - Ателье</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn { display: inline-block; padding: 8px 15px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn-danger { background: #dc3545; }
        .add-form { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .add-form input { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 5px; }
        .back-btn { background: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>👥 Клиенты</h1>
        <a href="index.php" class="btn back-btn">← На главную</a>
        
        <div class="add-form">
            <h3>➕ Добавить клиента</h3>
            <form method="post">
                <input type="hidden" name="action" value="add">
                <input type="text" name="last_name" placeholder="Фамилия" required>
                <input type="text" name="first_name" placeholder="Имя" required>
                <input type="text" name="middle_name" placeholder="Отчество">
                <input type="text" name="phone" placeholder="Телефон" required>
                <input type="email" name="email" placeholder="Email">
                <input type="text" name="address" placeholder="Адрес">
                <button type="submit" class="btn">Добавить</button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr><th>ID</th><th>ФИО</th><th>Телефон</th><th>Email</th><th>Адрес</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php while ($row = $clients->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['last_name'] ?> <?= $row['first_name'] ?> <?= $row['middle_name'] ?></td>
                    <td><?= $row['phone'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['address'] ?></td>
                    <td>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить?')">🗑️</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>