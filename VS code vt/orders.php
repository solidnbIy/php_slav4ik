<?php
require_once 'config.php';
checkAuth();

$message = '';
$error = '';

// ============================================
// ОБРАБОТКА СОЗДАНИЯ ЗАКАЗА
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $client_id = intval($_POST['client_id']);
    $description = trim($_POST['description']);
    $total_amount = floatval($_POST['total_amount']);
    $discount_amount = floatval($_POST['discount_amount']);
    $final_amount = $total_amount - $discount_amount;
    if ($final_amount < 0) $final_amount = 0;
    
    $planned_end_date = $_POST['planned_end_date'];
    $order_date = date('Y-m-d');
    $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    $status_id = 1;
    $created_by = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("INSERT INTO orders (order_number, client_id, created_by_user_id, order_date, planned_end_date, status_id, total_amount, discount_amount, final_amount, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siissiddds", $order_number, $client_id, $created_by, $order_date, $planned_end_date, $status_id, $total_amount, $discount_amount, $final_amount, $description);
    
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        $message = '<div class="alert-success">✅ Заказ #' . $order_number . ' успешно создан! ID: ' . $order_id . '</div>';
        
        // Добавляем услуги
        if (!empty($_POST['services'])) {
            foreach ($_POST['services'] as $service_data) {
                $service_id = $service_data['id'];
                $price = floatval($service_data['price']);
                $quantity = intval($service_data['quantity']);
                $employee_id = intval($service_data['employee_id']);
                
                $stmt2 = $conn->prepare("INSERT INTO order_services (order_id, service_id, employee_id, price_at_moment, quantity) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("iiidi", $order_id, $service_id, $employee_id, $price, $quantity);
                $stmt2->execute();
            }
        }
        
        // Добавляем материалы
        if (!empty($_POST['materials'])) {
            foreach ($_POST['materials'] as $material_data) {
                $material_id = $material_data['id'];
                $price = floatval($material_data['price']);
                $quantity = floatval($material_data['quantity']);
                
                $stmt3 = $conn->prepare("INSERT INTO order_materials (order_id, material_id, price_at_moment, quantity) VALUES (?, ?, ?, ?)");
                $stmt3->bind_param("iidd", $order_id, $material_id, $price, $quantity);
                $stmt3->execute();
            }
        }
        
    } else {
        $error = '<div class="alert-error">❌ Ошибка: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// ============================================
// ОБНОВЛЕНИЕ СТАТУСА ЗАКАЗА
// ============================================
if (isset($_GET['update_status']) && isset($_GET['status'])) {
    $order_id = intval($_GET['update_status']);
    $status_id = intval($_GET['status']);
    
    // Если статус "Выдан" - ставим дату фактического завершения
    if ($status_id == 4) {
        $conn->query("UPDATE orders SET status_id = $status_id, actual_end_date = CURDATE() WHERE id = $order_id");
    } else {
        $conn->query("UPDATE orders SET status_id = $status_id WHERE id = $order_id");
    }
    header("Location: orders.php");
    exit;
}

// ============================================
// УДАЛЕНИЕ ЗАКАЗА
// ============================================
if (isset($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    $conn->query("DELETE FROM orders WHERE id = $order_id");
    header("Location: orders.php");
    exit;
}

// ============================================
// ПОЛУЧЕНИЕ ДАННЫХ ДЛЯ ВЫВОДА
// ============================================
$orders = $conn->query("
    SELECT o.*, c.last_name, c.first_name, c.middle_name, c.phone, os.name as status_name 
    FROM orders o 
    JOIN clients c ON o.client_id = c.id 
    JOIN order_statuses os ON o.status_id = os.id 
    ORDER BY o.id DESC
");

$clients = $conn->query("SELECT id, last_name, first_name, middle_name, phone, email FROM clients ORDER BY last_name");
$statuses = $conn->query("SELECT * FROM order_statuses");
$services = $conn->query("SELECT s.*, sc.name as category_name FROM services s JOIN service_categories sc ON s.category_id = sc.id");
$materials = $conn->query("SELECT m.*, mu.name as unit_name FROM materials m JOIN material_units mu ON m.unit_id = mu.id");
$employees = $conn->query("SELECT id, last_name, first_name, middle_name, position_id FROM employees WHERE is_active = 1");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Заказы - Ателье</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial; padding: 20px; background: #f0f2f5; margin: 0; }
        .container { max-width: 1400px; margin: 0 auto; }
        
        h1, h2, h3 { color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .btn:hover { opacity: 0.85; transform: translateY(-1px); }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .btn-primary { background: #667eea; }
        .btn-back { background: #6c757d; }
        .btn-info { background: #17a2b8; }
        
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        
        .form-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #555; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        
        .section-title { font-size: 18px; font-weight: 600; margin: 20px 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid #667eea; color: #667eea; }
        
        .service-item, .material-item { background: #f8f9fa; padding: 12px; margin-bottom: 10px; border-radius: 8px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .service-item select, .material-item select, .service-item input, .material-item input { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .service-item select { min-width: 150px; }
        .service-item input { width: 100px; }
        .remove-btn { background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .add-btn { background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-1 { background: #fff3cd; color: #856404; }
        .status-2 { background: #cce5ff; color: #004085; }
        .status-3 { background: #d4edda; color: #155724; }
        .status-4 { background: #d1ecf1; color: #0c5460; }
        .status-5 { background: #f8d7da; color: #721c24; }
        
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        select.status-select { padding: 6px; border-radius: 5px; border: 1px solid #ddd; }
        
        .order-total { font-weight: bold; font-size: 16px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Управление заказами</h1>
            <a href="index.php" class="btn btn-back">← На главную</a>
        </div>
        
        <?= $message ?>
        <?= $error ?>
        
        <!-- ФОРМА СОЗДАНИЯ ЗАКАЗА -->
        <div class="form-card">
            <h2>➕ Создать новый заказ</h2>
            <form method="post" id="orderForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>👤 Клиент *</label>
                        <select name="client_id" id="client_id" required>
                            <option value="">-- Выберите клиента --</option>
                            <?php while($client = $clients->fetch_assoc()): ?>
                                <option value="<?= $client['id'] ?>">
                                    <?= htmlspecialchars($client['last_name'] . ' ' . $client['first_name'] . ' ' . ($client['middle_name'] ?? '')) ?> 
                                    (тел: <?= $client['phone'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📅 Планируемая дата завершения *</label>
                        <input type="date" name="planned_end_date" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>💰 Общая стоимость (₽)</label>
                        <input type="number" name="total_amount" id="total_amount" step="0.01" value="0" onchange="calculateFinal()">
                    </div>
                    
                    <div class="form-group">
                        <label>🏷️ Скидка (₽)</label>
                        <input type="number" name="discount_amount" id="discount_amount" step="0.01" value="0" onchange="calculateFinal()">
                    </div>
                    
                    <div class="form-group">
                        <label>💵 Итоговая сумма (₽)</label>
                        <input type="text" id="final_amount_display" readonly style="background:#e9ecef; font-weight:bold; color:#28a745;">
                        <input type="hidden" name="final_amount" id="final_amount_hidden">
                    </div>
                    
                    <div class="form-group">
                        <label>📝 Описание заказа</label>
                        <textarea name="description" rows="2" placeholder="Что нужно сшить/отремонтировать? Укажите детали..."></textarea>
                    </div>
                </div>
                
                <!-- УСЛУГИ -->
                <div class="section-title">🧵 Услуги</div>
                <div id="servicesContainer">
                    <div class="service-item">
                        <select name="services[0][id]" style="min-width:200px;">
                            <option value="">Выберите услугу</option>
                            <?php 
                            $services->data_seek(0);
                            while($service = $services->fetch_assoc()): ?>
                                <option value="<?= $service['id'] ?>" data-price="<?= $service['base_price'] ?>">
                                    <?= htmlspecialchars($service['name']) ?> (<?= number_format($service['base_price'], 2) ?> ₽)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <input type="number" name="services[0][price]" placeholder="Цена" step="0.01" value="0" style="width:120px;">
                        <input type="number" name="services[0][quantity]" placeholder="Кол-во" value="1" style="width:80px;">
                        <select name="services[0][employee_id]" style="min-width:150px;">
                            <option value="">Исполнитель</option>
                            <?php 
                            $employees->data_seek(0);
                            while($emp = $employees->fetch_assoc()): ?>
                                <option value="<?= $emp['id'] ?>"><?= $emp['last_name'] ?> <?= $emp['first_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">✖</button>
                    </div>
                </div>
                <button type="button" class="add-btn" onclick="addService()">+ Добавить услугу</button>
                
                <!-- МАТЕРИАЛЫ -->
                <div class="section-title">🧶 Материалы</div>
                <div id="materialsContainer">
                    <div class="material-item">
                        <select name="materials[0][id]" style="min-width:200px;">
                            <option value="">Выберите материал</option>
                            <?php 
                            $materials->data_seek(0);
                            while($material = $materials->fetch_assoc()): ?>
                                <option value="<?= $material['id'] ?>" data-price="<?= $material['price'] ?>" data-unit="<?= $material['unit_name'] ?>">
                                    <?= htmlspecialchars($material['name']) ?> (<?= number_format($material['price'], 2) ?> ₽/<?= $material['unit_name'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <input type="number" name="materials[0][price]" placeholder="Цена" step="0.01" value="0" style="width:120px;">
                        <input type="number" name="materials[0][quantity]" placeholder="Кол-во" step="0.01" value="1" style="width:100px;">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">✖</button>
                    </div>
                </div>
                <button type="button" class="add-btn" onclick="addMaterial()">+ Добавить материал</button>
                
                <div style="margin-top: 25px; text-align: right;">
                    <button type="submit" name="create_order" class="btn btn-success" style="padding: 12px 30px; font-size: 16px;">✅ Создать заказ</button>
                </div>
            </form>
        </div>
        
        <!-- СПИСОК ЗАКАЗОВ -->
        <h2>📋 Список заказов</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>№ заказа</th><th>Клиент</th><th>Телефон</th><th>Дата</th><th>Срок</th><th>Статус</th><th>Сумма</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php if ($orders && $orders->num_rows > 0): ?>
                    <?php while($row = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><strong><?= htmlspecialchars($row['order_number']) ?></strong></td>
                        <td><?= htmlspecialchars($row['last_name'] . ' ' . $row['first_name']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= date('d.m.Y', strtotime($row['order_date'])) ?></td>
                        <td><?= $row['planned_end_date'] ? date('d.m.Y', strtotime($row['planned_end_date'])) : '—' ?></td>
                        <td><span class="status-badge status-<?= $row['status_id'] ?>"><?= htmlspecialchars($row['status_name']) ?></span></td>
                        <td class="order-total"><?= number_format($row['final_amount'], 2) ?> ₽</td>
                        <td class="action-buttons">
                            <form method="get" style="display:inline;">
                                <input type="hidden" name="update_status" value="<?= $row['id'] ?>">
                                <select name="status" class="status-select" onchange="this.form.submit()">
                                    <option value="">Изменить статус</option>
                                    <?php 
                                    $statuses_current = $conn->query("SELECT * FROM order_statuses");
                                    while($status = $statuses_current->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $status['id'] ?>" <?= $status['id'] == $row['status_id'] ? 'disabled' : '' ?>>
                                            → <?= $status['name'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </form>
                            <a href="order_details.php?id=<?= $row['id'] ?>" class="btn btn-info" style="background:#17a2b8;">🔍 Детали</a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить заказ?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center; padding:40px;">📭 Нет заказов. Создайте первый заказ!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        let serviceIndex = 1;
        let materialIndex = 1;
        
        function addService() {
            const container = document.getElementById('servicesContainer');
            const newDiv = document.createElement('div');
            newDiv.className = 'service-item';
            newDiv.innerHTML = `
                <select name="services[${serviceIndex}][id]" style="min-width:200px;">
                    <option value="">Выберите услугу</option>
                    <?php 
                    $services2 = $conn->query("SELECT s.*, sc.name as category_name FROM services s JOIN service_categories sc ON s.category_id = sc.id");
                    while($service = $services2->fetch_assoc()): ?>
                        <option value="<?= $service['id'] ?>" data-price="<?= $service['base_price'] ?>"><?= htmlspecialchars($service['name']) ?> (<?= number_format($service['base_price'], 2) ?> ₽)</option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="services[${serviceIndex}][price]" placeholder="Цена" step="0.01" value="0" style="width:120px;">
                <input type="number" name="services[${serviceIndex}][quantity]" placeholder="Кол-во" value="1" style="width:80px;">
                <select name="services[${serviceIndex}][employee_id]" style="min-width:150px;">
                    <option value="">Исполнитель</option>
                    <?php 
                    $employees2 = $conn->query("SELECT id, last_name, first_name FROM employees WHERE is_active = 1");
                    while($emp = $employees2->fetch_assoc()): ?>
                        <option value="<?= $emp['id'] ?>"><?= $emp['last_name'] ?> <?= $emp['first_name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">✖</button>
            `;
            container.appendChild(newDiv);
            serviceIndex++;
        }
        
        function addMaterial() {
            const container = document.getElementById('materialsContainer');
            const newDiv = document.createElement('div');
            newDiv.className = 'material-item';
            newDiv.innerHTML = `
                <select name="materials[${materialIndex}][id]" style="min-width:200px;">
                    <option value="">Выберите материал</option>
                    <?php 
                    $materials2 = $conn->query("SELECT m.*, mu.name as unit_name FROM materials m JOIN material_units mu ON m.unit_id = mu.id");
                    while($material = $materials2->fetch_assoc()): ?>
                        <option value="<?= $material['id'] ?>" data-price="<?= $material['price'] ?>"><?= htmlspecialchars($material['name']) ?> (<?= number_format($material['price'], 2) ?> ₽)</option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="materials[${materialIndex}][price]" placeholder="Цена" step="0.01" value="0" style="width:120px;">
                <input type="number" name="materials[${materialIndex}][quantity]" placeholder="Кол-во" step="0.01" value="1" style="width:100px;">
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">✖</button>
            `;
            container.appendChild(newDiv);
            materialIndex++;
        }
        
        function calculateFinal() {
            const total = parseFloat(document.getElementById('total_amount').value) || 0;
            const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
            const final = total - discount;
            document.getElementById('final_amount_display').value = final.toFixed(2) + ' ₽';
            document.getElementById('final_amount_hidden').value = final.toFixed(2);
        }
        
        calculateFinal();
    </script>
</body>
</html>