<?php
// DELETE /api/queue/{id} — cancela uma acção pendente
$userId = require_auth();
$pdo    = db();
$taskId = (int)($GLOBALS['task_id'] ?? 0);

if (!$taskId) json_error(400, 'ID inválido');

// Verifica que pertence ao utilizador e está pendente
$stmt = $pdo->prepare("SELECT id FROM activity_queue WHERE id=? AND user_id=? AND status='pending'");
$stmt->execute([$taskId, $userId]);
if (!$stmt->fetch()) json_error(404, 'Acção não encontrada ou não pode ser cancelada');

$pdo->prepare("UPDATE activity_queue SET status='skipped' WHERE id=?")->execute([$taskId]);
json_ok(['cancelled' => true]);
