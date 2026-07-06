<?php
// GET /api/admin/stats — estatísticas globais
$userId = require_auth();
$pdo    = db();

// Verifica se é admin
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user || !$user['is_admin']) json_error(403, 'Acesso negado');

$stats = [];

// Utilizadores
$pdo->query("SELECT
    COUNT(*) AS total,
    SUM(plan='trial') AS trial,
    SUM(plan='starter') AS starter,
    SUM(plan='growth') AS growth,
    SUM(email_verified=1) AS verified,
    SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_7d,
    SUM(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS new_30d
FROM users")->fetchAll()[0];

$stats['users'] = $pdo->query("SELECT
    COUNT(*) AS total,
    SUM(IFNULL(plan,'free')='trial') AS trial,
    SUM(IFNULL(plan,'free')='starter') AS starter,
    SUM(IFNULL(plan,'free')='growth') AS growth,
    SUM(IFNULL(email_verified,1)=1) AS verified,
    SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_7d,
    SUM(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS new_30d
FROM users")->fetch();

// Leads e actividade
$stats['leads']   = $pdo->query("SELECT COUNT(*) AS total FROM leads")->fetch()['total'];
$stats['queue']   = $pdo->query("SELECT COUNT(*) AS total, SUM(status='done') AS done, SUM(status='pending') AS pending FROM activity_queue")->fetch();
$stats['campaigns'] = $pdo->query("SELECT COUNT(*) AS total FROM campaigns")->fetch()['total'];

// Utilizadores recentes
$stats['recent_users'] = $pdo->query("
    SELECT id, full_name, email, plan, email_verified, created_at, trial_ends_at
    FROM users ORDER BY created_at DESC LIMIT 20
")->fetchAll();

// MRR estimado
$prices = ['starter'=>19, 'growth'=>34];
$mrr = 0;
foreach ($pdo->query("SELECT plan, COUNT(*) AS n FROM users WHERE plan IN ('starter','growth') GROUP BY plan")->fetchAll() as $row) {
    $mrr += ($prices[$row['plan']] ?? 0) * $row['n'];
}
$stats['mrr'] = $mrr;

json_ok($stats);
