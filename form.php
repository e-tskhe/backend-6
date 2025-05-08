<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Задание 5</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php 
    if (!empty($messages)) {
        print('<div id="messages">');
        foreach($messages as $message) {
            print($message);
        }
        print('</div>');
    }
    ?>

    <div class="formular">
        <form action="" method="POST">
            <h2>Анкета</h2>
            
            <div class="container">
                <label for="name">ФИО:</label>
                <input type="text" id="name" name="name" maxlength="100"
                    <?php if ($errors['name']) {print 'class="error"';} ?> 
                    value="<?php print htmlspecialchars($values['name']); ?>">
                <?php if ($errors['name']) {print '<div class="error-message">'.$messages['name'].'</div>';} ?>
            </div>

            <div class="container">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone"
                    <?php if ($errors['phone']) {print 'class="error"';} ?> 
                    value="<?php print htmlspecialchars($values['phone']); ?>">
                <?php if ($errors['phone']) {print '<div class="error-message">'.$messages['phone'].'</div>';} ?>
            </div>

            <div class="container">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email"
                    <?php if ($errors['email']) {print 'class="error"';} ?> 
                    value="<?php print htmlspecialchars($values['email']); ?>">
                <?php if ($errors['email']) {print '<div class="error-message">'.$messages['email'].'</div>';} ?>
            </div>

            <div class="container">
                <label for="birthdate">Дата рождения:</label>
                <input type="date" id="birthdate" name="birthdate"
                    <?php if ($errors['birthdate']) {print 'class="error"';} ?> 
                    value="<?php print htmlspecialchars($values['birthdate']); ?>">
                <?php if ($errors['birthdate']) {print '<div class="error-message">'.$messages['birthdate'].'</div>';} ?>
            </div>

            <div class="container">
                <label>Пол:</label>
                <div id="gender">
                    <input type="radio" id="male" name="gender" value="male"
                        <?php if ($values['gender'] == 'male') {print 'checked';} ?>>
                    <label for="male" style="font-weight: 500;">Мужской</label>
                    <input type="radio" id="female" name="gender" value="female"
                        <?php if ($values['gender'] == 'female') {print 'checked';} ?>>
                    <label for="female" style="font-weight: 500;">Женский</label>
                </div>
                <?php if ($errors['gender']) {print '<div class="error-message">'.$messages['gender'].'</div>';} ?>
            </div>

            <div class="container">
                <label for="languages">Любимый язык программирования:<br></label>
                <select id="languages" name="languages[]" multiple 
                    <?php if ($errors['languages']) { print 'class="error"'; } ?>>
                    <?php
                    $allLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
                    $selectedLanguages = is_array($values['languages']) ? $values['languages'] : [];
                    
                    foreach ($allLanguages as $lang) {
                        echo '<option value="' . htmlspecialchars($lang) . '"';
                        if (in_array($lang, $selectedLanguages)) {
                            echo ' selected';
                        }
                        echo '>' . htmlspecialchars($lang) . '</option>';
                    }
                    ?>
                </select>
                <?php if ($errors['languages']) { print '<div class="error-message">Выберите хотя бы один язык программирования.</div>'; } ?>
            </div>

            <div class="container">
                <label for="bio">Биография:<br></label>
                <textarea id="bio" name="bio" rows="4"
                    <?php if ($errors['bio']) {print 'class="error"';} ?>><?php print htmlspecialchars($values['bio']); ?></textarea>
                <?php if ($errors['bio']) {print '<div class="error-message">'.$messages['bio'].'</div>';} ?>
            </div>

            <div class="container">
                <label>
                    <input type="checkbox" name="agreement" value="1"
                        <?php if ($values['agreement']) {print 'checked';} ?>>
                    С контрактом ознакомлен(а)
                </label>
                <?php if ($errors['agreement']) {print '<div class="error-message">'.$messages['agreement'].'</div>';} ?>
            </div>

            <div class="container"> 
                <button type="submit">Отправить</button>
            </div>
        </form>
    </div>
</body>
</html>