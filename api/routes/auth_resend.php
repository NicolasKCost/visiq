<?php
// POST /api/auth/resend-verification
$body  = json_decode(file_get_contents('php://input'), true);
$pdo   = db();
$email = strtolower(trim($body['email'] ?? ''));

if (!$email) json_error(400, 'Email obrigatório');

$stmt = $pdo->prepare('SELECT id, full_name, email_verified FROM users WHERE email=?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user)              json_error(404, 'Email não encontrado');
if ($user['email_verified']) json_error(400, 'Este email já está verificado');

// Novo token
$token    = bin2hex(random_bytes(32));
$tokenExp = date('Y-m-d H:i:s', time() + 86400);
$pdo->prepare('UPDATE users SET verify_token=?, verify_token_exp=? WHERE id=?')
    ->execute([$token, $tokenExp, $user['id']]);

require_once __DIR__ . '/../mailer.php';
sendVerificationEmail($email, $user['full_name'], $token);

json_ok(['sent' => true]);
