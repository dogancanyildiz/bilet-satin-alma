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
            SELECT *
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
