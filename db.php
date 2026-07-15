<?php

class Database
{
    private static ?Database $instance = null;
    private mysqli $connection;

    private function __construct()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dbName = getenv('DB_NAME') ?: 'iglesiae_ApIApp2024';
        $charset = getenv('DB_CHARSET') ?: 'utf8';

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->connection = new mysqli($host, $user, $pass, $dbName);
            $this->connection->set_charset($charset);
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            error_log('DB Connection error: ' . $e->getMessage());
            die(json_encode(["success" => 0, "error" => "Error interno del servidor"]));
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }

    public function query(string $sql): mysqli_result
    {
        try {
            $result = $this->connection->query($sql);
            if ($result === false) {
                throw new mysqli_sql_exception($this->connection->error);
            }
            return $result;
        } catch (mysqli_sql_exception $e) {
            http_response_code(400);
            error_log('DB Query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            die(json_encode(["success" => 0, "error" => "Error interno del servidor"]));
        }
    }

    public function fetchAll(string $table, ?int $id = null): array
    {
        if ($id !== null) {
            $sql = "SELECT * FROM `$table` WHERE id = " . intval($id);
        } else {
            $sql = "SELECT * FROM `$table`";
        }

        $result = $this->query($sql);

        if (mysqli_num_rows($result) > 0) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        return [];
    }
}

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

loadEnv(__DIR__ . '/.env');

function setHeaders(): void
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Content-Type: application/json; charset=UTF-8");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    setHeaders();
    http_response_code(204);
    exit;
}
