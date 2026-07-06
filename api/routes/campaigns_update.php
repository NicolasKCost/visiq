<?php
$userId     = require_auth();
$campaignId = $GLOBALS['campaign_id'];
$body       = json_decode(file_get_contents('php://input'), true);
$pdo        = db();

$stmt = $pdo->prepare('SELECT id FROM campaigns WHERE id=? AND user_id=?');
$stmt->execute([$campaignId, $userId]);
if (!$stmt->fetch()) json_error(404, 'Campanha não encontrada');

$fields = []; $params = [];
foreach (['name','description','niche','status','vp_image_id','msg_template_id','target_count'] as $f) {
    if (array_key_exists($f, $body)) {
        $fields[] = "$f=?";
        $params[] = in_array($f,['vp_image_id','msg_template_id','target_count'])
            ? ($body[$f] ? (int)$body[$f] : null)
            : $body[$f];
    }
}
if (!$fields) json_error(400, 'Nada para atualizar');
$params[] = $campaignId;
$pdo->prepare("UPDATE campaigns SET ".implode(',',$fields)." WHERE id=?")->execute($params);
json_ok(['updated' => true]);
