<?php
// api/routes/templates_list.php — GET /api/templates
$userId = require_auth();
$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, name, body, niche_category, warmup_step, is_global, created_at
    FROM message_templates
    WHERE user_id = ? OR is_global = 1
    ORDER BY is_global ASC, warmup_step ASC, created_at DESC
");
$stmt->execute([$userId]);
$templates = $stmt->fetchAll();

json_ok($templates);
