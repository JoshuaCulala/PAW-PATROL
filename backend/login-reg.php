<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

function redirectWithError(string $message, string $form = ''): never
{
    $url = '../login.php?error=' . rawurlencode($message);
    if ($form !== '') {
        $url .= '&form=' . rawurlencode($form);
    }
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!verifyCsrfToken()) {
    redirectWithError('Your session expired. Please try again.');
}

$action = $_POST['action'] ?? '';
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    redirectWithError('Enter a valid email address.');
}

if ($action === 'register') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        redirectWithError('Enter your name (at least 2 characters, up to 100).', 'register');
    }
    if (strlen($password) < 8) {
        redirectWithError('Choose a password with at least 8 characters.', 'register');
    }
    if (PASSWORD_DEFAULT === PASSWORD_BCRYPT && strlen($password) > 72) {
        redirectWithError('This server supports passwords up to 72 bytes. Choose a shorter password.', 'register');
    }
    if (!hash_equals($password, $confirmPassword)) {
        redirectWithError('The passwords do not match.', 'register');
    }

    try {
        $statement = $conn->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
        );
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $statement->bind_param('sss', $name, $email, $passwordHash);
        $statement->execute();

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $conn->insert_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['role'] = 'user';
        unset($_SESSION['csrf_token']);

        header('Location: ../dashboard.php');
        exit;
    } catch (mysqli_sql_exception $exception) {
        if ($exception->getCode() === 1062) {
            redirectWithError('An account with that email already exists. Please sign in.', 'register');
        }
        error_log('Account registration failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('Account registration failed. Please try again later.');
    }
}

if ($action === 'login') {
    if ($password === '') {
        redirectWithError('Enter your password.');
    }
    $statement = $conn->prepare(
        'SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1'
    );
    $statement->bind_param('s', $email);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        redirectWithError('Email or password is incorrect.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    unset($_SESSION['csrf_token']);

    header('Location: ../dashboard.php');
    exit;
}

redirectWithError('Choose sign in or create an account.');
