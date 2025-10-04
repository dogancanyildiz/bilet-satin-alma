<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/router.php';

initializeDatabase();

$router = new Router();

$router->addRoute('GET', '/', function() {
    echo "<h1>Welcome to Bus Ticket Platform!</h1>";
    echo "<p>Database connection working!</p>";
    if (isLoggedIn()) {
        $user = getCurrentUser();
        echo "<p>Hello " . htmlspecialchars($user['name']) . "!</p>";
        echo '<p><a href="/logout">Logout</a></p>';
    } else {
        echo '<p><a href="/login">Login</a> | <a href="/register">Register</a></p>';
    }
});

$router->addRoute('GET', '/login', function() {
    include __DIR__ . '/../views/login.php';
});

$router->addRoute('GET', '/register', function() {
    include __DIR__ . '/../views/register.php';
});

$router->addRoute('GET', '/logout', function() {
    logout();
    header('Location: /');
    exit;
});

$router->dispatch();
?>