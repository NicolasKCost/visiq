<?php
// GET /api/platform/announcement — endpoint público (sem auth)
// Devolve apenas o anúncio global e estado de manutenção
$pdo = db();

try {
    $rows = $pdo->query("SELECT `key`, `value` FROM platform_settings WHERE `key` IN ('announcement','maintenance_mode','maintenance_message')")->fetchAll();
    $data = [];
    foreach ($rows as $r) $data[$r['key']] = $r['value'];
} catch(PDOException $e) {
    json_ok(['announcement' => '', 'maintenance_mode' => '0']);
}

json_ok([
    'announcement'        => $data['announcement']        ?? '',
    'maintenance_mode'    => $data['maintenance_mode']    ?? '0',
    'maintenance_message' => $data['maintenance_message'] ?? '',
]);
