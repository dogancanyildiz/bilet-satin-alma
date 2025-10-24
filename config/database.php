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
            ticket_id TEXT NOT NULL,
            seat_number INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_coupons_company ON coupons(company_id)");

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
        $adminId = 'admin_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO users (id, full_name, email, role, password, balance) 
                      VALUES (?, 'System Admin', 'admin@test.com', 'admin', ?, 10000)")
            ->execute([$adminId, password_hash('123456', PASSWORD_DEFAULT)]);

        // Örnek otobüs firması
        $companyId = 'company_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO bus_company (id, name) VALUES (?, 'Metro Turizm')")
            ->execute([$companyId]);

        // Örnek rota
        $routeId = 'route_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO routes (id, departure_city, arrival_city, company_id, estimated_duration, base_price) 
                      VALUES (?, 'İstanbul', 'Ankara', ?, 270, 150.00)")
            ->execute([$routeId, $companyId]);

        // Firma admin kullanıcısı
        $companyAdminId = 'comp_admin_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO users (id, full_name, email, role, password, company_id, balance) 
                      VALUES (?, 'Metro Admin', 'company@test.com', 'company_admin', ?, ?, 5000)")
            ->execute([$companyAdminId, password_hash('123456', PASSWORD_DEFAULT), $companyId]);

        // Örnek kullanıcı
        $userId = 'user_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO users (id, full_name, email, role, password, balance) 
                      VALUES (?, 'Ahmet Yılmaz', 'user@test.com', 'user', ?, 1000)")
            ->execute([$userId, password_hash('123456', PASSWORD_DEFAULT)]);

        // Örnek sefer
        $tripId = 'trip_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO trips (id, route_id, company_id, departure_time, arrival_time, price, capacity) 
                      VALUES (?, ?, ?, ?, ?, 160.00, 40)")
            ->execute([
                $tripId,
                $routeId,
                $companyId,
                date('Y-m-d H:i:s', strtotime('+1 day 09:00')),
                date('Y-m-d H:i:s', strtotime('+1 day 13:30'))
            ]);

        // Örnek kupon
        $globalCouponId = 'coupon_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO coupons (id, code, discount, usage_limit, expire_date, company_id, is_global) 
                      VALUES (?, 'HOSGELDIN20', 0.20, 100, ?, NULL, 1)")
            ->execute([$globalCouponId, date('Y-m-d H:i:s', strtotime('+30 days'))]);

        $companyCouponId = 'coupon_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO coupons (id, code, discount, usage_limit, expire_date, company_id, is_global) 
                      VALUES (?, 'METRO10', 0.10, 50, ?, ?, 0)")
            ->execute([$companyCouponId, date('Y-m-d H:i:s', strtotime('+20 days')), $companyId]);

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
        insertSampleData();
        
        return true;
    } catch (PDOException $e) {
        error_log("Database reset error: " . $e->getMessage());
        return false;
    }
}
