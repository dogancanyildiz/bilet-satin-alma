<?php
/**
 * Admin panel helpers
 */

function getAllCompaniesWithStats(): array {
    try {
        $pdo = db();
        $sql = "
            SELECT 
                bc.id,
                bc.name,
                bc.logo_path,
                bc.created_at,
                (SELECT COUNT(*) FROM routes r WHERE r.company_id = bc.id) AS route_count,
                (SELECT COUNT(*) FROM trips t WHERE t.company_id = bc.id) AS trip_count,
                (SELECT COUNT(*) FROM users u WHERE u.company_id = bc.id AND u.role = 'company_admin') AS admin_count
            FROM bus_company bc
            ORDER BY bc.created_at DESC
        ";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Admin companies fetch error: ' . $e->getMessage());
        return [];
    }
}

function getCompanyAdmins(): array {
    try {
        $pdo = db();
        $sql = "
            SELECT 
                u.id,
                u.full_name,
                u.email,
                u.balance,
                u.created_at,
                bc.id AS company_id,
                bc.name AS company_name
            FROM users u
            LEFT JOIN bus_company bc ON u.company_id = bc.id
            WHERE u.role = 'company_admin'
            ORDER BY bc.name, u.full_name
        ";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Admin company admins fetch error: ' . $e->getMessage());
        return [];
    }
}

function getAllCouponsDetailed(): array {
    try {
        $pdo = db();
        $sql = "
            SELECT 
                c.id,
                c.code,
                c.discount,
                c.usage_limit,
                c.expire_date,
                c.company_id,
                c.is_global,
                c.created_at,
                bc.name AS company_name,
                (
                    SELECT COUNT(*) 
                    FROM user_coupons uc 
                    WHERE uc.coupon_id = c.id
                ) AS usage_count
            FROM coupons c
            LEFT JOIN bus_company bc ON c.company_id = bc.id
            ORDER BY c.is_global DESC, c.expire_date ASC
        ";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Admin coupons fetch error: ' . $e->getMessage());
        return [];
    }
}

function createCompany(string $name): array {
    try {
        $pdo = db();
        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => 'Firma adı boş olamaz.'];
        }

        $check = $pdo->prepare("SELECT id FROM bus_company WHERE name = ? LIMIT 1");
        $check->execute([$name]);
        if ($check->fetchColumn()) {
            return ['success' => false, 'message' => 'Bu firma adı zaten kullanılıyor.'];
        }

        $companyId = 'company_' . uniqid();
        $stmt = $pdo->prepare("INSERT INTO bus_company (id, name) VALUES (?, ?)");
        $stmt->execute([$companyId, $name]);

        return ['success' => true, 'message' => 'Firma oluşturuldu.'];
    } catch (PDOException $e) {
        error_log('Create company error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Firma oluşturulamadı.'];
    }
}

function updateCompany(string $companyId, string $name): array {
    try {
        $pdo = db();
        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => 'Firma adı boş olamaz.'];
        }

        $stmt = $pdo->prepare("SELECT id FROM bus_company WHERE id = ? LIMIT 1");
        $stmt->execute([$companyId]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Firma bulunamadı.'];
        }

        $check = $pdo->prepare("SELECT id FROM bus_company WHERE name = ? AND id != ? LIMIT 1");
        $check->execute([$name, $companyId]);
        if ($check->fetchColumn()) {
            return ['success' => false, 'message' => 'Bu firma adı başka bir firmada kullanılıyor.'];
        }

        $update = $pdo->prepare("UPDATE bus_company SET name = ? WHERE id = ?");
        $update->execute([$name, $companyId]);

        return ['success' => true, 'message' => 'Firma güncellendi.'];
    } catch (PDOException $e) {
        error_log('Update company error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Firma güncellenemedi.'];
    }
}

function deleteCompany(string $companyId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM bus_company WHERE id = ? LIMIT 1");
        $stmt->execute([$companyId]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Firma bulunamadı.'];
        }

        $tripCheck = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE company_id = ?");
        $tripCheck->execute([$companyId]);
        if ($tripCheck->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Seferi bulunan firma silinemez.'];
        }

        $routeCheck = $pdo->prepare("SELECT COUNT(*) FROM routes WHERE company_id = ?");
        $routeCheck->execute([$companyId]);
        if ($routeCheck->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Rotası bulunan firma silinemez.'];
        }

        $pdo->prepare("DELETE FROM coupons WHERE company_id = ?")->execute([$companyId]);
        $pdo->prepare("UPDATE users SET company_id = NULL WHERE company_id = ?")->execute([$companyId]);
        $pdo->prepare("DELETE FROM bus_company WHERE id = ?")->execute([$companyId]);

        return ['success' => true, 'message' => 'Firma silindi.'];
    } catch (PDOException $e) {
        error_log('Delete company error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Firma silinemedi.'];
    }
}

function createCompanyAdmin(string $fullName, string $email, string $password, string $companyId): array {
    try {
        $pdo = db();

        $fullName = trim($fullName);
        $email = trim($email);
        if ($fullName === '' || $email === '' || $password === '') {
            return ['success' => false, 'message' => 'İsim, e-posta ve şifre zorunludur.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Geçerli bir e-posta adresi girin.'];
        }

        $companyStmt = $pdo->prepare("SELECT id FROM bus_company WHERE id = ? LIMIT 1");
        $companyStmt->execute([$companyId]);
        if (!$companyStmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Firma bulunamadı.'];
        }

        $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $emailCheck->execute([$email]);
        if ($emailCheck->fetchColumn()) {
            return ['success' => false, 'message' => 'Bu e-posta adresi zaten kullanılıyor.'];
        }

        $userId = 'comp_admin_' . uniqid();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, company_id, balance) VALUES (?, ?, ?, 'company_admin', ?, ?, 5000)");
        $stmt->execute([$userId, $fullName, $email, $hashedPassword, $companyId]);

        return ['success' => true, 'message' => 'Firma admini oluşturuldu.'];
    } catch (PDOException $e) {
        error_log('Create company admin error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Firma admini oluşturulamadı.'];
    }
}

