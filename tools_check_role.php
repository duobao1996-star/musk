<?php
$cfg = require __DIR__ . '/config/database.php';
$db = $cfg['connections']['mysql'];
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset']);
$pdo = new PDO($dsn, $db['username'], $db['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$roleId = isset($argv[1]) ? (string)$argv[1] : '1';
$stmt = $pdo->prepare('SELECT role_id,right_id FROM pay_role_right WHERE role_id = ? LIMIT 50');
$stmt->execute([$roleId]);
$rr = $stmt->fetchAll(PDO::FETCH_ASSOC);
$ids = array_values(array_unique(array_map(fn($r)=>$r['right_id'], $rr)));
print("role_right count=".count($rr)."\n");
print(json_encode($rr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
if ($ids) {
  $in = implode(',', array_map('intval', $ids));
  $rights = $pdo->query("SELECT id,right_name,menu,is_del FROM pay_right WHERE id IN ($in) ORDER BY id LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
  print(json_encode($rights, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
} else {
  print("no right ids\n");
}
