<?php
// api/routes/queue_next.php
$userId = require_auth();

$currentHour = (int) date('G');
if ($currentHour < WORK_HOUR_START || $currentHour >= WORK_HOUR_END) {
    json_ok(null);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT
        aq.id, aq.lead_id, aq.action_type, aq.scheduled_at,
        -- Lead
        l.linkedin_profile_url,
        l.full_name        AS lead_name,
        l.company_name,
        l.company_logo_url,
        l.vp_image_id      AS lead_vp_image_id,
        l.message_template_id AS lead_msg_template_id,
        -- Template de mensagem: usa o associado ao lead se existir, senao o generico da etapa
        COALESCE(
            (SELECT mt2.body FROM message_templates mt2
             WHERE mt2.id = l.message_template_id AND aq.action_type='send_invite' LIMIT 1),
            mt.body
        ) AS msg_body,
        COALESCE(l.message_template_id, mt.id) AS msg_template_id,
        -- Imagem Visual Pitch: opcional de verdade. So retorna quando o lead tem vp_image_id valido.
        it.id              AS img_template_id,
        it.base_image_path AS img_base_path,
        it.text_field_label,
        it.text_pos_x,
        it.text_pos_y,
        it.text_max_width,
        it.font_size,
        it.font_color
    FROM activity_queue aq
    JOIN leads l ON l.id = aq.lead_id
    LEFT JOIN message_templates mt ON
        mt.user_id = aq.user_id
        AND mt.warmup_step = CASE aq.action_type
            WHEN 'send_invite'     THEN 1
            WHEN 'send_followup_1' THEN 2
            WHEN 'send_followup_2' THEN 3
            ELSE 0 END
        AND mt.is_global = 0
    LEFT JOIN image_templates it ON
        it.id = l.vp_image_id
        AND (it.user_id = aq.user_id OR it.is_global = 1)
        AND it.base_image_path IS NOT NULL
    WHERE
        aq.user_id    = :uid
        AND aq.status = 'pending'
        AND aq.scheduled_at <= NOW()
        AND aq.retry_count   < 3
    ORDER BY aq.scheduled_at ASC
    LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$row = $stmt->fetch();

if (!$row) { json_ok(null); }

$baseUrl = 'https://visiq.pro/uploads/';

$task = [
    'id'          => (int)$row['id'],
    'action_type' => $row['action_type'],
    'lead' => [
        'id'                   => (int)$row['lead_id'],
        'linkedin_profile_url' => $row['linkedin_profile_url'],
        'full_name'            => $row['lead_name'],
        'company_name'         => $row['company_name'],
        'company_logo_url'     => $row['company_logo_url'],
    ],
    'template' => $row['msg_template_id'] ? [
        'id'   => (int)$row['msg_template_id'],
        'body' => $row['msg_body'],
    ] : null,
    'image_template' => $row['img_base_path'] ? [
        'id'               => (int)$row['img_template_id'],
        'base_image_url'   => $baseUrl . $row['img_base_path'],
        'text_field_label' => $row['text_field_label'],
        'text_pos_x'       => (int)$row['text_pos_x'],
        'text_pos_y'       => (int)$row['text_pos_y'],
        'text_max_width'   => (int)$row['text_max_width'],
        'font_size'        => (int)$row['font_size'],
        'font_color'       => $row['font_color'],
        'direct_url'       => $baseUrl . $row['img_base_path'],
    ] : null,
];

$pdo->prepare("UPDATE activity_queue SET status='running' WHERE id=?")
    ->execute([$row['id']]);

json_ok($task);
