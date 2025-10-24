<?php
/**
 * Authentication and session management
 * Kullanıcı girişi, çıkışı ve session yönetimi
 */

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_secure', $isSecure ? '1' : '0');
    session_start();
}

/**
 * Kullanıcı girişi
 */
function login($email, $password) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Session bilgilerini ayarla
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['balance'] = $user['balance'];
            $_SESSION['login_time'] = time();
            
            return [
                'success' => true,
                'user' => $user,
                'message' => 'Giriş başarılı'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Geçersiz email veya şifre'
            ];
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Sistem hatası oluştu'
        ];
    }
}

/**
 * Kullanıcı kaydı
 */
function register($fullName, $email, $password, $phone = null, $birthDate = null, $gender = null, $role = 'user', $companyId = null) {
    try {
        $pdo = db();
        
        // Email kontrolü
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Bu email adresi zaten kullanılıyor'
            ];
        }
        
        // Yeni kullanıcı oluştur
        $userId = $role . '_' . uniqid();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $balance = ($role === 'user') ? 500 : 1000; // Yeni kullanıcılara başlangıç kredisi
        $sanitizedPhone = $phone ? preg_replace('/\D+/', '', $phone) : null;
        
        $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, company_id, balance, phone, birth_date, gender) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $fullName,
            $email,
            $role,
            $hashedPassword,
            $companyId,
            $balance,
            $sanitizedPhone,
            $birthDate,
            $gender
        ]);
        
        return [
            'success' => true,
            'message' => 'Kayıt başarılı',
            'user_id' => $userId
        ];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Sistem hatası oluştu'
        ];
    }
}

/**
 * Kullanıcı çıkışı
 */
function logout() {
    session_unset();
    session_destroy();
    session_start();
    return true;
}

/**
 * Kullanıcının giriş yapıp yapmadığını kontrol et
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Giriş yapmış kullanıcının bilgilerini al
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'name' => $_SESSION['user_name'],
        'company_id' => $_SESSION['company_id'] ?? null,
        'balance' => $_SESSION['balance']
    ];
}

function getUserById($userId) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("Fetch user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Kullanıcı rolü kontrolü
 */
function hasRole($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    
    if ($userRole === 'admin') {
        return true;
    }

    if (is_array($requiredRole)) {
        return in_array($userRole, $requiredRole);
    }

    return $userRole === $requiredRole;
}

/**
 * Çoklu rol kontrolü
 */
function hasAnyRole($roles) {
    return hasRole($roles);
}

/**
 * Yetki kontrolü middleware
 */
function requireAuth($redirectTo = '/login') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Rol kontrolü middleware
 */
function requireRole($roles, $redirectTo = '/') {
    requireAuth();
    $rolesToCheck = is_array($roles) ? $roles : [$roles];

    $currentRole = $_SESSION['user_role'] ?? null;
    if ($currentRole === 'admin') {
        return;
    }

    if (!in_array($currentRole, $rolesToCheck, true)) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Kullanıcı bakiyesini güncelle
 */
function updateUserBalance($userId, $amount) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $result = $stmt->execute([$amount, $userId]);
        
        // Session'daki bakiyeyi de güncelle
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $userId) {
            $_SESSION['balance'] += $amount;
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Balance update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Kullanıcı bakiyesini kontrol et
 */
function getUserBalance($userId) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Get balance error: " . $e->getMessage());
        return 0;
    }
}

/**
 * CSRF token oluştur
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token doğrula
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
