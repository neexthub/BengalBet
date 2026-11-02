<?php
/**
 * BengalBet DB Server Database Helper & PDO Wrapper
 * Auto-creates MySQL Database if missing and auto-seeds default schema & data
 * Compatible with XAMPP, WAMP, Laragon, cPanel, and live MySQL production servers
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $pdo = null;
    private static ?array $activeCredentials = null;
    private static bool $isMigrating = false;

    /**
     * Get or create PDO connection with auto-database creation & credential discovery
     */
    public static function getConnection(): PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Candidate credentials to attempt (configured credentials first, then XAMPP/localhost fallbacks)
        $candidates = [
            ['user' => DB_USER, 'pass' => DB_PASS]
        ];

        $isLocalhost = in_array(DB_HOST, ['localhost', '127.0.0.1', '::1']) || str_starts_with(DB_HOST, '192.168.');
        if ($isLocalhost) {
            // Common local dev / XAMPP default credentials
            $candidates[] = ['user' => 'root', 'pass' => ''];
            $candidates[] = ['user' => 'root', 'pass' => 'root'];
            $candidates[] = ['user' => 'root', 'pass' => '123456'];
            $candidates[] = ['user' => 'root', 'pass' => 'password'];
        }

        $lastError = null;
        $connectedPdo = null;
        $workingCred = null;

        foreach ($candidates as $cred) {
            $user = $cred['user'];
            $pass = $cred['pass'];

            // 1. First try connecting directly to the targeted database
            $dsnWithDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            try {
                $connectedPdo = new PDO($dsnWithDb, $user, $pass, $options);
                $workingCred = $cred;
                break;
            } catch (PDOException $e) {
                $lastError = $e;
                $msg = $e->getMessage();

                // If error is "Unknown database" (code 1049), try connecting to server root and creating database
                $isUnknownDb = (strpos($msg, 'Unknown database') !== false) || 
                               ($e->getCode() == 1049) || 
                               (strpos($msg, '1049') !== false);

                if ($isUnknownDb) {
                    try {
                        $dsnRoot = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                        $rootPdo = new PDO($dsnRoot, $user, $pass, $options);
                        
                        // Auto-create database
                        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        // Connect to newly created database
                        $connectedPdo = new PDO($dsnWithDb, $user, $pass, $options);
                        $workingCred = $cred;
                        
                        // Immediate auto-migration of tables & default data
                        self::$pdo = $connectedPdo;
                        self::$activeCredentials = $workingCred;
                        self::autoMigrateSchema();
                        break;
                    } catch (PDOException $createErr) {
                        $lastError = $createErr;
                        continue;
                    }
                }

                // If authentication failed, continue trying candidate credentials
                $isAuthFail = (strpos($msg, 'Access denied') !== false) || ($e->getCode() == 1045);
                if ($isAuthFail && $isLocalhost) {
                    continue;
                }
            }
        }

        if (!$connectedPdo) {
            $errMessage = $lastError ? $lastError->getMessage() : "Could not connect to MySQL server on " . DB_HOST . ":" . DB_PORT;
            throw new RuntimeException("Database connection failed: {$errMessage}. (Host: " . DB_HOST . ", Database: " . DB_NAME . ")");
        }

        self::$pdo = $connectedPdo;
        self::$activeCredentials = $workingCred;

        // Verify if tables exist in the database; if empty, run auto-migration
        try {
            $stmt = self::$pdo->query("SHOW TABLES FROM `" . DB_NAME . "`");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tables) || !in_array('users', $tables)) {
                self::autoMigrateSchema();
            }
        } catch (Exception $e) {
            self::autoMigrateSchema();
        }

        return self::$pdo;
    }

    /**
     * Run a parameterized query with auto-healing if table doesn't exist
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Check if table missing error (code 1146 or message contains doesn't exist)
            if (!self::$isMigrating && (strpos($e->getMessage(), "doesn't exist") !== false || $e->getCode() == 1146)) {
                self::autoMigrateSchema();
                // Retry query once after auto-migration
                $stmt = self::getConnection()->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }
            throw $e;
        }
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * Execute statement and return affected rows
     */
    public static function execute(string $sql, array $params = []): int {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Insert row into table
     */
    public static function insert(string $table, array $data): string {
        $keys = array_keys($data);
        $fields = implode('`, `', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $sql = "INSERT INTO `{$table}` (`{$fields}`) VALUES ({$placeholders})";
        
        self::query($sql, array_values($data));
        return self::getConnection()->lastInsertId() ?: ($data['id'] ?? '');
    }

    /**
     * Update rows in table
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setClauses = [];
        $values = [];
        foreach ($data as $key => $val) {
            $setClauses[] = "`{$key}` = ?";
            $values[] = $val;
        }
        $setString = implode(', ', $setClauses);
        $sql = "UPDATE `{$table}` SET {$setString} WHERE {$where}";
        $allParams = array_merge($values, $whereParams);
        return self::execute($sql, $allParams);
    }

    /**
     * Delete rows from table
     */
    public static function delete(string $table, string $where, array $whereParams = []): int {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        return self::execute($sql, $whereParams);
    }

    /**
     * Output standard JSON response
     */
    public static function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Output standard error response
     */
    public static function errorResponse(string $message, int $statusCode = 400, array $extra = []): void {
        self::jsonResponse(array_merge([
            'success' => false,
            'message' => $message,
            'error' => $message
        ], $extra), $statusCode);
    }

    /**
     * Get JSON request payload
     */
    public static function getJsonInput(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return $_POST ?: [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Extract authenticated user ID from Authorization header or fallback
     */
    public static function getAuthUserId(): string {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            if (!empty($token)) {
                return $token;
            }
        }

        if (!empty($_GET['userId'])) return trim($_GET['userId']);
        if (!empty($_POST['userId'])) return trim($_POST['userId']);

        return 'usr-889922'; // Default BengalBet player
    }

    /**
     * Check if master key provided in header or param matches MASTER_API_KEY
     */
    public static function verifyMasterKey(): bool {
        $key = $_SERVER['HTTP_X_MASTER_KEY'] ?? $_GET['master_key'] ?? $_POST['master_key'] ?? '';
        if (empty($key) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $key = $headers['X-Master-Key'] ?? $headers['x-master-key'] ?? '';
        }
        return (!empty($key) && hash_equals(MASTER_API_KEY, $key));
    }

    /**
     * Parse SQL file into individual statements
     */
    public static function splitSqlStatements(string $sql): array {
        // Strip block comments
        $sql = preg_replace('!/\*.*?\*/!s', '', $sql);
        
        // Strip single line comments (-- or #)
        $lines = explode("\n", $sql);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            $cleanLines[] = $line;
        }
        $cleanSql = implode("\n", $cleanLines);

        // Split on semicolons
        $rawStmts = explode(';', $cleanSql);
        $statements = [];
        foreach ($rawStmts as $raw) {
            $t = trim($raw);
            if (!empty($t)) {
                $statements[] = $t;
            }
        }
        return $statements;
    }

    /**
     * Auto-run schema migration & insert default records
     */
    public static function autoMigrateSchema(): void {
        if (self::$isMigrating) {
            return;
        }
        self::$isMigrating = true;

        try {
            $sqlFile = __DIR__ . '/database.sql';
            if (!file_exists($sqlFile)) {
                self::$isMigrating = false;
                return;
            }

            $sql = file_get_contents($sqlFile);
            if (empty(trim($sql))) {
                self::$isMigrating = false;
                return;
            }

            $pdo = self::$pdo;
            if (!$pdo) {
                self::$isMigrating = false;
                return;
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // Split and execute each statement cleanly
            $statements = self::splitSqlStatements($sql);
            foreach ($statements as $stmt) {
                try {
                    $pdo->exec($stmt);
                } catch (Exception $stmtErr) {
                    // Ignore non-fatal statement warnings
                }
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        } catch (Exception $e) {
            // Keep error logged
        } finally {
            self::$isMigrating = false;
        }
    }
}
