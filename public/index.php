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
require_once __DIR__ . '/../config/pdf.php';

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

    $trips = searchTrips($departureCity, $arrivalCity, $departureDate, $passengerCount);
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

    $bookedSeats = getTripBookedSeats($tripId);
    $currentUser = getCurrentUser();
    include __DIR__ . '/../views/trip_detail.php';
});

$router->addRoute('POST', '/trip/book', function() {
    requireAuth();
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    $tripId = isset($_POST['trip_id']) ? trim($_POST['trip_id']) : '';
    $seatNumber = isset($_POST['seat_number']) ? (int)$_POST['seat_number'] : 0;
    $passengerName = isset($_POST['passenger_name']) ? trim($_POST['passenger_name']) : '';
    $passengerTc = isset($_POST['passenger_tc']) ? trim($_POST['passenger_tc']) : null;
    $couponCode = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : null;

    $redirectToTrip = '/trip?id=' . urlencode($tripId);

    $currentUser = getCurrentUser();
    if (!$tripId) {
        $_SESSION['error'] = 'Geçersiz sefer bilgisi.';
        header('Location: /');
        exit;
    }

    if (!$currentUser || $currentUser['role'] !== 'user') {
        $_SESSION['error'] = 'Bilet satın almak için yolcu hesabı ile giriş yapmalısınız.';
        header('Location: ' . $redirectToTrip);
        exit;
    }

    if ($passengerName === '') {
        $_SESSION['error'] = 'Yolcu adı zorunludur.';
        $_SESSION['booking_form'] = [
            'seat_number' => $seatNumber,
            'passenger_name' => $passengerName,
            'passenger_tc' => $passengerTc,
            'coupon_code' => $couponCode
        ];
        header('Location: ' . $redirectToTrip);
        exit;
    }

    if ($seatNumber <= 0) {
        $_SESSION['error'] = 'Lütfen bir koltuk seçin.';
        $_SESSION['booking_form'] = [
            'seat_number' => $seatNumber,
            'passenger_name' => $passengerName,
            'passenger_tc' => $passengerTc,
            'coupon_code' => $couponCode
        ];
        header('Location: ' . $redirectToTrip);
        exit;
    }

    if ($passengerTc !== null && $passengerTc !== '') {
        $passengerTcDigits = preg_replace('/\D+/', '', $passengerTc);
        if (strlen($passengerTcDigits) !== 11) {
            $_SESSION['error'] = 'Yolcu TC kimlik numarası 11 haneli olmalıdır.';
            $_SESSION['booking_form'] = [
                'seat_number' => $seatNumber,
                'passenger_name' => $passengerName,
                'passenger_tc' => $passengerTc,
                'coupon_code' => $couponCode
            ];
            header('Location: ' . $redirectToTrip);
            exit;
        }
        $passengerTc = $passengerTcDigits;
    } else {
        $passengerTc = null;
    }

    $result = purchaseTripTicket(
        $tripId,
        $currentUser['id'],
        $seatNumber,
        $passengerName,
        $passengerTc,
        $couponCode ?: null
    );

    if ($result['success']) {
        unset($_SESSION['booking_form']);
        $_SESSION['success'] = 'Bilet satın alındı. Ödenen tutar: ' . number_format($result['total_price'], 2) . ' ₺';
        header('Location: /my-tickets');
        exit;
    }

    $_SESSION['error'] = $result['message'];
    $_SESSION['booking_form'] = [
        'seat_number' => $seatNumber,
        'passenger_name' => $passengerName,
        'passenger_tc' => $passengerTc,
        'coupon_code' => $couponCode
    ];
    header('Location: ' . $redirectToTrip);
    exit;
});

$router->addRoute('POST', '/ticket/cancel', function() {
    requireAuth();
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.';
        header('Location: /my-tickets');
        exit;
    }

    $ticketId = isset($_POST['ticket_id']) ? trim($_POST['ticket_id']) : '';
    if ($ticketId === '') {
        $_SESSION['error'] = 'Geçersiz bilet bilgisi.';
        header('Location: /my-tickets');
        exit;
    }

    $currentUser = getCurrentUser();
    $result = cancelTicket($ticketId, $currentUser);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

    header('Location: /my-tickets');
    exit;
});

$router->addRoute('GET', '/ticket/pdf', function() {
    requireAuth();
    $ticketId = isset($_GET['id']) ? trim($_GET['id']) : '';
    if ($ticketId === '') {
        $_SESSION['error'] = 'Geçersiz bilet bilgisi.';
        header('Location: /my-tickets');
        exit;
    }

    $currentUser = getCurrentUser();
    $ticket = getTicketDetails($ticketId);

    if (!$ticket) {
        http_response_code(404);
        $_SESSION['error'] = 'Bilet bulunamadı.';
        header('Location: /my-tickets');
        exit;
    }

    $isOwner = $ticket['user_id'] === $currentUser['id'];
    $isAdmin = $currentUser['role'] === 'admin';
    $isCompanyAdmin = $currentUser['role'] === 'company_admin' && $currentUser['company_id'] === ($ticket['company_id'] ?? null);

    if (!($isOwner || $isAdmin || $isCompanyAdmin)) {
        $_SESSION['error'] = 'Bu bileti görüntülemeye yetkiniz yok.';
        header('Location: /my-tickets');
        exit;
    }

    $owner = getUserById($ticket['user_id']) ?? $currentUser;
    output_ticket_pdf($ticket, [
        'name' => $owner['full_name'] ?? ($owner['name'] ?? ''),
    ]);
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
