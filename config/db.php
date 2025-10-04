<?php
function db(): PDO {
    $dbPath = __DIR__ . '/../storage/database.sqlite';

    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0777, true);
    }
    if (!file_exists($dbPath)) {
        touch($dbPath);
        chmod($dbPath, 0666);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}