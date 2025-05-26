<?php

/**
 * Задача 6. Реализовать вход администратора с использованием
 * HTTP-авторизации для просмотра и удаления результатов.
 **/
require_once 'db.php';

session_start();
if (empty($_SESSION['login']) || $_SESSION['login'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    die('Доступ запрещен. <a href="login.php">Войти</a>');
}

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM application WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    } elseif (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE application SET name = ?, phone = ?, email = ?, 
                            birthdate = ?, gender = ?, bio = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'], $_POST['phone'], $_POST['email'], 
            $_POST['birthdate'], $_POST['gender'], $_POST['bio'], $_POST['id']
        ]);
        
        $pdo->prepare("DELETE FROM application_language WHERE application_id = ?")
           ->execute([$_POST['id']]);
        
        $langStmt = $pdo->prepare("INSERT INTO programming_language (name) VALUES (?) 
                                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $appLangStmt = $pdo->prepare("INSERT INTO application_language (application_id, language_id) 
                                   VALUES (?, ?)");
        
        foreach ($_POST['languages'] as $langName) {
            $langStmt->execute([$langName]);
            $langId = $pdo->lastInsertId();
            $appLangStmt->execute([$_POST['id'], $langId]);
        }
    }
}


$applications = $pdo->query("
    SELECT a.*, u.username, 
           GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
    FROM application a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN application_language al ON al.application_id = a.id
    LEFT JOIN programming_language pl ON pl.id = al.language_id
    GROUP BY a.id
")->fetchAll(PDO::FETCH_ASSOC);


$languageStats = $pdo->query("
    SELECT pl.name, COUNT(al.application_id) as user_count
    FROM programming_language pl
    LEFT JOIN application_language al ON pl.id = al.language_id
    GROUP BY pl.name
    ORDER BY user_count DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .edit-form {
            display: none;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            margin-top: 10px;
        }
        .stats {
            margin-top: 30px;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Панель Администратора</h1>
        
        <h2>Отправки формы</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Languages</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= htmlspecialchars($app['id']) ?></td>
                    <td><?= htmlspecialchars($app['username']) ?></td>
                    <td><?= htmlspecialchars($app['name']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['languages']) ?></td>
                    <td>
                        <button onclick="toggleEditForm(<?= $app['id'] ?>)">Edit</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $app['id'] ?>">
                            <button type="submit" name="delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr id="edit-form-<?= $app['id'] ?>" class="edit-form">
                    <td colspan="7">
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $app['id'] ?>">
                            
                            <div class="container">
                                <label for="name-<?= $app['id'] ?>">Name:</label>
                                <input type="text" id="name-<?= $app['id'] ?>" name="name" 
                                       value="<?= htmlspecialchars($app['name']) ?>" required>
                            </div>
                            
                            <div class="container">
                                <label for="email-<?= $app['id'] ?>">Email:</label>
                                <input type="email" id="email-<?= $app['id'] ?>" name="email" 
                                       value="<?= htmlspecialchars($app['email']) ?>" required>
                            </div>
                            
                            <div class="container">
                                <label for="phone-<?= $app['id'] ?>">Phone:</label>
                                <input type="tel" id="phone-<?= $app['id'] ?>" name="phone" 
                                       value="<?= htmlspecialchars($app['phone']) ?>" required>
                            </div>
                            
                            <div class="container">
                                <label>Gender:</label>
                                <div id="gender">
                                    <input type="radio" id="male-<?= $app['id'] ?>" name="gender" value="male" 
                                           <?= $app['gender'] == 'male' ? 'checked' : '' ?>>
                                    <label for="male-<?= $app['id'] ?>">Male</label>
                                    <input type="radio" id="female-<?= $app['id'] ?>" name="gender" value="female" 
                                           <?= $app['gender'] == 'female' ? 'checked' : '' ?>>
                                    <label for="female-<?= $app['id'] ?>">Female</label>
                                </div>
                            </div>
                            
                            <div class="container">
                                <label for="languages-<?= $app['id'] ?>">Languages:</label>
                                <select id="languages-<?= $app['id'] ?>" name="languages[]" multiple>
                                    <?php
                                    $allLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
                                    $selectedLanguages = explode(', ', $app['languages']);
                                    
                                    foreach ($allLanguages as $lang) {
                                        echo '<option value="' . htmlspecialchars($lang) . '"';
                                        if (in_array($lang, $selectedLanguages)) {
                                            echo ' selected';
                                        }
                                        echo '>' . htmlspecialchars($lang) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="container">
                                <label for="bio-<?= $app['id'] ?>">Bio:</label>
                                <textarea id="bio-<?= $app['id'] ?>" name="bio" rows="4"><?= htmlspecialchars($app['bio']) ?></textarea>
                            </div>
                            
                            <button type="submit" name="update">Update</button>
                            <button type="button" onclick="toggleEditForm(<?= $app['id'] ?>)">Cancel</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="stats">
            <h2>Статистика по языкам программирования</h2>
            <table>
                <thead>
                    <tr>
                        <th>Language</th>
                        <th>Users Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languageStats as $stat): ?>
                    <tr>
                        <td><?= htmlspecialchars($stat['name']) ?></td>
                        <td><?= htmlspecialchars($stat['user_count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function toggleEditForm(id) {
            const form = document.getElementById(`edit-form-${id}`);
            form.style.display = form.style.display === 'none' ? 'table-row' : 'none';
        }
    </script>
</body>
</html>