<?php
/**
 * Company specific helpers
 */

function getCompanyInfo(string $companyId): ?array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM bus_company WHERE id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Company info fetch error: ' . $e->getMessage());
        return null;
    }
}

function getCompanyRoutes(string $companyId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT id, departure_city, arrival_city, estimated_duration, base_price, status, created_at
            FROM routes
            WHERE company_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Company routes fetch error: ' . $e->getMessage());
        return [];
    }
}

function getCompanyTrips(string $companyId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT 
                trips.*,
                routes.departure_city,
                routes.arrival_city
            FROM trips
            INNER JOIN routes ON trips.route_id = routes.id
            WHERE trips.company_id = ?
            ORDER BY trips.departure_time DESC
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Company trips fetch error: ' . $e->getMessage());
        return [];
    }
}

function getCompanyCoupons(string $companyId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT 
                coupons.*,
                (
                    SELECT COUNT(*) 
                    FROM user_coupons uc 
                    WHERE uc.coupon_id = coupons.id
                ) AS usage_count
            FROM coupons
            WHERE company_id = ?
            ORDER BY expire_date ASC
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Company coupons fetch error: ' . $e->getMessage());
        return [];
    }
}

function createCompanyRoute(string $companyId, array $input): array {
    try {
        $pdo = db();
        $departure = trim($input['departure_city'] ?? '');
        $arrival = trim($input['arrival_city'] ?? '');
        $duration = (int)($input['estimated_duration'] ?? 0);
        $basePrice = (float)($input['base_price'] ?? 0);

        if ($departure === '' || $arrival === '') {
            return ['success' => false, 'message' => 'Kalkış ve varış şehirleri zorunludur.'];
        }
        if ($duration <= 0) {
            return ['success' => false, 'message' => 'Tahmini süre 0 dakikadan büyük olmalıdır.'];
        }
        if ($basePrice <= 0) {
            return ['success' => false, 'message' => 'Temel fiyat 0 TL üstünde olmalıdır.'];
        }

        $routeId = 'route_' . uniqid();
        $stmt = $pdo->prepare("INSERT INTO routes (id, departure_city, arrival_city, company_id, estimated_duration, base_price) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$routeId, $departure, $arrival, $companyId, $duration, $basePrice]);

        return ['success' => true, 'message' => 'Rota başarıyla oluşturuldu.'];
    } catch (PDOException $e) {
        error_log('Create route error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Rota oluşturma sırasında hata meydana geldi.'];
    }
}

function updateCompanyRoute(string $companyId, string $routeId, array $input): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM routes WHERE id = ? AND company_id = ? LIMIT 1");
        $stmt->execute([$routeId, $companyId]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Rota bulunamadı.'];
        }

        $departure = trim($input['departure_city'] ?? '');
        $arrival = trim($input['arrival_city'] ?? '');
        $duration = (int)($input['estimated_duration'] ?? 0);
        $basePrice = (float)($input['base_price'] ?? 0);
        $status = $input['status'] ?? 'active';

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }
        if ($departure === '' || $arrival === '' || $duration <= 0 || $basePrice <= 0) {
            return ['success' => false, 'message' => 'Rota bilgileri geçerli değil.'];
        }

        $update = $pdo->prepare("UPDATE routes SET departure_city = ?, arrival_city = ?, estimated_duration = ?, base_price = ?, status = ? WHERE id = ?");
        $update->execute([$departure, $arrival, $duration, $basePrice, $status, $routeId]);

        return ['success' => true, 'message' => 'Rota güncellendi.'];
    } catch (PDOException $e) {
        error_log('Update route error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Rota güncellenemedi.'];
    }
}

function deleteCompanyRoute(string $companyId, string $routeId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM routes WHERE id = ? AND company_id = ? LIMIT 1");
        $stmt->execute([$routeId, $companyId]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Rota bulunamadı.'];
        }

        $tripCheck = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE route_id = ?");
        $tripCheck->execute([$routeId]);
        if ($tripCheck->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Rota için tanımlı seferler bulunduğundan silinemiyor.'];
        }

        $delete = $pdo->prepare("DELETE FROM routes WHERE id = ?");
        $delete->execute([$routeId]);

        return ['success' => true, 'message' => 'Rota silindi.'];
    } catch (PDOException $e) {
        error_log('Delete route error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Rota silinemedi.'];
    }
}

