<?php
$userId = require_auth();
$body   = json_decode(file_get_contents('php://input'), true);
$pdo    = db();

$name = trim($body['name'] ?? '');
if (!$name) json_error(400, 'Nome é obrigatório');

$pdo->prepare("
    INSERT INTO campaigns (user_id, name, description, niche, status, vp_image_id, msg_template_id, target_count)
    VALUES (?, ?, ?, ?, 'draft', ?, ?, ?)
")->execute([
    $userId,
    $name,
    $body['description']     ?? null,
    $body['niche']           ?? null,
    $body['vp_image_id']     ?? null,
    $body['msg_template_id'] ?? null,
    (int)($body['target_count'] ?? 50),
]);

json_ok(['id' => (int)$pdo->lastInsertId()], 201);
