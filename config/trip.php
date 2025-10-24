<?php
/**
 * Trip and route related database helpers
 */

function searchTrips(string $departureCity, string $arrivalCity, string $departureDate, int $passengerCount = 1): array {
    $pdo = db();

    $sql = "
        SELECT 
            trips.id,
            trips.route_id,
            trips.company_id,
            trips.departure_time,
            trips.arrival_time,
            trips.price,
            trips.capacity,
            trips.status,
            routes.departure_city,
            routes.arrival_city,
            routes.estimated_duration,
            bus_company.name AS company_name,
            COALESCE(seat_counts.reserved_seats, 0) AS reserved_seats
        FROM trips
        INNER JOIN routes ON trips.route_id = routes.id
        INNER JOIN bus_company ON trips.company_id = bus_company.id
        LEFT JOIN (
            SELECT 
                booked_seats.trip_id,
                COUNT(booked_seats.id) AS reserved_seats
            FROM booked_seats
            GROUP BY booked_seats.trip_id
        ) AS seat_counts ON seat_counts.trip_id = trips.id
        WHERE routes.status = 'active'
          AND trips.status IN ('scheduled', 'in_progress')
          AND DATE(trips.departure_time) = :departure_date
          AND routes.departure_city LIKE :departure_city COLLATE NOCASE
          AND routes.arrival_city LIKE :arrival_city COLLATE NOCASE
        ORDER BY trips.departure_time ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':departure_date' => $departureDate,
        ':departure_city' => '%' . $departureCity . '%',
        ':arrival_city' => '%' . $arrivalCity . '%'
    ]);

    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results = [];

    foreach ($trips as $trip) {
        $trip['available_seats'] = max(0, (int)$trip['capacity'] - (int)$trip['reserved_seats']);
        if ($passengerCount > 0 && $trip['available_seats'] < $passengerCount) {
            continue;
        }
        $results[] = $trip;
    }

    return $results;
}

function getTripById(string $tripId): ?array {
    $pdo = db();

    $sql = "
        SELECT 
            trips.id,
            trips.route_id,
            trips.company_id,
            trips.departure_time,
            trips.arrival_time,
            trips.price,
            trips.capacity,
            trips.status,
            routes.departure_city,
            routes.arrival_city,
            routes.estimated_duration,
            bus_company.name AS company_name,
            COALESCE(seat_counts.reserved_seats, 0) AS reserved_seats
        FROM trips
        INNER JOIN routes ON trips.route_id = routes.id
        INNER JOIN bus_company ON trips.company_id = bus_company.id
        LEFT JOIN (
            SELECT 
                booked_seats.trip_id,
                COUNT(booked_seats.id) AS reserved_seats
            FROM booked_seats
            GROUP BY booked_seats.trip_id
        ) AS seat_counts ON seat_counts.trip_id = trips.id
        WHERE trips.id = :trip_id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':trip_id' => $tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        return null;
    }

    $trip['available_seats'] = max(0, (int)$trip['capacity'] - (int)$trip['reserved_seats']);

    return $trip;
}

