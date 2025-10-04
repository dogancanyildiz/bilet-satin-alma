<?php
/**
 * Database initialization and schema creation
 * Bu dosya SQLite veritabanını ve tablolarını oluşturur
 */

function initializeDatabase() {
    try {
        $pdo = db();
        
        // Users tablosu - Kullanıcılar (Admin, Firma Admin, User rolleri)
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT NOT NULL CHECK (role IN ('admin', 'company_admin', 'user')),
            password TEXT NOT NULL,
            company_id TEXT NULLABLE,
            balance DECIMAL DEFAULT 500,
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

        // Trips tablosu - Seferler
        $pdo->exec("CREATE TABLE IF NOT EXISTS trips (
            id TEXT PRIMARY KEY,
            company_id TEXT NOT NULL,
            destination_city TEXT NOT NULL,
            arrival_time DATETIME NOT NULL,
            departure_time DATETIME NOT NULL,
            departure_city TEXT NOT NULL,
            price DECIMAL NOT NULL,
            capacity INTEGER NOT NULL,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES bus_company(id)
        )");

        // Tickets tablosu - Biletler
        $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
            id TEXT PRIMARY KEY,
            trip_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            status TEXT DEFAULT 'active' CHECK (status IN ('active', 'cancelled', 'expired')),
            total_price DECIMAL NOT NULL,
            seat_number INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (trip_id) REFERENCES trips(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

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
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_trips_route ON trips(departure_city, destination_city)");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_booked_seats_unique ON booked_seats(ticket_id, seat_number)");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code)");

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
                      VALUES (?, 'System Admin', 'admin@bilet.com', 'admin', ?, 10000)")
            ->execute([$adminId, password_hash('admin123', PASSWORD_DEFAULT)]);

        // Örnek otobüs firması
        $companyId = 'company_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO bus_company (id, name) VALUES (?, 'Metro Turizm')")
            ->execute([$companyId]);

        // Firma admin kullanıcısı
        $companyAdminId = 'comp_admin_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO users (id, full_name, email, role, password, company_id, balance) 
                      VALUES (?, 'Metro Admin', 'metro@admin.com', 'company_admin', ?, ?, 5000)")
            ->execute([$companyAdminId, password_hash('metro123', PASSWORD_DEFAULT), $companyId]);

        // Örnek kullanıcı
        $userId = 'user_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO users (id, full_name, email, role, password, balance) 
                      VALUES (?, 'Ahmet Yılmaz', 'ahmet@email.com', 'user', ?, 1000)")
            ->execute([$userId, password_hash('123456', PASSWORD_DEFAULT)]);

        // Örnek sefer
        $tripId = 'trip_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO trips (id, company_id, departure_city, destination_city, 
                      departure_time, arrival_time, price, capacity) 
                      VALUES (?, ?, 'İstanbul', 'Ankara', ?, ?, 150.00, 40)")
            ->execute([
                $tripId, 
                $companyId, 
                date('Y-m-d H:i:s', strtotime('+2 days 09:00')),
                date('Y-m-d H:i:s', strtotime('+2 days 13:30'))
            ]);

        // Örnek kupon
        $couponId = 'coupon_' . uniqid();
        $pdo->prepare("INSERT OR IGNORE INTO coupons (id, code, discount, usage_limit, expire_date) 
                      VALUES (?, 'HOSGELDIN20', 0.20, 100, ?)")
            ->execute([$couponId, date('Y-m-d H:i:s', strtotime('+30 days'))]);

        return true;
    } catch (PDOException $e) {
        error_log("Sample data insertion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Veritabanını sıfırla (geliştirme aşamasında kullanım için)
 */
function resetDatabase() {
    try {
        $pdo = db();
        
        // Tabloları sil
        $tables = ['user_coupons', 'booked_seats', 'tickets', 'trips', 'coupons', 'users', 'bus_company'];
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