<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/router.php';
require_once __DIR__ . '/../config/admin.php';
require_once __DIR__ . '/../config/company.php';
require_once __DIR__ . '/../config/ticket.php';
require_once __DIR__ . '/../config/trip.php';

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
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $birthDate = isset($_POST['birth_date']) ? $_POST['birth_date'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    
    $errors = array();
    
    if (empty($name)) array_push($errors, 'Name is required.');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) array_push($errors, 'Valid email is required.');
    if (empty($phone)) array_push($errors, 'Phone number is required.');
    $normalizedPhone = preg_replace('/\D+/', '', $phone);
    if (!empty($phone) && (strlen($normalizedPhone) !== 11 || strpos($normalizedPhone, '05') !== 0)) {
        array_push($errors, 'Phone number must be 11 digits and start with 05.');
    }
    if (empty($birthDate)) {
        array_push($errors, 'Birth date is required.');
    } else {
        $birthDateObj = DateTime::createFromFormat('Y-m-d', $birthDate);
        $birthDateErrors = DateTime::getLastErrors();
        if (!$birthDateObj || $birthDateErrors['warning_count'] > 0 || $birthDateErrors['error_count'] > 0) {
            array_push($errors, 'Birth date must be a valid date.');
        } elseif ($birthDateObj > new DateTime()) {
            array_push($errors, 'Birth date cannot be in the future.');
        }
    }
    if (empty($gender) || !in_array($gender, ['male', 'female', 'other'])) {
        array_push($errors, 'Please select a valid gender option.');
    }
    if (strlen($password) < 6) array_push($errors, 'Password must be at least 6 characters.');
    if ($password !== $password_confirm) array_push($errors, 'Passwords do not match.');
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: /register');
        exit;
    }
    
    $result = register($name, $email, $password, $normalizedPhone, $birthDate, $gender);
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

$router->addRoute('GET', '/search', function() {
    $departureCity = isset($_GET['departure_city']) ? trim($_GET['departure_city']) : '';
    $arrivalCity = isset($_GET['arrival_city']) ? trim($_GET['arrival_city']) : '';
    $departureDate = isset($_GET['departure_date']) ? $_GET['departure_date'] : '';
    $passengerCount = isset($_GET['passenger_count']) ? (int)$_GET['passenger_count'] : 1;
    if ($passengerCount < 1) {
        $passengerCount = 1;
    }

    if ($departureCity === '' || $arrivalCity === '' || $departureDate === '') {
        $_SESSION['error'] = 'Please provide departure city, arrival city and departure date.';
        header('Location: /');
        exit;
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $departureDate);
    $dateErrors = DateTime::getLastErrors();
    if (!$dateObj || $dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0) {
        $_SESSION['error'] = 'Departure date must be a valid date.';
        header('Location: /');
        exit;
    }

    $trips = searchTrips($departureCity, $arrivalCity, $departureDate);
    $searchParams = [
        'departure_city' => $departureCity,
        'arrival_city' => $arrivalCity,
        'departure_date' => $departureDate,
        'passenger_count' => $passengerCount
    ];

    include __DIR__ . '/../views/search_results.php';
});

$router->addRoute('GET', '/trip', function() {
    $tripId = isset($_GET['id']) ? trim($_GET['id']) : '';
    if ($tripId === '') {
        $_SESSION['error'] = 'Trip not found.';
        header('Location: /');
        exit;
    }

    $trip = getTripById($tripId);
    if (!$trip) {
        http_response_code(404);
        include __DIR__ . '/../views/404.php';
        return;
    }

    include __DIR__ . '/../views/trip_detail.php';
});

$router->addRoute('GET', '/logout', function() {
    logout();
    header('Location: /');
    exit;
});

$router->addRoute('GET', '/my-tickets', function() {
    requireAuth();
    $currentUser = getCurrentUser();
    if (!in_array($currentUser['role'], ['user', 'company_admin'], true)) {
        header('Location: /');
        exit;
    }

    $tickets = getTicketsByUser($currentUser['id']);
    include __DIR__ . '/../views/my_tickets.php';
});

$router->addRoute('GET', '/profile', function() {
    requireAuth();
    $currentUser = getCurrentUser();
    $userDetails = getUserById($currentUser['id']);
    $companyInfo = null;
    if (!empty($currentUser['company_id'])) {
        $companyInfo = getCompanyInfo($currentUser['company_id']);
    }
    include __DIR__ . '/../views/profile.php';
});

$router->addRoute('GET', '/admin-panel', function() {
    requireAuth();
    requireRole('admin');
    $companies = getAllCompaniesWithStats();
    $companyAdmins = getCompanyAdmins();
    $coupons = getAllCouponsDetailed();
    include __DIR__ . '/../views/admin_panel.php';
});

$router->addRoute('GET', '/company-panel', function() {
    requireAuth();
    requireRole('company_admin');
    $currentUser = getCurrentUser();
    if ($currentUser['role'] !== 'company_admin') {
        header('Location: /admin-panel');
        exit;
    }
    $companyId = $currentUser['company_id'] ?? null;

    if (!$companyId) {
        $_SESSION['error'] = 'Firma bilgisi bulunamadı. Lütfen sistem yöneticisi ile iletişime geçin.';
        header('Location: /');
        exit;
    }

    $company = getCompanyInfo($companyId);
    if (!$company) {
        $_SESSION['error'] = 'Firma kaydı bulunamadı.';
        header('Location: /');
        exit;
    }

    $routes = getCompanyRoutes($companyId);
    $trips = getCompanyTrips($companyId);
    $coupons = getCompanyCoupons($companyId);
    include __DIR__ . '/../views/company_panel.php';
});

$router->dispatch();
