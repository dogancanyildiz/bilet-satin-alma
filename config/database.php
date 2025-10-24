<?php
/**
 * Database initialization and schema creation
 * Bu dosya SQLite veritabanını ve tablolarını oluşturur
 */

function initializeDatabase() {
    try {
        $pdo = db();
        $pdo->exec('PRAGMA foreign_keys = ON');
        
        // Users tablosu - Kullanıcılar (Admin, Firma Admin, User rolleri)
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT NOT NULL CHECK (role IN ('admin', 'company_admin', 'user')),
            password TEXT NOT NULL,
            company_id TEXT NULLABLE,
            balance DECIMAL DEFAULT 500,
            phone TEXT,
            birth_date DATE,
            gender TEXT CHECK (gender IN ('male', 'female', 'other')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES bus_company(id)
        )");

        // Bus Company tablosu - Otobüs firmaları
        $pdo->exec("CREATE TABLE IF NOT EXISTS bus_company (
            id TEXT PRIMARY KEY,
            name TEXT UNIQUE NOT NULL,
            logo_path TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Routes tablosu - Rotalar
        $pdo->exec("CREATE TABLE IF NOT EXISTS routes (
            id TEXT PRIMARY KEY,
            departure_city TEXT NOT NULL,
            arrival_city TEXT NOT NULL,
            company_id TEXT NOT NULL,
            estimated_duration INTEGER NOT NULL,
            base_price DECIMAL NOT NULL,
            status TEXT DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES bus_company(id)
        )");

        // Trips tablosu - Seferler
        $pdo->exec("CREATE TABLE IF NOT EXISTS trips (
            id TEXT PRIMARY KEY,
            route_id TEXT NOT NULL,
            company_id TEXT NOT NULL,
            departure_time DATETIME NOT NULL,
            arrival_time DATETIME NOT NULL,
            price DECIMAL NOT NULL,
            capacity INTEGER NOT NULL,
            status TEXT DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'in_progress', 'completed', 'cancelled')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (route_id) REFERENCES routes(id),
            FOREIGN KEY (company_id) REFERENCES bus_company(id)
        )");

        // Tickets tablosu - Biletler
        $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
            id TEXT PRIMARY KEY,
            trip_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            status TEXT DEFAULT 'active' CHECK (status IN ('active', 'cancelled', 'expired')),
            total_price DECIMAL NOT NULL,
            original_price DECIMAL NOT NULL,
            discount_amount DECIMAL DEFAULT 0,
            coupon_code TEXT,
            passenger_name TEXT NOT NULL,
            passenger_tc TEXT,
            seat_number INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (trip_id) REFERENCES trips(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (coupon_code) REFERENCES coupons(code)
        )");

        // Booked Seats tablosu - Rezerve edilmiş koltuklar
        $pdo->exec("CREATE TABLE IF NOT EXISTS booked_seats (
            id TEXT PRIMARY KEY,
            trip_id TEXT NOT NULL,
            ticket_id TEXT NOT NULL,
            seat_number INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (trip_id) REFERENCES trips(id),
            FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        )");

        // Coupons tablosu - İndirim kuponları
        $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
            id TEXT PRIMARY KEY,
            code TEXT UNIQUE NOT NULL,
            discount REAL NOT NULL,
            usage_limit INTEGER NOT NULL,
            expire_date DATETIME NOT NULL,
            company_id TEXT NULL,
            is_global INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES bus_company(id)
        )");
        ensureCouponExtendedSchema($pdo);
        ensureBookedSeatsSchema($pdo);
        ensureTripsSchema($pdo);
        ensureTicketsSchema($pdo);

        // User Coupons tablosu - Kullanıcıların kullandığı kuponlar
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_coupons (
            id TEXT PRIMARY KEY,
            coupon_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (coupon_id) REFERENCES coupons(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");

        // Indeksler ve unique constraints
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users(email)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_trips_departure ON trips(departure_time)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_routes_departure_arrival ON routes(departure_city, arrival_city)");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_booked_seats_unique ON booked_seats(ticket_id, seat_number)");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_booked_trip_seat ON booked_seats(trip_id, seat_number)");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_coupons_company ON coupons(company_id)");

        insertSampleData();

        return true;
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

/**
 * Sample data ekleme fonksiyonu
 */
function insertSampleData() {
    try {
        $pdo = db();

        // Admin kullanıcısı oluştur
        $adminStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $adminStmt->execute(['admin@test.com']);
        if (!$adminStmt->fetchColumn()) {
            $adminId = 'admin_' . uniqid();
            $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, balance) 
                           VALUES (?, 'System Admin', 'admin@test.com', 'admin', ?, 10000)")
                ->execute([$adminId, password_hash('123456', PASSWORD_DEFAULT)]);
        }

        // Örnek otobüs firması
        $companyStmt = $pdo->prepare("SELECT id FROM bus_company WHERE name = ? LIMIT 1");
        $companyStmt->execute(['Metro Turizm']);
        $companyId = $companyStmt->fetchColumn();
        if (!$companyId) {
            $companyId = 'company_' . uniqid();
            $pdo->prepare("INSERT INTO bus_company (id, name) VALUES (?, 'Metro Turizm')")
                ->execute([$companyId]);
        }

        // Örnek rota
        $routeStmt = $pdo->prepare("SELECT id FROM routes WHERE departure_city = ? AND arrival_city = ? AND company_id = ? LIMIT 1");
        $routeStmt->execute(['İstanbul', 'Ankara', $companyId]);
        $routeId = $routeStmt->fetchColumn();
        if (!$routeId) {
            $routeId = 'route_' . uniqid();
            $pdo->prepare("INSERT INTO routes (id, departure_city, arrival_city, company_id, estimated_duration, base_price) 
                           VALUES (?, 'İstanbul', 'Ankara', ?, 270, 150.00)")
                ->execute([$routeId, $companyId]);
        }

        // Firma admin kullanıcısı
        $companyAdminStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $companyAdminStmt->execute(['company@test.com']);
        if (!$companyAdminStmt->fetchColumn()) {
            $companyAdminId = 'comp_admin_' . uniqid();
            $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, company_id, balance) 
                           VALUES (?, 'Metro Admin', 'company@test.com', 'company_admin', ?, ?, 5000)")
                ->execute([$companyAdminId, password_hash('123456', PASSWORD_DEFAULT), $companyId]);
        }

        // Örnek kullanıcı
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute(['user@test.com']);
        if (!$userStmt->fetchColumn()) {
            $userId = 'user_' . uniqid();
            $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, balance) 
                           VALUES (?, 'Ahmet Yılmaz', 'user@test.com', 'user', ?, 1000)")
                ->execute([$userId, password_hash('123456', PASSWORD_DEFAULT)]);
        }

        // Örnek sefer
        $departureDate = date('Y-m-d', strtotime('+1 day'));
        $tripCheck = $pdo->prepare("SELECT id FROM trips WHERE route_id = ? AND DATE(departure_time) = ? LIMIT 1");
        $tripCheck->execute([$routeId, $departureDate]);
        if (!$tripCheck->fetchColumn()) {
            $tripId = 'trip_' . uniqid();
            $departureTime = $departureDate . ' 09:00:00';
            $arrivalTime = $departureDate . ' 13:30:00';
            $pdo->prepare("INSERT INTO trips (id, route_id, company_id, departure_time, arrival_time, price, capacity) 
                           VALUES (?, ?, ?, ?, ?, 160.00, 40)")
                ->execute([$tripId, $routeId, $companyId, $departureTime, $arrivalTime]);
        }

        // Örnek kupon
        $couponStmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ? LIMIT 1");
        $couponStmt->execute(['HOSGELDIN20']);
        if (!$couponStmt->fetchColumn()) {
            $globalCouponId = 'coupon_' . uniqid();
            $pdo->prepare("INSERT INTO coupons (id, code, discount, usage_limit, expire_date, company_id, is_global) 
                           VALUES (?, 'HOSGELDIN20', 0.20, 100, ?, NULL, 1)")
                ->execute([$globalCouponId, date('Y-m-d H:i:s', strtotime('+30 days'))]);
        }

        $companyCouponStmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ? LIMIT 1");
        $companyCouponStmt->execute(['METRO10']);
        if (!$companyCouponStmt->fetchColumn()) {
            $companyCouponId = 'coupon_' . uniqid();
            $pdo->prepare("INSERT INTO coupons (id, code, discount, usage_limit, expire_date, company_id, is_global) 
                           VALUES (?, 'METRO10', 0.10, 50, ?, ?, 0)")
                ->execute([$companyCouponId, date('Y-m-d H:i:s', strtotime('+20 days')), $companyId]);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Sample data insertion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Eski veritabanlarında kupon şemasını güncelle
 */
function ensureCouponExtendedSchema(PDO $pdo) {
    try {
        $columns = $pdo->query("PRAGMA table_info(coupons)")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');

        if (!in_array('company_id', $columnNames, true)) {
            $pdo->exec("ALTER TABLE coupons ADD COLUMN company_id TEXT NULL");
        }
        if (!in_array('is_global', $columnNames, true)) {
            $pdo->exec("ALTER TABLE coupons ADD COLUMN is_global INTEGER DEFAULT 0");
            $pdo->exec("UPDATE coupons SET is_global = 1 WHERE company_id IS NULL");
        }
    } catch (PDOException $e) {
        error_log('Coupon schema update error: ' . $e->getMessage());
    }
}

/**
 * Veritabanını sıfırla (geliştirme aşamasında kullanım için)
 */
function resetDatabase() {
    try {
        $pdo = db();
        
        // Tabloları sil
        $tables = ['user_coupons', 'booked_seats', 'tickets', 'trips', 'routes', 'coupons', 'users', 'bus_company'];
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS $table");
        }
        
        // Yeniden oluştur
        initializeDatabase();
        
        return true;
    } catch (PDOException $e) {
        error_log("Database reset error: " . $e->getMessage());
        return false;
    }
}

/**
 * Booked seats tablosu için şema güncelleyici
 */

function ensureTicketsSchema(PDO $pdo) {
    try {
        $columns = $pdo->query("PRAGMA table_info(tickets)")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');

        if (!in_array('original_price', $columnNames, true)) {
            $pdo->exec("ALTER TABLE tickets ADD COLUMN original_price DECIMAL DEFAULT 0");
            $pdo->exec("UPDATE tickets SET original_price = total_price WHERE original_price = 0");
        }
        if (!in_array('discount_amount', $columnNames, true)) {
            $pdo->exec("ALTER TABLE tickets ADD COLUMN discount_amount DECIMAL DEFAULT 0");
        }
        if (!in_array('coupon_code', $columnNames, true)) {
            $pdo->exec("ALTER TABLE tickets ADD COLUMN coupon_code TEXT");
        }
        if (!in_array('passenger_name', $columnNames, true)) {
            $pdo->exec("ALTER TABLE tickets ADD COLUMN passenger_name TEXT DEFAULT ''");
            $pdo->exec("UPDATE tickets SET passenger_name = '' WHERE passenger_name IS NULL");
        }
        if (!in_array('passenger_tc', $columnNames, true)) {
            $pdo->exec("ALTER TABLE tickets ADD COLUMN passenger_tc TEXT");
        }
    } catch (PDOException $e) {
        error_log('Tickets schema update error: ' . $e->getMessage());
    }
}

function ensureBookedSeatsSchema(PDO $pdo) {
    try {
        $columns = $pdo->query("PRAGMA table_info(booked_seats)")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');

        if (!in_array('trip_id', $columnNames, true)) {
            $pdo->exec("ALTER TABLE booked_seats ADD COLUMN trip_id TEXT");
            $pdo->exec("UPDATE booked_seats SET trip_id = (
                SELECT tickets.trip_id FROM tickets WHERE tickets.id = booked_seats.ticket_id
            ) WHERE trip_id IS NULL");
        }

        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_booked_trip_seat ON booked_seats(trip_id, seat_number)");
    } catch (PDOException $e) {
        error_log('Booked seats schema update error: ' . $e->getMessage());
    }
}

function ensureTripsSchema(PDO $pdo) {
    try {
        $columns = $pdo->query("PRAGMA table_info(trips)")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');

        if (!in_array('route_id', $columnNames, true)) {
            $pdo->exec("ALTER TABLE trips ADD COLUMN route_id TEXT");
            $columns = $pdo->query("PRAGMA table_info(trips)")->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'name');
        }

        if (!in_array('status', $columnNames, true)) {
            $pdo->exec("ALTER TABLE trips ADD COLUMN status TEXT DEFAULT 'scheduled'");
            $pdo->exec("UPDATE trips SET status = 'scheduled' WHERE status IS NULL");
        }

        $hasDeparture = in_array('departure_city', $columnNames, true);
        $hasDestination = in_array('destination_city', $columnNames, true);
        if (!$hasDeparture || !$hasDestination || !in_array('route_id', $columnNames, true)) {
            return;
        }

        $trips = $pdo->query("SELECT id, company_id, route_id, departure_city, destination_city, price, departure_time, arrival_time FROM trips")
            ->fetchAll(PDO::FETCH_ASSOC);

        $routeSelect = $pdo->prepare("SELECT id FROM routes WHERE company_id = ? AND departure_city = ? AND arrival_city = ? LIMIT 1");
        $routeInsert = $pdo->prepare(
            "INSERT INTO routes (id, departure_city, arrival_city, company_id, estimated_duration, base_price, status) VALUES (?, ?, ?, ?, ?, ?, 'active')"
        );
        $tripUpdate = $pdo->prepare("UPDATE trips SET route_id = ? WHERE id = ?");

        foreach ($trips as $trip) {
            if (!empty($trip['route_id'])) {
                continue;
            }
            if (empty($trip['departure_city']) || empty($trip['destination_city'])) {
                continue;
            }

            $routeSelect->execute([$trip['company_id'], $trip['departure_city'], $trip['destination_city']]);
            $routeId = $routeSelect->fetchColumn();

            if (!$routeId) {
                $routeId = 'route_' . uniqid();
                $duration = 120;
                if (!empty($trip['departure_time']) && !empty($trip['arrival_time'])) {
                    $dep = DateTime::createFromFormat('Y-m-d H:i:s', $trip['departure_time']);
                    $arr = DateTime::createFromFormat('Y-m-d H:i:s', $trip['arrival_time']);
                    if ($dep && $arr && $arr > $dep) {
                        $duration = max(1, (int)(($arr->getTimestamp() - $dep->getTimestamp()) / 60));
                    }
                }
                $basePrice = !empty($trip['price']) ? (float)$trip['price'] : 100;
                $routeInsert->execute([
                    $routeId,
                    $trip['departure_city'],
                    $trip['destination_city'],
                    $trip['company_id'],
                    $duration,
                    $basePrice
                ]);
            }

            $tripUpdate->execute([$routeId, $trip['id']]);
        }
    } catch (PDOException $e) {
        error_log('Trips schema update error: ' . $e->getMessage());
    }
}
