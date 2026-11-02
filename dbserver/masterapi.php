<?php
/**
 * BengalBet Master API Controller
 * Provides secure remote file management and MySQL database management
 * Target Server: https://deserver.neexthub.site/dbserver/
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Verify Master Key or Admin Authentication
$input = Database::getJsonInput();
$action = $_GET['action'] ?? $input['action'] ?? 'ping';

// Security check: master key must match
if (!Database::verifyMasterKey()) {
    // Also allow check if request has valid master_key in body or query
    $providedKey = $input['master_key'] ?? $_GET['master_key'] ?? $_POST['master_key'] ?? '';
    if (empty($providedKey) || !hash_equals(MASTER_API_KEY, $providedKey)) {
        Database::errorResponse('Unauthorized Master API request. Invalid or missing X-Master-Key.', 401);
    }
}

// Helper: resolve safe filesystem path
function resolveSafePath(string $subpath): string {
    $subpath = trim(str_replace(['..', '\\'], ['', '/'], $subpath), '/');
    // Default root is either dbserver directory or website root
    $basePath = SERVER_ROOT_PATH;
    if (empty($subpath)) {
        return $basePath;
    }
    return $basePath . '/' . $subpath;
}

try {
    switch ($action) {
        // --------------------------------------------------------------------
        // SYSTEM HEALTH & STATUS
        // --------------------------------------------------------------------
        case 'ping':
        case 'status':
            $dbConnected = false;
            $dbVersion = 'Unknown';
            $tableCount = 0;
            try {
                $pdo = Database::getConnection();
                $dbConnected = true;
                $dbVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                $tables = Database::fetchAll("SHOW TABLES");
                $tableCount = count($tables);
            } catch (Exception $e) {
                $dbVersion = $e->getMessage();
            }

            Database::jsonResponse([
                'success' => true,
                'status' => 'online',
                'service' => 'BengalBet DBServer Master API',
                'version' => '2.5.0',
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'mysql' => [
                    'connected' => $dbConnected,
                    'version' => $dbVersion,
                    'database' => DB_NAME,
                    'table_count' => $tableCount
                ],
                'filesystem' => [
                    'root_path' => SERVER_ROOT_PATH,
                    'dbserver_path' => DBSERVER_PATH,
                    'is_writable' => is_writable(DBSERVER_PATH),
                    'free_space_mb' => round(disk_free_space(__DIR__) / 1024 / 1024, 2)
                ]
            ]);
            break;

        // --------------------------------------------------------------------
        // FILE MANAGEMENT
        // --------------------------------------------------------------------
        case 'list_files':
            $relPath = $_GET['path'] ?? $input['path'] ?? '';
            $targetDir = resolveSafePath($relPath);

            if (!file_exists($targetDir) || !is_dir($targetDir)) {
                Database::errorResponse("Directory does not exist: {$relPath}", 404);
            }

            $files = [];
            $scanned = scandir($targetDir);
            foreach ($scanned as $item) {
                if ($item === '.' || $item === '..') continue;
                $fullItemPath = $targetDir . '/' . $item;
                $isDir = is_dir($fullItemPath);
                $relItemPath = trim($relPath . '/' . $item, '/');

                $files[] = [
                    'name' => $item,
                    'path' => $relItemPath,
                    'type' => $isDir ? 'dir' : 'file',
                    'size' => $isDir ? 0 : filesize($fullItemPath),
                    'size_formatted' => $isDir ? '-' : (round(filesize($fullItemPath) / 1024, 2) . ' KB'),
                    'extension' => $isDir ? '' : pathinfo($item, PATHINFO_EXTENSION),
                    'modified' => date('Y-m-d H:i:s', filemtime($fullItemPath)),
                    'is_writable' => is_writable($fullItemPath)
                ];
            }

            Database::jsonResponse([
                'success' => true,
                'current_path' => $relPath,
                'count' => count($files),
                'files' => $files
            ]);
            break;

        case 'get_file':
            $relPath = $_GET['path'] ?? $input['path'] ?? '';
            if (empty($relPath)) {
                Database::errorResponse('File path is required.', 400);
            }
            $targetFile = resolveSafePath($relPath);

            if (!file_exists($targetFile) || is_dir($targetFile)) {
                Database::errorResponse("File not found: {$relPath}", 404);
            }

            $content = file_get_contents($targetFile);
            Database::jsonResponse([
                'success' => true,
                'path' => $relPath,
                'size' => filesize($targetFile),
                'modified' => date('Y-m-d H:i:s', filemtime($targetFile)),
                'is_writable' => is_writable($targetFile),
                'content' => $content
            ]);
            break;

        case 'create_file':
        case 'update_file':
        case 'save_file':
            $relPath = $input['path'] ?? $_POST['path'] ?? '';
            $content = $input['content'] ?? $_POST['content'] ?? '';

            if (empty($relPath)) {
                Database::errorResponse('Target file path is required.', 400);
            }

            $targetFile = resolveSafePath($relPath);
            $dir = dirname($targetFile);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $written = file_put_contents($targetFile, $content);
            if ($written === false) {
                Database::errorResponse("Failed to write to file: {$relPath}. Permission denied.", 500);
            }

            Database::jsonResponse([
                'success' => true,
                'message' => "File successfully saved ({$written} bytes written)",
                'path' => $relPath,
                'bytes' => $written,
                'modified' => date('Y-m-d H:i:s')
            ]);
            break;

        case 'delete_file':
            $relPath = $input['path'] ?? $_GET['path'] ?? '';
            if (empty($relPath)) {
                Database::errorResponse('Path is required.', 400);
            }

            $target = resolveSafePath($relPath);
            if (!file_exists($target)) {
                Database::errorResponse("File or directory not found: {$relPath}", 404);
            }

            if (is_dir($target)) {
                // Recursive delete
                $it = new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $file) {
                    if ($file->isDir()) {
                        rmdir($file->getRealPath());
                    } else {
                        unlink($file->getRealPath());
                    }
                }
                rmdir($target);
            } else {
                unlink($target);
            }

            Database::jsonResponse([
                'success' => true,
                'message' => "Successfully deleted {$relPath}"
            ]);
            break;

        case 'create_dir':
            $relPath = $input['path'] ?? $_POST['path'] ?? '';
            if (empty($relPath)) {
                Database::errorResponse('Directory path is required.', 400);
            }
            $targetDir = resolveSafePath($relPath);
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            Database::jsonResponse([
                'success' => true,
                'message' => "Directory created: {$relPath}"
            ]);
            break;

        // Batch Sync / Push Live Code Update
        case 'sync_patch':
        case 'batch_update_files':
            $filesList = $input['files'] ?? [];
            if (!is_array($filesList) || empty($filesList)) {
                Database::errorResponse('No files provided in sync payload.', 400);
            }

            $updatedCount = 0;
            $errors = [];
            foreach ($filesList as $f) {
                $fPath = $f['path'] ?? '';
                $fContent = $f['content'] ?? '';
                if (empty($fPath)) continue;

                $target = resolveSafePath($fPath);
                $d = dirname($target);
                if (!file_exists($d)) {
                    mkdir($d, 0755, true);
                }
                if (file_put_contents($target, $fContent) !== false) {
                    $updatedCount++;
                } else {
                    $errors[] = "Could not write {$fPath}";
                }
            }

            Database::jsonResponse([
                'success' => count($errors) === 0,
                'message' => "Synced {$updatedCount} files to server.",
                'updated_count' => $updatedCount,
                'errors' => $errors
            ]);
            break;

        // --------------------------------------------------------------------
        // DATABASE MANAGEMENT
        // --------------------------------------------------------------------
        case 'db_tables':
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SHOW TABLE STATUS FROM `" . DB_NAME . "`");
            $tableStatus = $stmt->fetchAll();

            $tables = [];
            foreach ($tableStatus as $t) {
                $tables[] = [
                    'name' => $t['Name'],
                    'engine' => $t['Engine'],
                    'rows' => (int)$t['Rows'],
                    'data_length' => (int)$t['Data_length'],
                    'data_length_formatted' => round($t['Data_length'] / 1024, 2) . ' KB',
                    'collation' => $t['Collation'],
                    'created_at' => $t['Create_time']
                ];
            }

            Database::jsonResponse([
                'success' => true,
                'database' => DB_NAME,
                'count' => count($tables),
                'tables' => $tables
            ]);
            break;

        case 'db_schema':
            $table = $_GET['table'] ?? $input['table'] ?? '';
            if (empty($table)) {
                Database::errorResponse('Table name is required.', 400);
            }

            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DESCRIBE `{$table}`");
            $stmt->execute();
            $columns = $stmt->fetchAll();

            Database::jsonResponse([
                'success' => true,
                'table' => $table,
                'columns' => $columns
            ]);
            break;

        case 'db_table_data':
            $table = $_GET['table'] ?? $input['table'] ?? '';
            if (empty($table)) {
                Database::errorResponse('Table name is required.', 400);
            }

            $page = max(1, (int)($_GET['page'] ?? $input['page'] ?? 1));
            $limit = min(100, max(5, (int)($_GET['limit'] ?? $input['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            $search = trim($_GET['search'] ?? $input['search'] ?? '');
            $orderBy = trim($_GET['order_by'] ?? $input['order_by'] ?? '');
            $orderDir = strtoupper(trim($_GET['order_dir'] ?? $input['order_dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            $pdo = Database::getConnection();
            
            // Check columns
            $descStmt = $pdo->prepare("DESCRIBE `{$table}`");
            $descStmt->execute();
            $columns = $descStmt->fetchAll();
            $colNames = array_column($columns, 'Field');

            $whereSql = "1=1";
            $params = [];
            if (!empty($search)) {
                $searchClauses = [];
                foreach ($colNames as $col) {
                    $searchClauses[] = "`{$col}` LIKE ?";
                    $params[] = "%{$search}%";
                }
                $whereSql = '(' . implode(' OR ', $searchClauses) . ')';
            }

            // Total count
            $countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM `{$table}` WHERE {$whereSql}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetch()['cnt'];

            // Order clause
            $orderSql = "";
            if (!empty($orderBy) && in_array($orderBy, $colNames)) {
                $orderSql = "ORDER BY `{$orderBy}` {$orderDir}";
            } else if (in_array('created_at', $colNames)) {
                $orderSql = "ORDER BY `created_at` DESC";
            } else if (in_array('id', $colNames)) {
                $orderSql = "ORDER BY `id` DESC";
            }

            // Fetch rows
            $dataStmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE {$whereSql} {$orderSql} LIMIT {$limit} OFFSET {$offset}");
            $dataStmt->execute($params);
            $rows = $dataStmt->fetchAll();

            Database::jsonResponse([
                'success' => true,
                'table' => $table,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'columns' => $colNames,
                'rows' => $rows
            ]);
            break;

        case 'db_query':
            $sql = trim($input['sql'] ?? $_POST['sql'] ?? '');
            if (empty($sql)) {
                Database::errorResponse('SQL query string is required.', 400);
            }

            $pdo = Database::getConnection();
            $upperSql = strtoupper($sql);
            $isSelect = str_starts_with($upperSql, 'SELECT') || str_starts_with($upperSql, 'SHOW') || str_starts_with($upperSql, 'DESCRIBE') || str_starts_with($upperSql, 'EXPLAIN');

            if ($isSelect) {
                $stmt = $pdo->query($sql);
                $rows = $stmt->fetchAll();
                Database::jsonResponse([
                    'success' => true,
                    'query_type' => 'read',
                    'count' => count($rows),
                    'rows' => $rows
                ]);
            } else {
                $affected = $pdo->exec($sql);
                Database::jsonResponse([
                    'success' => true,
                    'query_type' => 'write',
                    'affected_rows' => $affected,
                    'last_insert_id' => $pdo->lastInsertId(),
                    'message' => "Query executed successfully. Affected rows: {$affected}"
                ]);
            }
            break;

        case 'db_insert_row':
            $table = $input['table'] ?? $_POST['table'] ?? '';
            $data = $input['data'] ?? [];
            if (empty($table) || empty($data) || !is_array($data)) {
                Database::errorResponse('Table name and data object are required.', 400);
            }

            $insertedId = Database::insert($table, $data);
            Database::jsonResponse([
                'success' => true,
                'message' => 'Row inserted successfully.',
                'id' => $insertedId
            ]);
            break;

        case 'db_update_row':
            $table = $input['table'] ?? $_POST['table'] ?? '';
            $primaryKey = $input['primary_key'] ?? 'id';
            $primaryVal = $input['primary_val'] ?? '';
            $data = $input['data'] ?? [];

            if (empty($table) || empty($primaryVal) || empty($data) || !is_array($data)) {
                Database::errorResponse('Table, primary value, and data are required.', 400);
            }

            unset($data[$primaryKey]); // Do not update primary key
            $affected = Database::update($table, $data, "`{$primaryKey}` = ?", [$primaryVal]);
            Database::jsonResponse([
                'success' => true,
                'message' => "Row updated successfully. Affected: {$affected}",
                'affected_rows' => $affected
            ]);
            break;

        case 'db_delete_row':
            $table = $input['table'] ?? $_POST['table'] ?? '';
            $primaryKey = $input['primary_key'] ?? 'id';
            $primaryVal = $input['primary_val'] ?? '';

            if (empty($table) || empty($primaryVal)) {
                Database::errorResponse('Table and primary value are required.', 400);
            }

            $affected = Database::delete($table, "`{$primaryKey}` = ?", [$primaryVal]);
            Database::jsonResponse([
                'success' => true,
                'message' => "Row deleted successfully.",
                'affected_rows' => $affected
            ]);
            break;

        case 'db_import_sql':
            $sql = $input['sql'] ?? $_POST['sql'] ?? '';
            if (empty($sql)) {
                $sqlFile = __DIR__ . '/database.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                }
            }

            if (empty($sql)) {
                Database::errorResponse('SQL content is empty or file not found.', 400);
            }

            $pdo = Database::getConnection();
            $pdo->exec($sql);
            Database::jsonResponse([
                'success' => true,
                'message' => 'SQL script successfully executed into database.'
            ]);
            break;

        case 'db_auto_create':
        case 'db_init':
            $pdo = Database::getConnection();
            Database::autoMigrateSchema();

            $tablesStmt = $pdo->query("SHOW TABLES FROM `" . DB_NAME . "`");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            $usersCount = 0;
            try {
                $u = Database::fetchOne("SELECT COUNT(*) as cnt FROM `users`");
                $usersCount = (int)($u['cnt'] ?? 0);
            } catch (Exception $e) {}

            Database::jsonResponse([
                'success' => true,
                'message' => 'ডাটাবেস তৈরি ও ডিফল্ট ডেটা সিডিং সম্পন্ন হয়েছে (Database auto-created and default data seeded)!',
                'database' => DB_NAME,
                'created_tables' => count($tables),
                'tables' => $tables,
                'users_count' => $usersCount,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        case 'db_reset':
            // Database Reset Process: Drops existing tables and re-initializes from database.sql
            $pdo = Database::getConnection();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // 1. Fetch current tables
            $tablesStmt = $pdo->query("SHOW TABLES FROM `" . DB_NAME . "`");
            $existingTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
            $droppedCount = 0;

            foreach ($existingTables as $tbl) {
                $pdo->exec("DROP TABLE IF EXISTS `{$tbl}`");
                $droppedCount++;
            }

            // 2. Re-migrate schema and default seeds
            Database::autoMigrateSchema();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            // 3. Retrieve fresh tables and counts
            $newTablesStmt = $pdo->query("SHOW TABLES FROM `" . DB_NAME . "`");
            $newTables = $newTablesStmt->fetchAll(PDO::FETCH_COLUMN);

            Database::jsonResponse([
                'success' => true,
                'message' => 'ডাটাবেস সম্পূর্ণভাবে রিসেট ও ফ্রেশ ইন্সটল সম্পন্ন হয়েছে (Database reset successfully completed)!',
                'dropped_tables' => $droppedCount,
                'created_tables' => count($newTables),
                'tables' => $newTables,
                'database' => DB_NAME,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        default:
            Database::errorResponse("Unknown Master API action: {$action}", 404);
            break;
    }
} catch (Exception $e) {
    Database::errorResponse($e->getMessage(), 500, ['trace' => $e->getTraceAsString()]);
}
