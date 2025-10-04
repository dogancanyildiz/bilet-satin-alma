<?php
/**
 * Database initialization script
 * Bu dosya uygulama her başladığında çalışır ve gerekli tabloları oluşturur
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/database.php';

// Veritabanını başlat
if (initializeDatabase()) {
    error_log("Database initialized successfully");
    
    // Eğer hiç kullanıcı yoksa örnek verileri ekle
    $pdo = db();
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    if ($userCount == 0) {
        if (insertSampleData()) {
            error_log("Sample data inserted successfully");
        } else {
            error_log("Failed to insert sample data");
        }
    }
} else {
    error_log("Failed to initialize database");
}