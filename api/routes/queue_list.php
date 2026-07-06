<?php
// GET /api/queue — lista a fila de ações do utilizador
$userId = require_auth();
$pdo    = db();

$status = $_GET['status'] ?? 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 30;
$offset = ($page - 1) * $limit;

$where  = 'aq.user_id = ?';
$params = [$userId];

if ($status !== 'all') {
    $where  .= ' AND aq.status = ?';
    $params[] = $status;
}

$stmt = $pdo->prepare("
    SELECT
        aq.id, aq.action_type, aq.status,
        aq.scheduled_at, aq.executed_at,
        aq.retry_count, aq.error_message, aq.notes,
        l.full_name     AS lead_name,
        l.linkedin_profile_url AS lead_url,
        l.company_name  AS company,
        l.job_title
    FROM activity_queue aq
    JOIN leads l ON l.id = aq.lead_id
    WHERE $where
    ORDER BY
        FIELD(aq.status,'running','pending','error','done','skipped'),
        aq.scheduled_at ASC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$items = $stmt->fetchAll();

// Totais por status
$counts = $pdo->prepare("
    SELECT status, COUNT(*) AS n
    FROM activity_queue
    WHERE user_id = ?
    GROUP BY status
");
$counts->execute([$userId]);
$totals = [];
foreach ($counts->fetchAll() as $r) $totals[$r['status']] = (int)$r['n'];

json_ok(['items' => $items, 'totals' => $totals, 'page' => $page]);
