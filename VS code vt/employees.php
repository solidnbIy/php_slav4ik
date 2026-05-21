<?php
require_once 'config.php';
checkAuth();

// Добавление сотрудника
if ($_POST['action'] === 'add') {
    $stmt = $conn->prepare("INSERT INTO employees (last_name, first_name, middle_name, position_id, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $_POST['last_name'], $_POST['first_name'], $_POST['middle_name'], $_POST['position_id'], $_POST['phone']);
    $stmt->execute();
    header("Location: employees.php");
    exit;
}

// Удаление сотрудника
if ($_GET['delete']) {
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: employees.php");
    exit;
}

$employees = $conn->query("
    SELECT e.*, ep.name as position_name 
    FROM employees e 
    JOIN employee_positions ep ON e.position_id = ep.id 
    ORDER BY e.id DESC
");

$positions = $conn->query("SELECT * FROM employee_positions");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Сотрудники - Ателье</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f7fa; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn { display: inline-block; padding: 8px 15px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn-danger { background: #dc3545; }
        .add-form { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .add-form input, .add-form select { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 5px; }
        .back-btn { background: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>👔 Сотрудники</h1>
        <a href="index.php" class="btn back-btn">← На главную</a>
        
        <div class="add-form">
            <h3>➕ Добавить сотрудника</h3>
            <form method="post">
                <input type="hidden" name="action" value="add">
                <input type="text" name="last_name" placeholder="Фамилия" required>
                <input type="text" name="first_name" placeholder="Имя" required>
                <input type="text" name="middle_name" placeholder="Отчество">
                <select name="position_id" required>
                    <option value="">Выберите должность</option>
                    <?php while($pos = $positions->fetch_assoc()): ?>
                        <option value="<?= $pos['id'] ?>"><?= $pos['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="phone" placeholder="Телефон">
                <button type="submit" class="btn">Добавить</button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr><th>ID</th><th>ФИО</th><th>Должность</th><th>Телефон</th><th>Статус</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php while($row = $employees->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['last_name'] ?> <?= $row['first_name'] ?> <?= $row['middle_name'] ?></td>
                    <td><?= $row['position_name'] ?></td>
                    <td><?= $row['phone'] ?></td>
                    <td><?= $row['is_active'] ? '✅ Работает' : '❌ Уволен' ?></td>
                    <td>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить сотрудника?')">🗑️</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>