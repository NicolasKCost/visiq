<?php
// DELETE /api/queue — cancela TODAS as acções pendentes do utilizador
$userId = require_auth();
$pdo    = db();

$stmt = $pdo->prepare("UPDATE activity_queue SET status='skipped' WHERE user_id=? AND status='pending'");
$stmt->execute([$userId]);
json_ok(['cancelled' => $stmt->rowCount()]);
