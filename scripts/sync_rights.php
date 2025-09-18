<?php

use think\facade\Db;

require_once __DIR__ . '/../vendor/autoload.php';

// 简单的路由扫描：从 config/route.php 提取 '/api' 下常见写法
$routeFile = __DIR__ . '/../config/route.php';
$content = file_get_contents($routeFile);

preg_match_all('#Route::(get|post|put|delete)\(\s*\'([^\']+)\'#i', $content, $matches, PREG_SET_ORDER);

$inserted = 0; $updated = 0; $skipped = 0;

foreach ($matches as $m) {
    $method = strtoupper($m[1]);
    $path = $m[2];
    if (strpos($path, '/api') !== 0) {
        continue; // 只同步 /api 下的
    }
    // 规范化
    $normPath = preg_replace('#/\{[^/]+\}#', '', $path); // 去掉 {id}

    // 是否存在
    $exists = Db::table('pay_right')->where('path', $normPath)->where('method', $method)->find();
    if ($exists) {
        $skipped++;
        continue;
    }
    $rightName = $method . ' ' . $normPath;
    $desc = $rightName;
    Db::table('pay_right')->insert([
        'pid' => null,
        'right_name' => $rightName,
        'description' => $desc,
        'menu' => 0,
        'sort' => 0,
        'icon' => null,
        'path' => $normPath,
        'method' => $method,
        'is_del' => 1,
    ]);
    $inserted++;
}

echo json_encode(['inserted' => $inserted, 'skipped' => $skipped]);


