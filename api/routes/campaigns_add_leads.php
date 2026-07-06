<?php
// POST /api/campaigns/{id}/leads — adiciona leads a uma campanha
$userId     = require_auth();
$campaignId = $GLOBALS['campaign_id'];
$body       = json_decode(file_get_contents('php://input'), true);
$pdo        = db();

$stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id=? AND user_id=?');
$stmt->execute([$campaignId, $userId]);
$campaign = $stmt->fetch();
if (!$campaign) json_error(404, 'Campanha não encontrada');

$leadIds = $body['lead_ids'] ?? [];
if (!$leadIds) json_error(400, 'Sem leads para adicionar');

$updated = 0;
foreach ($leadIds as $lid) {
    $r = $pdo->prepare('UPDATE leads SET campaign_id=? WHERE id=? AND user_id=?');
    $r->execute([$campaignId, (int)$lid, $userId]);
    $updated += $r->rowCount();
}

// Se campanha tem VP image ou template, aplica aos leads
if ($campaign['vp_image_id'] || $campaign['msg_template_id']) {
    $sets = [];
    $vals = [];
    if ($campaign['vp_image_id'])     { $sets[] = 'vp_image_id=?';        $vals[] = $campaign['vp_image_id']; }
    if ($campaign['msg_template_id']) { $sets[] = 'message_template_id=?'; $vals[] = $campaign['msg_template_id']; }
    $placeholders = implode(',', array_fill(0, count($leadIds), '?'));
    $vals = array_merge($vals, $leadIds);
    $pdo->prepare("UPDATE leads SET ".implode(',',$sets)." WHERE id IN ($placeholders) AND user_id=?")
        ->execute(array_merge($vals, [$userId]));
}

json_ok(['added' => $updated]);
