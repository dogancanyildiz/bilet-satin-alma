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