function updateCompanyAdmin(string $userId, array $input): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'company_admin' LIMIT 1");
        $stmt->execute([$userId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$admin) {
            return ['success' => false, 'message' => 'Firma admini bulunamadı.'];
        }

        $fullName = trim($input['full_name'] ?? $admin['full_name']);
        $email = trim($input['email'] ?? $admin['email']);
        $companyId = $input['company_id'] ?? $admin['company_id'];

        if ($fullName === '' || $email === '') {
            return ['success' => false, 'message' => 'İsim ve e-posta boş olamaz.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'E-posta adresi geçerli değil.'];
        }

        $companyStmt = $pdo->prepare("SELECT id FROM bus_company WHERE id = ? LIMIT 1");
        $companyStmt->execute([$companyId]);
        if (!$companyStmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Firma bulunamadı.'];
        }

        $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $emailCheck->execute([$email, $userId]);
        if ($emailCheck->fetchColumn()) {
            return ['success' => false, 'message' => 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.'];
        }

        $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, company_id = ? WHERE id = ?");
        $update->execute([$fullName, $email, $companyId, $userId]);

        if (!empty($input['password'])) {
            $newPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([$newPassword, $userId]);
        }

        return ['success' => true, 'message' => 'Firma admini güncellendi.'];
    } catch (PDOException $e) {
        error_log('Update company admin error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Firma admini güncellenemedi.'];
    }
}

function deleteCompanyAdmin(string $userId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'company_admin' LIMIT 1");
        $stmt->execute([$userId]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Firma admini bulunamadı.'];
        }

        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

        return ['success' => true, 'message' => 'Firma admini silindi.'];
    } catch (PDOException $e) {
        error_log('Delete company admin error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Firma admini silinemedi.'];
    }
}

function createGlobalCoupon(array $input): array {
    try {
        $pdo = db();
        $code = strtoupper(trim($input['code'] ?? ''));
        $discount = (float)($input['discount'] ?? 0);
        $limit = (int)($input['usage_limit'] ?? 0);
        $expireRaw = trim($input['expire_date'] ?? '');

        if ($code === '' || $discount <= 0 || $discount >= 1) {
            return ['success' => false, 'message' => 'Kupon kodu ve indirim oranı geçerli olmalıdır.'];
        }
        if ($limit <= 0) {
            return ['success' => false, 'message' => 'Kullanım limiti pozitif olmalıdır.'];
        }
        $expire = DateTime::createFromFormat('Y-m-d\TH:i', $expireRaw);
        if (!$expire) {
            return ['success' => false, 'message' => 'Son kullanma tarihi geçerli olmalıdır.'];
        }

        $check = $pdo->prepare("SELECT id FROM coupons WHERE UPPER(code) = ? LIMIT 1");
        $check->execute([$code]);
        if ($check->fetchColumn()){
            return ['success' => false, 'message' => 'Bu kupon kodu zaten kullanılıyor.'];
        }

        $couponId = 'coupon_' . uniqid();
        $stmt = $pdo->prepare("INSERT INTO coupons (id, code, discount, usage_limit, expire_date, company_id, is_global) VALUES (?, ?, ?, ?, ?, NULL, 1)");
        $stmt->execute([$couponId, $code, $discount, $limit, $expire->format('Y-m-d H:i:s')]);

        return ['success' => true, 'message' => 'Global kupon oluşturuldu.'];
    } catch (PDOException $e) {
        error_log('Create global coupon error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Kupon oluşturulamadı.'];
    }
}

function updateGlobalCoupon(string $couponId, array $input): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ? AND is_global = 1 LIMIT 1");
        $stmt->execute([$couponId]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$coupon) {
            return ['success' => false, 'message' => 'Kupon bulunamadı.'];
        }

        $discount = (float)($input['discount'] ?? $coupon['discount']);
        $limit = (int)($input['usage_limit'] ?? $coupon['usage_limit']);
        $expireRaw = $input['expire_date'] ?? $coupon['expire_date'];
        $expire = DateTime::createFromFormat('Y-m-d\TH:i', $expireRaw) ?: DateTime::createFromFormat('Y-m-d H:i:s', $expireRaw);

        if ($discount <= 0 || $discount >= 1 || $limit <= 0 || !$expire) {
            return ['success' => false, 'message' => 'Kupon bilgileri geçerli değil.'];
        }

        $update = $pdo->prepare("UPDATE coupons SET discount = ?, usage_limit = ?, expire_date = ? WHERE id = ?");
        $update->execute([$discount, $limit, $expire->format('Y-m-d H:i:s'), $couponId]);

        return ['success' => true, 'message' => 'Kupon güncellendi.'];
    } catch (PDOException $e) {
        error_log('Update global coupon error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Kupon güncellenemedi.'];
    }
}

function deleteCoupon(string $couponId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM coupons WHERE id = ? LIMIT 1");
        $stmt->execute([$couponId]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Kupon bulunamadı.'];
        }

        $pdo->prepare("DELETE FROM user_coupons WHERE coupon_id = ?")->execute([$couponId]);
        $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$couponId]);

        return ['success' => true, 'message' => 'Kupon silindi.'];
    } catch (PDOException $e) {
        error_log('Delete coupon error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Kupon silinemedi.'];
    }
}
