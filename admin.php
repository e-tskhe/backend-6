<?php


if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] != 'admin' ||
    md5($_SERVER['PHP_AUTH_PW']) != md5('admin123')) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    die('<h1>401 Требуется авторизация</h1>');
}

require_once 'db.php'


try {
    $db = getDBConnection();

    // Обработка удаления записи
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        $db->beginTransaction();
        
        // Удаляем связи с языками
        $stmt = $db->prepare("DELETE FROM application_language WHERE application_id = ?");
        $stmt->execute([$id]);
        
        // Удаляем саму заявку
        $stmt = $db->prepare("DELETE FROM application WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
        header('Location: admin.php');
        exit();
    }

    // Обработка обновления записи
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        $stmt = $db->prepare("UPDATE application SET name = ?, phone = ?, email = ?, 
                             birthdate = ?, gender = ?, bio = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['bio'],
            $id
        ]);

        // Обновляем языки
        $db->prepare("DELETE FROM application_language WHERE application_id = ?")->execute([$id]);
        
        $langStmt = $db->prepare("INSERT INTO programming_language (name) VALUES (?) 
                                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $appLangStmt = $db->prepare("INSERT INTO application_language (application_id, language_id) 
                                   VALUES (?, ?)");

        foreach ($_POST['languages'] as $langName) {
            $langStmt->execute([$langName]);
            $langId = $db->lastInsertId();
            $appLangStmt->execute([$id, $langId]);
        }
        
        header('Location: admin.php');
        exit();
    }

    // Получаем статистику по языкам
    $languagesStats = $db->query("
        SELECT pl.name, COUNT(al.application_id) as user_count 
        FROM programming_language pl
        LEFT JOIN application_language al ON pl.id = al.language_id
        GROUP BY pl.name
        ORDER BY user_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Получаем все заявки с языками
    $applications = $db->query("
        SELECT a.id, a.user_id, a.name, a.phone, a.email, a.birthdate, a.gender, a.bio,
               GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
        FROM application a
        LEFT JOIN application_language al ON a.id = al.application_id
        LEFT JOIN programming_language pl ON al.language_id = pl.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    print('Ошибка базы данных: ' . $e->getMessage());
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .stats {
            margin-bottom: 30px;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 5px;
        }
        .edit-form {
            display: none;
            background: #f9f9f9;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .edit-form label {
            display: block;
            margin: 10px 0 5px;
        }
        .edit-form input, .edit-form textarea, .edit-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        .edit-form select[multiple] {
            height: 150px;
        }
        button, .button {
            padding: 5px 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        button:hover, .button:hover {
            background: #45a049;
        }
        .delete-btn {
            background: #f44336;
        }
        .delete-btn:hover {
            background: #d32f2f;
        }
        .edit-btn {
            background: #2196F3;
        }
        .edit-btn:hover {
            background: #0b7dda;
        }
    </style>
</head>
<body>
    <h1>Панель администратора</h1>
    
    <div class="stats">
        <h2>Статистика по языкам программирования</h2>
        <table>
            <tr>
                <th>Язык программирования</th>
                <th>Количество пользователей</th>
            </tr>
            <?php foreach ($languagesStats as $stat): ?>
            <tr>
                <td><?= htmlspecialchars($stat['name']) ?></td>
                <td><?= htmlspecialchars($stat['user_count']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <h2>Все заявки</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Телефон</th>
            <th>Email</th>
            <th>Дата рождения</th>
            <th>Пол</th>
            <th>Языки программирования</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($applications as $app): ?>
        <tr>
            <td><?= htmlspecialchars($app['id']) ?></td>
            <td><?= htmlspecialchars($app['name']) ?></td>
            <td><?= htmlspecialchars($app['phone']) ?></td>
            <td><?= htmlspecialchars($app['email']) ?></td>
            <td><?= htmlspecialchars($app['birthdate']) ?></td>
            <td><?= htmlspecialchars($app['gender'] == 'male' ? 'Мужской' : 'Женский') ?></td>
            <td><?= htmlspecialchars($app['languages']) ?></td>
            <td>
                <a href="#" class="button edit-btn" onclick="showEditForm(<?= $app['id'] ?>)">Редактировать</a>
                <a href="?delete=<?= $app['id'] ?>" class="button delete-btn" onclick="return confirm('Вы уверены?')">Удалить</a>
            </td>
        </tr>
        
        <!-- Форма редактирования (скрыта по умолчанию) -->
        <tr id="edit-form-<?= $app['id'] ?>" class="edit-form">
            <td colspan="8">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $app['id'] ?>">
                    
                    <label for="name-<?= $app['id'] ?>">ФИО:</label>
                    <input type="text" id="name-<?= $app['id'] ?>" name="name" value="<?= htmlspecialchars($app['name']) ?>" required>
                    
                    <label for="phone-<?= $app['id'] ?>">Телефон:</label>
                    <input type="tel" id="phone-<?= $app['id'] ?>" name="phone" value="<?= htmlspecialchars($app['phone']) ?>" required>
                    
                    <label for="email-<?= $app['id'] ?>">Email:</label>
                    <input type="email" id="email-<?= $app['id'] ?>" name="email" value="<?= htmlspecialchars($app['email']) ?>" required>
                    
                    <label for="birthdate-<?= $app['id'] ?>">Дата рождения:</label>
                    <input type="date" id="birthdate-<?= $app['id'] ?>" name="birthdate" value="<?= htmlspecialchars($app['birthdate']) ?>" required>
                    
                    <label>Пол:</label>
                    <div>
                        <input type="radio" id="male-<?= $app['id'] ?>" name="gender" value="male" <?= $app['gender'] == 'male' ? 'checked' : '' ?> required>
                        <label for="male-<?= $app['id'] ?>" style="display: inline;">Мужской</label>
                        
                        <input type="radio" id="female-<?= $app['id'] ?>" name="gender" value="female" <?= $app['gender'] == 'female' ? 'checked' : '' ?>>
                        <label for="female-<?= $app['id'] ?>" style="display: inline;">Женский</label>
                    </div>
                    
                    <label for="languages-<?= $app['id'] ?>">Языки программирования:</label>
                    <select id="languages-<?= $app['id'] ?>" name="languages[]" multiple required>
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
                    
                    <label for="bio-<?= $app['id'] ?>">Биография:</label>
                    <textarea id="bio-<?= $app['id'] ?>" name="bio" rows="4" required><?= htmlspecialchars($app['bio']) ?></textarea>
                    
                    <button type="submit">Сохранить</button>
                    <button type="button" onclick="hideEditForm(<?= $app['id'] ?>)">Отмена</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <script>
        function showEditForm(id) {
            // Скрываем все формы редактирования
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Показываем нужную форму
            document.getElementById('edit-form-' + id).style.display = 'table-row';
            
            // Прокручиваем к форме
            document.getElementById('edit-form-' + id).scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideEditForm(id) {
            document.getElementById('edit-form-' + id).style.display = 'none';
        }
    </script>
</body>
</html>