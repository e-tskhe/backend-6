<?php
function getDBConnection() {
    $user = 'u68891';
    $pass = '3849293';
    try {
        $db = new PDO('mysql:host=localhost;dbname=u68891', $user, $pass, [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        // Создаем администратора, если его нет
        $adminCheck = $db->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
        if (!$adminCheck) {
            $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            $stmt->execute(['admin', md5('admin123')]);
        }
        return $db;
    } catch (PDOException $e) {
        die('Ошибка базы данных: ' . $e->getMessage());
    }
}
?>