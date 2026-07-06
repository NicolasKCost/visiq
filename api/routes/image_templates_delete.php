<?php
// api/routes/image_templates_delete.php
$userId = require_auth();
$tmplId = $GLOBALS['image_template_id'];
$pdo    = db();

$stmt = $pdo->prepare('SELECT * FROM image_templates WHERE id = ? AND user_id = ? AND is_global = 0');
$stmt->execute([$tmplId, $userId]);
$tmpl = $stmt->fetch();
if (!$tmpl) json_error(404, 'Template não encontrado');

// Remove ficheiro de imagem
if ($tmpl['base_image_path']) {
    $path = __DIR__ . '/../../uploads/' . $tmpl['base_image_path'];
    if (file_exists($path)) unlink($path);
}

$pdo->prepare('DELETE FROM image_templates WHERE id = ?')->execute([$tmplId]);
json_ok(['deleted' => true]);
