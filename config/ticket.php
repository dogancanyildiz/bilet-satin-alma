<?php
/**
 * Ticket helper functions
 */

function getTicketsByUser(string $userId): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT 
                tickets.id,
                tickets.trip_id,
                tickets.status,
                tickets.total_price,
                tickets.original_price,
                tickets.discount_amount,
                tickets.coupon_code,
                tickets.passenger_name,
                tickets.passenger_tc,
                tickets.seat_number,
                tickets.created_at,
                trips.departure_time,
                trips.arrival_time,
                trips.price AS trip_price,
                routes.departure_city,
                routes.arrival_city,
                bus_company.name AS company_name
            FROM tickets
            INNER JOIN trips ON tickets.trip_id = trips.id
            INNER JOIN routes ON trips.route_id = routes.id
            INNER JOIN bus_company ON trips.company_id = bus_company.id
            WHERE tickets.user_id = ?
            ORDER BY tickets.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Tickets fetch error: ' . $e->getMessage());
        return [];
    }
}

function getTicketDetails(string $ticketId, ?PDO $pdo = null): ?array {
    try {
        $pdo = $pdo ?: db();
        $stmt = $pdo->prepare("
            SELECT 
                tickets.*,
                trips.departure_time,
                trips.arrival_time,
                trips.price AS trip_price,
                trips.company_id,
                routes.departure_city,
                routes.arrival_city,
                bus_company.name AS company_name,
                coupons.id AS coupon_id
            FROM tickets
            INNER JOIN trips ON tickets.trip_id = trips.id
            INNER JOIN routes ON trips.route_id = routes.id
            INNER JOIN bus_company ON trips.company_id = bus_company.id
            LEFT JOIN coupons ON tickets.coupon_code = coupons.code
            WHERE tickets.id = ?
            LIMIT 1
        ");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        return $ticket ?: null;
    } catch (PDOException $e) {
        error_log('Ticket detail fetch error: ' . $e->getMessage());
        return null;
    }
}

function cancelTicket(string $ticketId, array $currentUser): array {
    $pdo = db();

    try {
        $pdo->beginTransaction();

        $ticket = getTicketDetails($ticketId, $pdo);
        if (!$ticket) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Bilet bulunamadı.'];
        }

        $isOwner = $ticket['user_id'] === $currentUser['id'];
        $isAdmin = $currentUser['role'] === 'admin';

        if (!$isOwner && !$isAdmin) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Bu bileti iptal etmeye yetkiniz yok.'];
        }

        if ($ticket['status'] !== 'active') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Sadece aktif biletler iptal edilebilir.'];
        }

        $departureTime = DateTime::createFromFormat('Y-m-d H:i:s', $ticket['departure_time']);
        if ($departureTime) {
            $limit = new DateTime('+1 hour');
            if ($departureTime <= $limit) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Kalkışa 1 saatten az kaldığı için iptal edilemez.'];
            }
        }

        $pdo->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ?")
            ->execute([$ticketId]);

        $pdo->prepare("DELETE FROM booked_seats WHERE ticket_id = ?")
            ->execute([$ticketId]);

        $refundAmount = (float)$ticket['total_price'];
        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")
            ->execute([$refundAmount, $ticket['user_id']]);

        if (!empty($ticket['coupon_id'])) {
            $pdo->prepare("DELETE FROM user_coupons WHERE coupon_id = ? AND user_id = ?")
                ->execute([$ticket['coupon_id'], $ticket['user_id']]);
        }

        $pdo->commit();

        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $ticket['user_id']) {
            $_SESSION['balance'] = ($_SESSION['balance'] ?? 0) + $refundAmount;
        }

        return [
            'success' => true,
            'message' => 'Bilet başarıyla iptal edildi. ' . number_format($refundAmount, 2) . ' ₺ hesabınıza iade edildi.'
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Ticket cancellation error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Bilet iptali sırasında hata oluştu.'];
    }
}
