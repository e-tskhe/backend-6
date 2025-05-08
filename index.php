<?php
function generateLogin() {
    $base = substr(md5(uniqid()), 0, 6);
    
    $randomSuffix = rand(100, 999);
    
    return 'user_' . $base . $randomSuffix;
}

function generatePassword($length = 12) {
    $hash = md5(uniqid() . rand());
    
    $uppercase = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 2);
    $lowercase = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 2);
    $numbers = substr(str_shuffle('123456789'), 0, 2);
    $special = substr(str_shuffle('!@#$%^&*'), 0, 2);
    
    $password = str_shuffle(
        substr($hash, 0, $length - 8) . 
        $uppercase . $lowercase . $numbers . $special
    );
    
    return substr($password, 0, $length);
}
?>

<?php
/**
 * Реализовать проверку заполнения обязательных полей формы в предыдущей
 * с использованием Cookies, а также заполнение формы по умолчанию ранее
 * введенными значениями.
 */

// Отправляем браузеру правильную кодировку,
// файл index.php должен быть в кодировке UTF-8 без BOM.
header('Content-Type: text/html; charset=UTF-8');

// В суперглобальном массиве $_SERVER PHP сохраняет некторые заголовки запроса HTTP
// и другие сведения о клиненте и сервере, например метод текущего запроса $_SERVER['REQUEST_METHOD'].
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Массив для временного хранения сообщений пользователю.
    $messages = array();

    // В суперглобальном массиве $_COOKIE PHP хранит все имена и значения куки текущего запроса.
    // Выдаем сообщение об успешном сохранении.
    if (!empty($_COOKIE['save'])) {
        // Удаляем куку, указывая время устаревания в прошлом.
        setcookie('save', '', 100000);
        // Если есть параметр save, то выводим сообщение пользователю.
        $messages[] = 'Спасибо, результаты сохранены.';
    }

    if (!empty($_COOKIE['pass'])) {
        $messages[] = sprintf('Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong>
        и паролем <strong>%s</strong> для изменения данных.',
        strip_tags($_COOKIE['login']),
        strip_tags($_COOKIE['pass']));
    }

    // Складываем признак ошибок в массив.
    if ($errors['name']) {
        setcookie('name_error', '', 100000);
        $messages['name'] = '<div class="error">Заполните имя. Допустимы только буквы и пробелы.</div>';
    }
    if ($errors['phone']) {
        setcookie('phone_error', '', 100000);
        $messages['phone'] = '<div class="error">Заполните телефон в формате +7/7/8 и 10 цифр.</div>';
    }
    if ($errors['email']) {
        setcookie('email_error', '', 100000);
        $messages['email'] = '<div class="error">Заполните email в правильном формате.</div>';
    }
    if ($errors['birthdate']) {
        setcookie('birthdate_error', '', 100000);
        $messages['birthdate'] = '<div class="error">Заполните дату рождения.</div>';
    }
    if ($errors['gender']) {
        setcookie('gender_error', '', 100000);
        $messages['gender'] = '<div class="error">Укажите пол.</div>';
    }
    if ($errors['languages']) {
        setcookie('languages_error', '', 100000);
        $messages['languages'] = '<div class="error">Выберите хотя бы один язык программирования.</div>';
    }
    if ($errors['bio']) {
        setcookie('bio_error', '', 100000);
        $messages['bio'] = '<div class="error">Заполните биографию. Допустимы буквы, цифры и знаки препинания.</div>';
    }
    if ($errors['agreement']) {
        setcookie('agreement_error', '', 100000);
        $messages['agreement'] = '<div class="error">Необходимо ваше согласие.</div>';
    }

    $values = array();
    $values['name'] = empty($_COOKIE['name_value']) ? '' : $_COOKIE['name_value'];
    $values['phone'] = empty($_COOKIE['phone_value']) ? '' : $_COOKIE['phone_value'];
    $values['email'] = empty($_COOKIE['email_value']) ? '' : $_COOKIE['email_value'];
    $values['birthdate'] = empty($_COOKIE['birthdate_value']) ? '' : $_COOKIE['birthdate_value'];
    $values['gender'] = empty($_COOKIE['gender_value']) ? '' : $_COOKIE['gender_value'];
    $values['languages'] = empty($_COOKIE['languages_value']) ? array() : unserialize($_COOKIE['languages_value']);    $values['bio'] = empty($_COOKIE['bio_value']) ? '' : $_COOKIE['bio_value'];
    $values['agreement'] = empty($_COOKIE['agreement_value']) ? '' : $_COOKIE['agreement_value'];

    // Если нет предыдущих ошибок ввода, есть кука сессии, начали сессию и
    // ранее в сессию записан факт успешного логина.
    if (empty($errors) && !empty($_COOKIE[session_name()]) &&
        session_start() && !empty($_SESSION['login'])) {
        // TODO: загрузить данные пользователя из БД
        // и заполнить переменную $values,
        // предварительно санитизовав.
        // Для загрузки данных из БД делаем запрос SELECT и вызываем метод PDO fetchArray(), fetchObject() или fetchAll() 
        // См. https://www.php.net/manual/en/pdostatement.fetchall.php
        printf('Вход с логином %s, uid %d', $_SESSION['login'], $_SESSION['uid']);
    }   

    // Включаем содержимое файла form.php.
    // В нем будут доступны переменные $messages, $errors и $values для вывода 
    // сообщений, полей с ранее заполненными данными и признаками ошибок.
    include('form2.php');
}
// Иначе, если запрос был методом POST, т.е. нужно проверить данные и сохранить их в XML-файл.
else {
    // Проверяем ошибки.
    $errors = FALSE;
    
    if (empty($_POST['name'])) {
        // Выдаем куку на день с флажком об ошибке в поле name.
        setcookie('name_error', 'Заполните имя', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    elseif (!preg_match('/^[а-яёa-z\s-]+$/iu', $_POST['name'])) {
        setcookie('name_error', 'Имя должно содержать только буквы и дефисы', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    // Сохраняем ранее введенное в форму значение на месяц.
    setcookie('name_value', $_POST['name'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['phone'])) {
        setcookie('phone_error', 'Заполните телефон', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    elseif (!preg_match('/^(\+7|7|8)\d{10}$/', $_POST['phone'])) {
        setcookie('phone_error', 'Телефон должен быть в формате +7/7/8 и 10 цифр', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('phone_value', $_POST['phone'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['email'])) {
        setcookie('email_error', 'Заполните email', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', 'Некорректный формат email', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('email_value', $_POST['email'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['birthdate'])) {
        setcookie('birthdate_error', 'Укажите дату рождения', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['birthdate'])) {
        setcookie('birthdate_error', 'Некорректный формат даты', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('birthdate_value', $_POST['birthdate'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['gender'])) {
        setcookie('gender_error', 'Укажите пол', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('gender_value', $_POST['gender'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['languages'])) {
        setcookie('languages_error', 'Выберите хотя бы один язык', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('languages_value', serialize($_POST['languages']), time() + 30 * 24 * 60 * 60);

    if (empty($_POST['bio'])) {
        setcookie('bio_error', 'Заполните биографию', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    elseif (!preg_match('/^[а-яёa-z0-9\s.,!?-]+$/iu', $_POST['bio'])) {
        setcookie('bio_error', 'Биография содержит недопустимые символы', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('bio_value', $_POST['bio'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['agreement'])) {
        setcookie('agreement_error', 'Необходимо ваше согласие', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('agreement_value', $_POST['agreement'], time() + 30 * 24 * 60 * 60);

    if ($errors) {
        // При наличии ошибок перезагружаем страницу и завершаем работу скрипта.
        header('Location: index.php');
        exit();
    }
    else {
        // Удаляем Cookies с признаками ошибок.
        setcookie('name_error', '', 100000);
        setcookie('phone_error', '', 100000);
        setcookie('email_error', '', 100000);
        setcookie('birthdate_error', '', 100000);
        setcookie('gender_error', '', 100000);
        setcookie('languages_error', '', 100000);
        setcookie('bio_error', '', 100000);
        setcookie('agreement_error', '', 100000);
    }

    if (!empty($_SESSION['login']) && $_SESSION['login'] == 'admin') {
        echo '<p><a href="admin.php">Перейти в панель администратора</a></p>';
    }

    // Проверяем меняются ли ранее сохраненные данные или отправляются новые.
    if (!empty($_COOKIE[session_name()]) &&
        session_start() && !empty($_SESSION['login'])) {
        // Сохранение в БД
        $user = 'u68891'; 
        $pass = '3849293'; 
        $pdo = new PDO('mysql:host=localhost;dbname=u68891', $user, $pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); 

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($errors)) {
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $birthdate = $_POST['birthdate'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $bio = $_POST['bio'] ?? '';
            $languages = $_POST['languages'] ?? []; 

            $pdo->beginTransaction();

            // Сохранение основной информации в таблицу application
            $stmt = $pdo->prepare("INSERT INTO application (name, phone, email, birthdate, gender, bio) 
                        VALUES (:name, :phone, :email, :birthdate, :gender, :bio)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':birthdate', $birthdate);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':bio', $bio);
            
            $stmt->execute();
            $applicationId = $pdo->lastInsertId();

            // Сохранение любимых языков программирования в таблицу application_language
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
                    ':app_id' => $applicationId,
                    ':lang_id' => $langId
                ]);
            }
            $pdo->commit();

            echo "Данные успешно сохранены!";
            }
        }
    }
    else {
        // Генерируем уникальный логин и пароль.
        // TODO: сделать механизм генерации, например функциями rand(), uniquid(), md5(), substr().
        $login = generateLogin();
        $password = generatePassword();
        // Сохраняем в Cookies.
        setcookie('login', $login);
        setcookie('pass', $pass);

        // TODO: Сохранение данных формы, логина и хеш md5() пароля в базу данных.
        // ...

        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $birthdate = $_POST['birthdate'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $bio = $_POST['bio'] ?? '';
        $languages = $_POST['languages'] ?? []; 

        $pdo->beginTransaction();

        
        $userStmt = $pdo->prepare("INSERT INTO users (username, password_hash) 
                                    VALUES (:username, :password_hash)");
        $userStmt->bindParam(':username', $login);
        $userStmt->bindParam(':password_hash', $passwordHash);
        $userStmt->execute();
        $userId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO application (user_id, name, phone, email, birthdate, gender, bio) 
                    VALUES (:user_id, :name, :phone, :email, :birthdate, :gender, :bio)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':birthdate', $birthdate);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':bio', $bio);
        $stmt->execute();
        $applicationId = $pdo->lastInsertId();

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
                ':app_id' => $applicationId,
                ':lang_id' => $langId
            ]);
        }

        $pdo->commit();

        echo "Данные успешно сохранены!<br>";
        echo "Ваш логин: " . htmlspecialchars($login) . "<br>";
        echo "Ваш пароль: " . htmlspecialchars($password) . "<br>";
        echo "Запомните их для входа в систему.";
        }
    }
    // Сохраняем куку с признаком успешного сохранения.
    setcookie('save', '1');

    // Делаем перенаправление.
    header('Location: index.php');
    