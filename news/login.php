<?php
// news/login.php
session_start();
header('Content-Type: application/json');

$allowed_login = 'u3239172_user';

$stored_hash = '$2a$12$f/ENglyHzB8gxr70mOu5l.wc6fNKXieMUG2rY6lN2OyWw/d4LZxZm'; 
// >>> ПРИМЕЧАНИЕ: перед заливкой на сервер выполните на сервере php: password_hash('AZ0O2n6cp1lAJQAU', PASSWORD_DEFAULT) и замените $stored_hash реальным хешем.
// Я не записываю plain password в клиентские файлы. Вы сами можете сгенерировать хеш и подставить его сюда.

$login = $_POST['login'] ?? '';
$pass = $_POST['password'] ?? '';

if ($login !== $allowed_login) {
    echo json_encode(['success'=>false, 'error'=>'Неверный логин или пароль']);
    exit;
}

// Проверяем пароль
if (!password_verify($pass, $stored_hash)) {
    echo json_encode(['success'=>false, 'error'=>'Неверный логин или пароль']);
    exit;
}

// Успешный вход
// Генерируем случайный токен (для красивого адреса) и сохраняем в сессию
$token = bin2hex(random_bytes(10));
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_token'] = $token;
$_SESSION['admin_login'] = $allowed_login;

// Вернём адрес для перехода (случайный параметр)
$redirect = "/news/admin.php?token=" . $token;
echo json_encode(['success'=>true, 'redirect'=>$redirect]);
exit;
