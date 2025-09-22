<?php
// 同步 /api 路由到 pay_right（使用 PDO，避免 CLI ORM 配置问题）

error_reporting(E_ALL & ~E_NOTICE);

$configFile = __DIR__ . '/../config/database.php';
$dbCfg = require $configFile;
$mysql = $dbCfg['connections']['mysql'] ?? null;
if (!$mysql) {
    fwrite(STDERR, "Missing mysql config in config/database.php\n");
    exit(1);
}

$host = $mysql['host'] ?? '127.0.0.1';
$port = $mysql['port'] ?? 3306;
$dbname = $mysql['database'] ?? '';
$user = $mysql['username'] ?? '';
$pass = $mysql['password'] ?? '';
$charset = $mysql['charset'] ?? 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function columnExists(PDO $pdo, string $db, string $table, string $column): bool {
    $sql = "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$db, $table, $column]);
    return $stmt->fetch()['c'] > 0;
}

function indexExists(PDO $pdo, string $db, string $table, string $indexName): bool {
    $sql = "SELECT COUNT(*) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$db, $table, $indexName]);
    return $stmt->fetch()['c'] > 0;
}

// 1) 添加字段
if (!columnExists($pdo, $dbname, 'pay_right', 'path')) {
    $pdo->exec("ALTER TABLE `pay_right` ADD COLUMN `path` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '接口路径' AFTER `icon`");
}
if (!columnExists($pdo, $dbname, 'pay_right', 'method')) {
    $pdo->exec("ALTER TABLE `pay_right` ADD COLUMN `method` VARCHAR(10) NOT NULL DEFAULT 'GET' COMMENT 'HTTP方法' AFTER `path`");
}
if (!indexExists($pdo, $dbname, 'pay_right', 'uniq_path_method')) {
    try {
        $pdo->exec("CREATE UNIQUE INDEX `uniq_path_method` ON `pay_right` (`path`,`method`)");
    } catch (Throwable $e) {
        // 忽略（某些版本行为差异）
    }
}

// 2) 解析路由文件
$routeFile = __DIR__ . '/../config/route.php';
$content = file_get_contents($routeFile) ?: '';

$pattern = "#Route::(get|post|put|delete)\\(\\s*'([^']*)'#i";
preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

// 补充对 group 内方法链式写法的支持，如 ->middleware([...]) 之后的逗号
// 上面的正则已足够覆盖 Route::method('/path', ...) 的大多数写法

$inserted = 0; $skipped = 0; $updated = 0;

function normalize_path(string $p): string {
    // 去掉 {id} 等参数段
    $p = preg_replace('#/\{[^/]+\}#', '', $p);
    // 结尾斜杠
    if ($p !== '/' && substr($p, -1) === '/') $p = substr($p, 0, -1);
    return $p;
}

$selStmt = $pdo->prepare("SELECT id, description FROM pay_right WHERE path=? AND method=? LIMIT 1");
$insStmt = $pdo->prepare("INSERT INTO pay_right (`pid`,`right_name`,`description`,`menu`,`sort`,`icon`,`path`,`method`,`is_del`) VALUES (NULL,?,?,?,?,?,?,?,1)");
$updStmt = $pdo->prepare("UPDATE pay_right SET description=? WHERE id=?");

function guess_desc(string $method, string $path): string {
    // 基于模块猜测中文描述
    $method = strtoupper($method);
    $m = [
        'GET' => '查看',
        'POST' => '创建',
        'PUT' => '更新',
        'DELETE' => '删除'
    ][$method] ?? $method;
    if (strpos($path, '/api/permissions') === 0) {
        return $m . '权限';
    }
    if (strpos($path, '/api/roles/all-rights-tree') === 0) {
        return '查看权限树';
    }
    if (strpos($path, '/api/roles') === 0) {
        return $m . '角色';
    }
    if (strpos($path, '/api/operation-logs/stats') === 0) {
        return '查看操作统计';
    }
    if (strpos($path, '/api/operation-logs/clean') === 0) {
        return '清理旧日志';
    }
    if (strpos($path, '/api/operation-logs') === 0) {
        return $m . '操作日志';
    }
    if (strpos($path, '/api/login') === 0) {
        return '用户登录';
    }
    if (strpos($path, '/api/logout') === 0) {
        return '用户登出';
    }
    if (strpos($path, '/api/refresh-token') === 0) {
        return '刷新令牌';
    }
    if (strpos($path, '/api/me') === 0) {
        return '获取我的信息';
    }
    return $m . ' ' . $path; // 兜底
}

foreach ($matches as $m) {
    $method = strtoupper($m[1]);
    $path = $m[2];
    if (strpos($path, '/api') !== 0) continue; // 只处理 API
    $norm = normalize_path($path);
    $selStmt->execute([$norm, $method]);
    $row = $selStmt->fetch();
    if ($row) {
        // 若描述仍为默认（与 right_name 相同），尝试更新为更友好的中文
        $id = $row['id'];
        $desc = $row['description'];
        $default = $method . ' ' . $norm;
        if ($desc === $default || $desc === '' || $desc === null) {
            $newDesc = guess_desc($method, $norm);
            $updStmt->execute([$newDesc, $id]);
            $updated++;
        } else {
            $skipped++;
        }
        continue;
    }
    $rn = $method . ' ' . $norm;
    $insStmt->execute([$rn, guess_desc($method, $norm), 0, 0, null, $norm, $method]);
    $inserted++;
}

echo json_encode(['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped], JSON_UNESCAPED_UNICODE) . PHP_EOL;


