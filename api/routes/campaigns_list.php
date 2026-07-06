<?php
$userId = require_auth();
$pdo = db();

$stmt = $pdo->prepare("
    SELECT
        c.*,
        COUNT(l.id)                                    AS leads_total,
        SUM(l.status IN ('connected','replied','converted')) AS leads_converted,
        SUM(l.status = 'replied')                      AS leads_replied,
        it.base_image_path                             AS vp_image_path,
        mt.name                                        AS msg_template_name
    FROM campaigns c
    LEFT JOIN leads l           ON l.campaign_id = c.id
    LEFT JOIN image_templates it ON it.id = c.vp_image_id
    LEFT JOIN message_templates mt ON mt.id = c.msg_template_id
    WHERE c.user_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute([$userId]);
$campaigns = $stmt->fetchAll();

foreach ($campaigns as &$c) {
    $c['vp_image_url'] = $c['vp_image_path']
        ? 'https://visiq.pro/uploads/' . $c['vp_image_path']
        : null;
    $c['conversion_rate'] = $c['leads_total'] > 0
        ? round($c['leads_converted'] / $c['leads_total'] * 100, 1)
        : 0;
}

json_ok($campaigns);
