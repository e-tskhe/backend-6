<?php
function getDBConnection() {
    $user = 'u68891';
    $pass = '3849293';
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=u68891', $user, $pass, [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        return $pdo;
    } catch (PDOException $e) {
        die('Ошибка базы данных: ' . $e->getMessage());
    }
}
?>