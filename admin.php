<?php
$user = 'u68891'; 
$pass = '3849293'; 
try {
    $pdo = new PDO('mysql:host=localhost;dbname=u68891', $user, $pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Database connection error: ' . $e->getMessage());
}

$admin_user = 'admin';
$admin_pass_hash = md5('12345');

if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] != $admin_user ||
    md5($_SERVER['PHP_AUTH_PW']) != $admin_pass_hash) {
    
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    die('<h1>401 Authorization Required</h1>');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete'])) {
        $appId = $_POST['delete'];
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM application_language WHERE application_id = ?");
            $stmt->execute([$appId]);
            
            $stmt = $pdo->prepare("DELETE FROM application WHERE id = ?");
            $stmt->execute([$appId]);
            
            $pdo->commit();
            $message = "Application #$appId deleted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error deleting application: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit'])) {
        $appId = $_POST['edit'];
        $stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
        $stmt->execute([$appId]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT pl.name FROM programming_language pl
                              JOIN application_language al ON pl.id = al.language_id
                              WHERE al.application_id = ?");
        $stmt->execute([$appId]);
        $editData['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif (isset($_POST['update'])) {
        $appId = $_POST['update'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $birthdate = $_POST['birthdate'];
        $gender = $_POST['gender'];
        $bio = $_POST['bio'];
        $languages = $_POST['languages'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE application SET 
                                   name = ?, phone = ?, email = ?, 
                                   birthdate = ?, gender = ?, bio = ?
                                   WHERE id = ?");
            $stmt->execute([$name, $phone, $email, $birthdate, $gender, $bio, $appId]);
            
            $stmt = $pdo->prepare("DELETE FROM application_language WHERE application_id = ?");
            $stmt->execute([$appId]);
            
            $langStmt = $pdo->prepare("
                INSERT INTO programming_language (name)
                VALUES (:name)
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
            ");
            
            $appLangStmt = $pdo->prepare("
                INSERT INTO application_language (application_id, language_id)
                VALUES (:app_id, :lang_id)
            ");
            
            foreach ($languages as $langName) {
                $langStmt->execute([':name' => $langName]);
                $langId = $pdo->lastInsertId();
                $appLangStmt->execute([
                    ':app_id' => $appId,
                    ':lang_id' => $langId
                ]);
            }
            
            $pdo->commit();
            $message = "Application #$appId updated successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating application: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("
    SELECT a.*, GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
    FROM application a
    LEFT JOIN application_language al ON a.id = al.application_id
    LEFT JOIN programming_language pl ON al.language_id = pl.id
    GROUP BY a.id
    ORDER BY a.id DESC
");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT pl.name, COUNT(al.application_id) as user_count
    FROM programming_language pl
    LEFT JOIN application_language al ON pl.id = al.language_id
    GROUP BY pl.name
    ORDER BY user_count DESC
");
$languageStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .error { color: red; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 150px; }
        select[multiple] { height: 100px; }
    </style>
</head>
<body>
    <h1>Admin Panel</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (isset($message)): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <h2>User Applications</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Birthdate</th>
                <th>Gender</th>
                <th>Languages</th>
                <th>Bio</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= htmlspecialchars($app['id']) ?></td>
                    <td><?= htmlspecialchars($app['name']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= htmlspecialchars($app['birthdate']) ?></td>
                    <td><?= htmlspecialchars($app['gender']) ?></td>
                    <td><?= htmlspecialchars($app['languages']) ?></td>
                    <td><?= htmlspecialchars($app['bio']) ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="edit" value="<?= $app['id'] ?>">Edit</button>
                        </form>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="delete" value="<?= $app['id'] ?>" 
                                    onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (isset($editData)): ?>
        <h2>Edit Application #<?= htmlspecialchars($editData['id']) ?></h2>
        <form method="post">
            <input type="hidden" name="update" value="<?= $editData['id'] ?>">
            
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($editData['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($editData['phone']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($editData['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="birthdate">Birthdate:</label>
                <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($editData['birthdate']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Gender:</label>
                <input type="radio" id="male" name="gender" value="male" <?= $editData['gender'] == 'male' ? 'checked' : '' ?> required>
                <label for="male">Male</label>
                <input type="radio" id="female" name="gender" value="female" <?= $editData['gender'] == 'female' ? 'checked' : '' ?>>
                <label for="female">Female</label>
            </div>
            
            <div class="form-group">
                <label for="languages">Languages:</label>
                <select id="languages" name="languages[]" multiple required>
                    <?php
                    $allLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 
                                    'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
                    foreach ($allLanguages as $lang): ?>
                        <option value="<?= $lang ?>" <?= in_array($lang, $editData['languages']) ? 'selected' : '' ?>>
                            <?= $lang ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio" rows="4" required><?= htmlspecialchars($editData['bio']) ?></textarea>
            </div>
            
            <button type="submit">Update</button>
            <button type="button" onclick="window.location.href='admin.php'">Cancel</button>
        </form>
    <?php endif; ?>
    
    <h2>Language Statistics</h2>
    <table>
        <thead>
            <tr>
                <th>Language</th>
                <th>User Count</th>
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
</body>
</html>