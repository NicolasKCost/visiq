<?php
// POST /api/auth/reset-password
$body  = json_decode(file_get_contents('php://input'), true);
$pdo   = db();
$token = trim($body['token']    ?? '');
$pass  = trim($body['password'] ?? '');

if (!$token) json_error(400, 'Token inválido');
if (strlen($pass) < 8) json_error(400, 'Senha deve ter pelo menos 8 caracteres');

// Verifica token — tenta reset_token primeiro, depois verify_token
$stmt = null;
try {
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE reset_token=? AND reset_token_exp > NOW()");
    $stmt->execute([$token]);
} catch(PDOException $e) {
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE verify_token=? AND verify_token_exp > NOW()");
    $stmt->execute([$token]);
}
$user = $stmt->fetch();
if (!$user) json_error(400, 'Link inválido ou expirado. Pede uma nova recuperação de senha.');

// Actualiza senha e limpa tokens
$hash = password_hash($pass, PASSWORD_BCRYPT);
try {
    $pdo->prepare("UPDATE users SET password_hash=?, reset_token=NULL, reset_token_exp=NULL WHERE id=?")
        ->execute([$hash, $user['id']]);
} catch(PDOException $e) {
    $pdo->prepare("UPDATE users SET password_hash=?, verify_token=NULL, verify_token_exp=NULL WHERE id=?")
        ->execute([$hash, $user['id']]);
}

json_ok(['reset' => true, 'email' => $user['email']]);
