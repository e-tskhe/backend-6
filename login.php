<?php
/**
 * Файл login.php для не авторизованного пользователя выводит форму логина.
 * При отправке формы проверяет логин/пароль и создает сессию,
 * записывает в нее логин и id пользователя.
 * После авторизации пользователь перенаправляется на главную страницу
 * для изменения ранее введенных данных.
 **/

require_once 'db.php';

// Отправляем браузеру правильную кодировку,
// файл login.php должен быть в кодировке UTF-8 без BOM.

header('Content-Type: text/html; charset=UTF-8');

session_start();

if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['login'])) {
        $error = 'Введите логин';
    }
    elseif (empty($_POST['password'])) {
        $error = 'Введите пароль';
    }
    else {
        try {
            $pdo = getDBConnection();

            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && md5($_POST['password']) === $user['password_hash']) {
                // Если все ок, то авторизуем пользователя.
                $_SESSION['login'] = $_POST['username'];
                // Записываем ID пользователя.
                $_SESSION['uid'] = $user['id'];

                if ($user['username'] === 'admin') {
                    $_SESSION['is_admin'] = true;
                    header('Location: admin.php');
                } else {
                    $_SESSION['is_admin'] = false;
                    header('Location: index.php');
                }
                exit();
            } else {
                // Неверный логин/пароль
                $error = "Неверный логин или пароль";
            }
        } catch (PDOException $e) {
            $error = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-section">
        <form method="POST" class="auth-form">
            <h3>Вход в систему</h3>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" required 
                       value="<?= !empty($_POST['login']) ? htmlspecialchars($_POST['login']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="auth-btn">Войти</button>
            
            <?php if (!empty($_COOKIE['login']) && !empty($_COOKIE['password'])): ?>
                <div class="auth-hint">
                    Ваши данные для входа:<br>
                    Логин: <?= htmlspecialchars($_COOKIE['login']) ?><br>
                    Пароль: <?= htmlspecialchars($_COOKIE['password']) ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>