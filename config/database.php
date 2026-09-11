<?php
// Config/database.php - PDO Database Connection Configuration

if (session_status() === PHP_SESSION_NONE) {
    ob_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bakery_db');

define('BASE_URL', '/bakery%20manegement/');

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Check if DB does not exist, redirect to installer if accessing web pages
            if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                if (file_exists(__DIR__ . '/../install.php') && basename($_SERVER['PHP_SELF']) !== 'install.php') {
                    header('Location: ' . BASE_URL . 'install.php');
                    exit;
                }
            }
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}

function getDB() {
    return Database::getInstance();
}
