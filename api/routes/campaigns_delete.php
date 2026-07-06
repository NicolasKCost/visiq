<?php
$userId     = require_auth();
$campaignId = $GLOBALS['campaign_id'];
$pdo        = db();

$stmt = $pdo->prepare('SELECT id FROM campaigns WHERE id=? AND user_id=?');
$stmt->execute([$campaignId, $userId]);
if (!$stmt->fetch()) json_error(404, 'Campanha não encontrada');

// Remove associação dos leads (não apaga os leads)
$pdo->prepare('UPDATE leads SET campaign_id=NULL WHERE campaign_id=?')->execute([$campaignId]);
$pdo->prepare('DELETE FROM campaigns WHERE id=?')->execute([$campaignId]);
json_ok(['deleted' => true]);
