<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../config/db.php';
$pdo = db();

echo "<h1>🚍 Bilet Satın Alma Platformu</h1>";
echo "<p>PHP Sürümü: " . PHP_VERSION . "</p>";
echo "<p>SQLite PDO: " . (extension_loaded('pdo_sqlite') ? 'Yüklü ✅' : 'Eksik ❌') . "</p>";
echo "<hr><p>Proje başarıyla çalışıyor 🎉</p>";