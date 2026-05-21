<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    
    $result = $conn->query("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.username = '$username' AND u.password_hash = '$password' AND u.is_active = 1
    ");
    
    if ($user = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role_name'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Неверный логин или пароль";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Вход - Ателье</title>
    <style>
        body {
            font-family: Arial;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-form {
            width: 350px;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }
        h2 { margin-bottom: 30px; color: #333; }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        .error { color: red; margin-bottom: 15px; }
        .info {
            margin-top: 20px;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>🏪 Ателье</h2>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Логин" value="admin" required>
            <input type="password" name="password" placeholder="Пароль" value="admin" required>
            <button type="submit">Войти</button>
        </form>
        <div class="info">
            <strong>Данные для входа:</strong><br>
            Логин: <strong>admin</strong><br>
            Пароль: <strong>admin</strong>
        </div>
    </div>
</body>
</html>