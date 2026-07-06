<?php
// api/routes/leads_create.php
$userId = require_auth();
$body   = json_decode(file_get_contents('php://input'), true);
$pdo    = db();

$url = trim($body['linkedin_profile_url'] ?? '');
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    json_error(400, 'URL do perfil LinkedIn inválida');
}

// Evita duplicata
$exists = $pdo->prepare('SELECT id FROM leads WHERE user_id=? AND linkedin_profile_url=?');
$exists->execute([$userId, $url]);
if ($exists->fetch()) json_error(409, 'Este lead já existe');

$vpImageId   = isset($body['vp_image_id'])          ? (int)$body['vp_image_id']          : null;
$msgTplId    = isset($body['message_template_id'])   ? (int)$body['message_template_id']  : null;
$campaignId  = isset($body['campaign_id'])           ? (int)$body['campaign_id']          : null;

// Insere o lead — com fallback se as colunas não existirem ainda
try {
    $pdo->prepare("
        INSERT INTO leads (user_id, linkedin_profile_url, full_name, company_name, status, vp_image_id, message_template_id, campaign_id)
        VALUES (?, ?, ?, ?, 'new', ?, ?, ?)
    ")->execute([$userId, $url, $body['full_name']??null, $body['company_name']??null, $vpImageId, $msgTplId, $campaignId]);
} catch (PDOException $e) {
    // Fallback sem as colunas novas
    $pdo->prepare("
        INSERT INTO leads (user_id, linkedin_profile_url, full_name, company_name, status)
        VALUES (?, ?, ?, ?, 'new')
    ")->execute([$userId, $url, $body['full_name']??null, $body['company_name']??null]);
}

$leadId = (int)$pdo->lastInsertId();

// Cria o primeiro passo da fila: visit_profile
$pdo->prepare("
    INSERT INTO activity_queue (user_id, lead_id, action_type, status, scheduled_at)
    VALUES (?, ?, 'visit_profile', 'pending', NOW())
")->execute([$userId, $leadId]);

// Devolve também a imagem VP associada para a extensão poder usar
$vpImageUrl = null;
if ($vpImageId) {
    $imgStmt = $pdo->prepare('SELECT base_image_path FROM image_templates WHERE id=? AND (user_id=? OR is_global=1)');
    $imgStmt->execute([$vpImageId, $userId]);
    $img = $imgStmt->fetch();
    if ($img && $img['base_image_path']) {
        $vpImageUrl = 'https://visiq.pro/uploads/' . $img['base_image_path'];
    }
}

json_ok([
    'lead_id'        => $leadId,
    'vp_image_url'   => $vpImageUrl,
    'vp_image_id'    => $vpImageId,
    'msg_template_id'=> $msgTplId,
], 201);
