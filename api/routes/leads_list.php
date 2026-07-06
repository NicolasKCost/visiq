<?php
// api/routes/leads_list.php
$userId = require_auth();
$pdo    = db();

$stmt = $pdo->prepare("
    SELECT
        l.id, l.full_name, l.company_name, l.linkedin_profile_url,
        l.status, l.created_at,
        IFNULL(l.vp_image_id, NULL)         AS vp_image_id,
        IFNULL(l.message_template_id, NULL)  AS message_template_id,
        -- Imagem VP
        (SELECT CONCAT('https://visiq.pro/uploads/', it.base_image_path)
         FROM image_templates it
         WHERE it.id = l.vp_image_id AND it.base_image_path IS NOT NULL
         LIMIT 1) AS vp_image_url,
        -- Nome do template VP
        (SELECT it.name FROM image_templates it WHERE it.id = l.vp_image_id LIMIT 1) AS vp_image_name,
        -- Próxima ação
        (SELECT action_type FROM activity_queue
         WHERE lead_id=l.id AND status='pending'
         ORDER BY scheduled_at ASC LIMIT 1) AS next_action,
        (SELECT scheduled_at FROM activity_queue
         WHERE lead_id=l.id AND status='pending'
         ORDER BY scheduled_at ASC LIMIT 1) AS next_action_at
    FROM leads l
    WHERE l.user_id=?
    ORDER BY l.created_at DESC
    LIMIT 500
");
$stmt->execute([$userId]);
$leads = $stmt->fetchAll();

// Funil
$metrics = $pdo->prepare("SELECT status, COUNT(*) AS total FROM leads WHERE user_id=? GROUP BY status");
$metrics->execute([$userId]);
$funnel = [];
foreach ($metrics->fetchAll() as $row) $funnel[$row['status']] = (int)$row['total'];

json_ok(['leads'=>$leads, 'funnel'=>$funnel]);
