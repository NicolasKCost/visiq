<?php
// PUT /api/leads/{id}/status
$userId = require_auth();
$leadId = $GLOBALS['lead_id'];
$pdo    = db();
$body   = json_decode(file_get_contents('php://input'), true);

$validStatuses = ['new','visited','connected','replied','converted','ignored'];
$status = $body['status'] ?? '';
if (!in_array($status, $validStatuses)) json_error(400, 'Estado inválido');

$stmt = $pdo->prepare('SELECT id FROM leads WHERE id=? AND user_id=?');
$stmt->execute([$leadId, $userId]);
if (!$stmt->fetch()) json_error(404, 'Lead não encontrado');

$pdo->prepare('UPDATE leads SET status=? WHERE id=? AND user_id=?')
    ->execute([$status, $leadId, $userId]);

json_ok(['updated' => true]);