function getTripBookedSeats(string $tripId): array {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT seat_number FROM booked_seats WHERE trip_id = ?");
    $stmt->execute([$tripId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function purchaseTripTicket(
    string $tripId,
    string $userId,
    int $seatNumber,
    string $passengerName,
    ?string $passengerTc = null,
    ?string $couponInput = null
): array {
    $pdo = db();

    try {
        $pdo->beginTransaction();

        $tripStmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? LIMIT 1");
        $tripStmt->execute([$tripId]);
        $trip = $tripStmt->fetch(PDO::FETCH_ASSOC);
        if (!$trip) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Sefer bulunamadı.'];
        }

        if (!in_array($trip['status'], ['scheduled', 'in_progress'], true)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Bu sefer için bilet satışı kapalı.'];
        }

        $departureTime = DateTime::createFromFormat('Y-m-d H:i:s', $trip['departure_time']);
        if ($departureTime && $departureTime <= new DateTime()) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Geçmiş seferlere bilet alınamaz.'];
        }

        $capacity = (int)$trip['capacity'];
        if ($seatNumber < 1 || $seatNumber > $capacity) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Geçersiz koltuk numarası seçtiniz.'];
        }

        $seatCheck = $pdo->prepare("SELECT 1 FROM booked_seats WHERE trip_id = ? AND seat_number = ? LIMIT 1");
        $seatCheck->execute([$tripId, $seatNumber]);
        if ($seatCheck->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Seçilen koltuk zaten rezerve edilmiş.'];
        }

        $userStmt = $pdo->prepare("SELECT id, full_name, balance FROM users WHERE id = ? LIMIT 1");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        $tripPrice = (float)$trip['price'];
        $discountAmount = 0.0;
        $couponCode = null;
        $coupon = null;

        if ($couponInput) {
            $couponStmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = UPPER(?) LIMIT 1");
            $couponStmt->execute([$couponInput]);
            $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);
            if (!$coupon) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Kupon kodu bulunamadı.'];
            }

            if ((int)$coupon['is_global'] !== 1 && $coupon['company_id'] !== $trip['company_id']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Bu kupon seçilen sefer için geçerli değil.'];
            }

            $expireDate = DateTime::createFromFormat('Y-m-d H:i:s', $coupon['expire_date']);
            if ($expireDate && $expireDate < new DateTime()) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Kupon süresi dolmuş.'];
            }

            $usageCountStmt = $pdo->prepare("SELECT COUNT(*) FROM user_coupons WHERE coupon_id = ?");
            $usageCountStmt->execute([$coupon['id']]);
            $usageCount = (int)$usageCountStmt->fetchColumn();
            if ($usageCount >= (int)$coupon['usage_limit']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Kupon kullanım limiti dolmuş.'];
            }

            $userCouponStmt = $pdo->prepare("SELECT COUNT(*) FROM user_coupons WHERE coupon_id = ? AND user_id = ?");
            $userCouponStmt->execute([$coupon['id'], $userId]);
            if ((int)$userCouponStmt->fetchColumn() > 0) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Bu kuponu zaten kullandınız. Aktif bileti iptal ederseniz kupon hakkı geri gelir.'];
            }

            $discountAmount = round($tripPrice * (float)$coupon['discount'], 2);
            $couponCode = $coupon['code'];
        }

        $totalPrice = max(0.0, round($tripPrice - $discountAmount, 2));
        if ((float)$user['balance'] < $totalPrice) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Yetersiz bakiye. Lütfen bakiye yükleyin.'];
        }

        $ticketId = 'ticket_' . uniqid();
        $bookedSeatId = 'seat_' . uniqid();
        $userCouponId = $couponCode ? 'uc_' . uniqid() : null;

        $ticketStmt = $pdo->prepare("INSERT INTO tickets (
                id, trip_id, user_id, status, total_price, original_price, discount_amount, coupon_code,
                passenger_name, passenger_tc, seat_number
            ) VALUES (?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?)");
        $ticketStmt->execute([
            $ticketId,
            $tripId,
            $userId,
            $totalPrice,
            $tripPrice,
            $discountAmount,
            $couponCode,
            $passengerName,
            $passengerTc,
            $seatNumber
        ]);

        $seatInsert = $pdo->prepare("INSERT INTO booked_seats (id, trip_id, ticket_id, seat_number) VALUES (?, ?, ?, ?)");
        $seatInsert->execute([$bookedSeatId, $tripId, $ticketId, $seatNumber]);

        $balanceUpdate = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $balanceUpdate->execute([$totalPrice, $userId]);

        if ($couponCode && $userCouponId) {
            $couponInsert = $pdo->prepare("INSERT INTO user_coupons (id, coupon_id, user_id) VALUES (?, ?, ?)");
            $couponInsert->execute([$userCouponId, $coupon['id'], $userId]);
        }

        $pdo->commit();

        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $userId) {
            $_SESSION['balance'] = ($_SESSION['balance'] ?? 0) - $totalPrice;
        }

        return [
            'success' => true,
            'message' => 'Bilet başarıyla satın alındı.',
            'ticket_id' => $ticketId,
            'total_price' => $totalPrice,
            'discount' => $discountAmount,
            'coupon_code' => $couponCode
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Ticket purchase error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Bilet satın alma sırasında hata oluştu.'];
    }
}
