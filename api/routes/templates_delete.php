<?php
// api/routes/templates_delete.php — DELETE /api/templates/{id}
$userId = require_auth();
$tmplId = $GLOBALS['template_id'];
$pdo    = db();

$stmt = $pdo->prepare('SELECT id FROM message_templates WHERE id = ? AND user_id = ? AND is_global = 0');
$stmt->execute([$tmplId, $userId]);
if (!$stmt->fetch()) json_error(404, 'Template não encontrado ou não pode ser eliminado');

$pdo->prepare('DELETE FROM message_templates WHERE id = ?')->execute([$tmplId]);

json_ok(['deleted' => true]);
