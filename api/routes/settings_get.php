<?php
// GET /api/settings
$userId = require_auth();
$pdo    = db();

$stmt = $pdo->prepare("
    SELECT id, full_name, email, plan,
           IFNULL(work_hour_start, 9)     AS work_hour_start,
           IFNULL(work_hour_end, 18)       AS work_hour_end,
           IFNULL(daily_invite_limit, 20)  AS daily_invite_limit,
           IFNULL(timezone, 'Europe/Lisbon') AS timezone,
           IFNULL(linkedin_url, '')        AS linkedin_url,
           IFNULL(notifications_email, 1)  AS notifications_email,
           IFNULL(notifications_summary, 1) AS notifications_summary,
           created_at
    FROM users WHERE id=?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) json_error(404, 'Utilizador não encontrado');

// Estatísticas do utilizador
$stats = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM leads WHERE user_id=?) AS total_leads,
        (SELECT COUNT(*) FROM activity_queue WHERE user_id=? AND status='done' AND DATE(executed_at)=CURDATE()) AS actions_today,
        (SELECT COUNT(*) FROM campaigns WHERE user_id=?) AS total_campaigns
");
$stats->execute([$userId, $userId, $userId]);
$user['stats'] = $stats->fetch();

json_ok($user);
