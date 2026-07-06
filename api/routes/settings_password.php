<?php
// PUT /api/settings/password
$userId = require_auth();
$body   = json_decode(file_get_contents('php://input'), true);
$pdo    = db();

$current = trim($body['current_password'] ?? '');
$new     = trim($body['new_password']     ?? '');

if (!$current || !$new) json_error(400, 'Preenche a senha atual e a nova');
if (strlen($new) < 8)   json_error(400, 'A nova senha deve ter pelo menos 8 caracteres');

$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!password_verify($current, $user['password_hash'])) {
    json_error(400, 'Senha atual incorreta');
}

$pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
    ->execute([password_hash($new, PASSWORD_BCRYPT), $userId]);

json_ok(['updated' => true]);
