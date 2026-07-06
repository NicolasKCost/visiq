<?php
// DELETE /api/leads/{id}
$userId = require_auth();
$leadId = $GLOBALS['lead_id'];
$pdo    = db();

$stmt = $pdo->prepare('SELECT id FROM leads WHERE id=? AND user_id=?');
$stmt->execute([$leadId, $userId]);
if (!$stmt->fetch()) json_error(404, 'Lead não encontrado');

// Remove ações da fila
$pdo->prepare('DELETE FROM activity_queue WHERE lead_id=? AND user_id=?')->execute([$leadId, $userId]);
// Remove o lead
$pdo->prepare('DELETE FROM leads WHERE id=? AND user_id=?')->execute([$leadId, $userId]);

json_ok(['deleted' => true]);
