<?php
// api/routes/templates_create.php — POST /api/templates
$userId = require_auth();
$body   = json_decode(file_get_contents('php://input'), true);
$pdo    = db();

$name     = trim($body['name']     ?? '');
$text     = trim($body['body']     ?? '');
$niche    = $body['niche_category'] ?? 'generic';
$step     = (int)($body['warmup_step'] ?? 1);

if (!$name || !$text) {
    json_error(400, 'Nome e corpo da mensagem são obrigatórios');
}

$valid_niches = ['branding','web_design','social_media','motion_graphics','packaging','ux_ui','generic'];
if (!in_array($niche, $valid_niches)) $niche = 'generic';
if ($step < 1 || $step > 3) $step = 1;

$pdo->prepare("
    INSERT INTO message_templates (user_id, name, body, niche_category, warmup_step, is_global)
    VALUES (?, ?, ?, ?, ?, 0)
")->execute([$userId, $name, $text, $niche, $step]);

json_ok(['id' => (int)$pdo->lastInsertId()], 201);
