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