function createCompanyTrip(string $companyId, array $input): array {
    try {
        $pdo = db();
        $routeId = $input['route_id'] ?? '';
        $stmt = $pdo->prepare("SELECT id FROM routes WHERE id = ? AND company_id = ? LIMIT 1");
        $stmt->execute([$routeId, $companyId]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Rota bulunamadı.'];
        }

        $depRaw = trim($input['departure_time'] ?? '');
        $arrRaw = trim($input['arrival_time'] ?? '');
        $price = (float)($input['price'] ?? 0);
        $capacity = (int)($input['capacity'] ?? 0);

        $dep = DateTime::createFromFormat('Y-m-d\TH:i', $depRaw);
        $arr = DateTime::createFromFormat('Y-m-d\TH:i', $arrRaw);
        if (!$dep || !$arr || $arr <= $dep) {
            return ['success' => false, 'message' => 'Varış zamanı kalkıştan sonra olmalıdır.'];
        }
        if ($price <= 0 || $capacity <= 0) {
            return ['success' => false, 'message' => 'Fiyat ve kapasite pozitif olmalıdır.'];
        }

        $tripId = 'trip_' . uniqid();
        $insert = $pdo->prepare("INSERT INTO trips (id, route_id, company_id, departure_time, arrival_time, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$tripId, $routeId, $companyId, $dep->format('Y-m-d H:i:s'), $arr->format('Y-m-d H:i:s'), $price, $capacity]);

        return ['success' => true, 'message' => 'Sefer başarıyla oluşturuldu.'];
    } catch (PDOException $e) {
        error_log('Create trip error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Sefer oluşturulamadı.'];
    }
}

function updateCompanyTrip(string $companyId, string $tripId, array $input): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND company_id = ? LIMIT 1");
        $stmt->execute([$tripId, $companyId]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trip) {
            return ['success' => false, 'message' => 'Sefer bulunamadı.'];
        }

        $routeId = $input['route_id'] ?? $trip['route_id'];
        $routeCheck = $pdo->prepare("SELECT id FROM routes WHERE id = ? AND company_id = ? LIMIT 1");
        $routeCheck->execute([$routeId, $companyId]);
        if (!$routeCheck->fetchColumn()) {
            return ['success' => false, 'message' => 'Rota bulunamadı.'];
        }

        $depRaw = trim($input['departure_time'] ?? $trip['departure_time']);
        $arrRaw = trim($input['arrival_time'] ?? $trip['arrival_time']);
        $price = (float)($input['price'] ?? $trip['price']);
        $capacity = (int)($input['capacity'] ?? $trip['capacity']);
        $status = $input['status'] ?? $trip['status'];
        if (!in_array($status, ['scheduled', 'in_progress', 'completed', 'cancelled'], true)) {
            $status = $trip['status'];
        }

        $dep = DateTime::createFromFormat('Y-m-d\TH:i', $depRaw) ?: DateTime::createFromFormat('Y-m-d H:i:s', $depRaw);
        $arr = DateTime::createFromFormat('Y-m-d\TH:i', $arrRaw) ?: DateTime::createFromFormat('Y-m-d H:i:s', $arrRaw);
        if (!$dep || !$arr || $arr <= $dep) {
            return ['success' => false, 'message' => 'Varış zamanı kalkıştan sonra olmalıdır.'];
        }
        if ($price <= 0 || $capacity <= 0) {
            return ['success' => false, 'message' => 'Fiyat ve kapasite pozitif olmalıdır.'];
        }

        $update = $pdo->prepare("UPDATE trips SET route_id = ?, departure_time = ?, arrival_time = ?, price = ?, capacity = ?, status = ? WHERE id = ?");
        $update->execute([$routeId, $dep->format('Y-m-d H:i:s'), $arr->format('Y-m-d H:i:s'), $price, $capacity, $status, $tripId]);

        return ['success' => true, 'message' => 'Sefer güncellendi.'];
    } catch (PDOException $e) {
        error_log('Update trip error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Sefer güncellenemedi.'];
    }
}

function deleteCompanyTrip(string $companyId, string $tripId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM trips WHERE id = ? AND company_id = ? LIMIT 1");
        $stmt->execute([$tripId, $companyId]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Sefer bulunamadı.'];
        }

        $ticketCheck = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE trip_id = ? AND status = 'active'");
        $ticketCheck->execute([$tripId]);
        if ($ticketCheck->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Aktif bileti olan sefer silinemez.'];
        }

        $pdo->prepare("DELETE FROM booked_seats WHERE trip_id = ?")->execute([$tripId]);
        $pdo->prepare("DELETE FROM tickets WHERE trip_id = ?")->execute([$tripId]);
        $pdo->prepare("DELETE FROM trips WHERE id = ?")->execute([$tripId]);

        return ['success' => true, 'message' => 'Sefer silindi.'];
    } catch (PDOException $e) {
        error_log('Delete trip error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Sefer silinemedi.'];
    }
}

function createCompanyCoupon(string $companyId, array $input): array {
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

        $exists = $pdo->prepare("SELECT id FROM coupons WHERE UPPER(code) = ? LIMIT 1");
        $exists->execute([$code]);
        if ($exists->fetchColumn()) {
            return ['success' => false, 'message' => 'Bu kupon kodu zaten kullanılıyor.'];
        }

        $couponId = 'coupon_' . uniqid();
        $insert = $pdo->prepare("INSERT INTO coupons (id, code, discount, usage_limit, expire_date, company_id, is_global) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $insert->execute([$couponId, $code, $discount, $limit, $expire->format('Y-m-d H:i:s'), $companyId]);

        return ['success' => true, 'message' => 'Kupon oluşturuldu.'];
    } catch (PDOException $e) {
        error_log('Create coupon error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Kupon oluşturulamadı.'];
    }
}

function updateCompanyCoupon(string $companyId, string $couponId, array $input): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ? AND company_id = ? LIMIT 1");
        $stmt->execute([$couponId, $companyId]);
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
        error_log('Update coupon error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Kupon güncellenemedi.'];
    }
}

function deleteCompanyCoupon(string $companyId, string $couponId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM coupons WHERE id = ? AND company_id = ? LIMIT 1");
        $stmt->execute([$couponId, $companyId]);
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
