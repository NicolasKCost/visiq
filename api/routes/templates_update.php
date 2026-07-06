<?php
// api/routes/templates_update.php — PUT /api/templates/{id}
$userId = require_auth();
$tmplId = $GLOBALS['template_id'];
$body   = json_decode(file_get_contents('php://input'), true);
$pdo    = db();

// Garante que o template pertence ao utilizador (não pode editar globais)
$stmt = $pdo->prepare('SELECT id FROM message_templates WHERE id = ? AND user_id = ? AND is_global = 0');
$stmt->execute([$tmplId, $userId]);
if (!$stmt->fetch()) json_error(404, 'Template não encontrado');

$fields = [];
$params = [];

if (isset($body['name']))           { $fields[] = 'name = ?';            $params[] = trim($body['name']); }
if (isset($body['body']))           { $fields[] = 'body = ?';            $params[] = trim($body['body']); }
if (isset($body['niche_category'])) { $fields[] = 'niche_category = ?';  $params[] = $body['niche_category']; }
if (isset($body['warmup_step']))    { $fields[] = 'warmup_step = ?';     $params[] = (int)$body['warmup_step']; }

if (!$fields) json_error(400, 'Nada para atualizar');

$params[] = $tmplId;
$pdo->prepare("UPDATE message_templates SET " . implode(', ', $fields) . " WHERE id = ?")
    ->execute($params);

json_ok(['updated' => true]);
