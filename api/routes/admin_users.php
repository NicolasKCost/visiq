<?php
// GET/PUT/DELETE /api/admin/users
$userId = require_auth();
$pdo    = db();

$stmt = $pdo->prepare("SELECT IFNULL(is_admin,0) AS is_admin FROM users WHERE id=?");
$stmt->execute([$userId]);
$me = $stmt->fetch();
if (!$me || !$me['is_admin']) json_error(403, 'Acesso negado');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['search'] ?? '';
    $plan   = $_GET['plan']   ?? '';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 25;
    $offset = ($page - 1) * $limit;

    $where  = ['1=1'];
    $params = [];
    if ($search) {
        $where[]  = '(full_name LIKE ? OR email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($plan) {
        $where[]  = 'plan = ?';
        $params[] = $plan;
    }
    $whereStr = implode(' AND ', $where);

    try {
        $q = $pdo->prepare("
            SELECT
                id,
                full_name,
                email,
                IFNULL(plan,'free')            AS plan,
                IFNULL(email_verified,0)        AS email_verified,
                IFNULL(is_admin,0)              AS is_admin,
                created_at,
                trial_ends_at,
                (SELECT COUNT(*) FROM leads WHERE user_id = users.id) AS leads_count
            FROM users
            WHERE $whereStr
            ORDER BY created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $q->execute($params);
        $users = $q->fetchAll();

        $qTotal = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $whereStr");
        $qTotal->execute($params);
        $total = (int)$qTotal->fetchColumn();

        json_ok(['users' => $users, 'total' => $total, 'page' => $page]);

    } catch (PDOException $e) {
        json_error(500, 'Erro SQL: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $body     = json_decode(file_get_contents('php://input'), true) ?: [];
    $targetId = (int)($body['user_id'] ?? $_GET['user_id'] ?? 0);
    if (!$targetId) json_error(400, 'user_id obrigatório');
    if ($targetId === (int)$userId) json_error(400, 'Não podes eliminar a tua própria conta');

    try {
        foreach (['activity_queue','leads','campaigns','message_templates','image_templates'] as $tbl) {
            $pdo->prepare("DELETE FROM $tbl WHERE user_id=?")->execute([$targetId]);
        }
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$targetId]);
        json_ok(['deleted' => true]);
    } catch (PDOException $e) {
        json_error(500, 'Erro ao eliminar: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $body   = json_decode(file_get_contents('php://input'), true) ?: [];
    $target = (int)($body['user_id'] ?? 0);
    if (!$target) json_error(400, 'user_id obrigatório');

    $allowed = ['plan','email_verified','is_admin','trial_ends_at'];
    $sets    = [];
    $params  = [];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $body)) {
            $sets[]   = "$f = ?";
            $params[] = $body[$f];
        }
    }
    if (!$sets) json_error(400, 'Nada para actualizar');
    $params[] = $target;

    try {
        $pdo->prepare("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?")
            ->execute($params);
        json_ok(['updated' => true]);
    } catch (PDOException $e) {
        json_error(500, 'Erro ao actualizar: ' . $e->getMessage());
    }
}

// Se chegou aqui sem responder, método não suportado
json_error(405, 'Método não suportado');
