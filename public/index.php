<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/router.php';

initializeDatabase();

$router = new Router();

$router->addRoute('GET', '/', function() {
    include __DIR__ . '/../views/home.php';
});

$router->addRoute('GET', '/login', function() {
    if (isLoggedIn()) {
        header('Location: /');
        exit;
    }
    include __DIR__ . '/../views/login.php';
});

$router->addRoute('POST', '/login', function() {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCSRFToken($csrfToken)) {
        $_SESSION['error'] = 'Security error. Please try again.';
        header('Location: /login');
        exit;
    }
    
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    $result = login($email, $password);
    if ($result['success']) {
        header('Location: /');
        exit;
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: /login');
        exit;
    }
});

$router->addRoute('GET', '/register', function() {
    if (isLoggedIn()) {
        header('Location: /');
        exit;
    }
    include __DIR__ . '/../views/register.php';
});

$router->addRoute('POST', '/register', function() {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCSRFToken($csrfToken)) {
        $_SESSION['error'] = 'Security error. Please try again.';
        header('Location: /register');
        exit;
    }
    
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    
    $errors = array();
    
    if (empty($name)) array_push($errors, 'Name is required.');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) array_push($errors, 'Valid email is required.');
    if (strlen($password) < 6) array_push($errors, 'Password must be at least 6 characters.');
    if ($password !== $password_confirm) array_push($errors, 'Passwords do not match.');
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: /register');
        exit;
    }
    
    $result = register($name, $email, $password);
    if ($result['success']) {
        $_SESSION['success'] = 'Registration successful! You can now login.';
        header('Location: /login');
        exit;
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: /register');
        exit;
    }
});

$router->addRoute('GET', '/logout', function() {
    logout();
    header('Location: /');
    exit;
});

$router->addRoute('GET', '/my-tickets', function() {
    requireAuth();
    $_SESSION['info'] = 'My tickets page coming soon.';
    header('Location: /');
    exit;
});

$router->addRoute('GET', '/profile', function() {
    requireAuth();
    $_SESSION['info'] = 'Profile page coming soon.';
    header('Location: /');
    exit;
});

$router->addRoute('GET', '/admin-panel', function() {
    requireAuth();
    requireRole('admin');
    $_SESSION['info'] = 'Admin panel coming soon.';
    header('Location: /');
    exit;
});

$router->addRoute('GET', '/company-panel', function() {
    requireAuth();
    requireRole('company_admin');
    $_SESSION['info'] = 'Company panel coming soon.';
    header('Location: /');
    exit;
});

$router->dispatch();
