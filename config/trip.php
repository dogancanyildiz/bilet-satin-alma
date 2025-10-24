<?php
/**
 * Trip and route related database helpers
 */

function searchTrips(string $departureCity, string $arrivalCity, string $departureDate): array {
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
                tickets.trip_id AS trip_id,
                COUNT(booked_seats.id) AS reserved_seats
            FROM booked_seats
            INNER JOIN tickets ON booked_seats.ticket_id = tickets.id
            WHERE tickets.status = 'active'
            GROUP BY tickets.trip_id
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

    foreach ($trips as &$trip) {
        $trip['available_seats'] = max(0, (int)$trip['capacity'] - (int)$trip['reserved_seats']);
    }

    return $trips;
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
                tickets.trip_id AS trip_id,
                COUNT(booked_seats.id) AS reserved_seats
            FROM booked_seats
            INNER JOIN tickets ON booked_seats.ticket_id = tickets.id
            WHERE tickets.status = 'active'
            GROUP BY tickets.trip_id
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
