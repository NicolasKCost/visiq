<?php
// POST /api/auth/login
$body  = json_decode(file_get_contents('php://input'), true);
$pdo   = db();

$email = strtolower(trim($body['email']    ?? ''));
$pass  = $body['password'] ?? '';

if (!$email || !$pass) json_error(400, 'Email e senha são obrigatórios');

$stmt = $pdo->prepare("
    SELECT id, full_name, email, password_hash, plan,
           IFNULL(email_verified, 1) AS email_verified,
           trial_ends_at,
           IFNULL(is_admin, 0) AS is_admin
    FROM users WHERE email=?
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password_hash'])) {
    json_error(401, 'Credenciais incorrectas');
}

// Verifica se email está confirmado
if (!(int)$user['email_verified']) {
    json_error(403, 'Email não verificado. Verifica a tua caixa de email e clica no link de confirmação.');
}

// Verifica trial expirado
$plan = $user['plan'];
if ($plan === 'trial' && $user['trial_ends_at'] && strtotime($user['trial_ends_at']) < time()) {
    $plan = 'expired';
}

// Gera token JWT (usa jwt_create do auth.php)
$token = jwt_create((int)$user['id']);

json_ok([
    'token'         => $token,
    'full_name'     => $user['full_name'],
    'email'         => $user['email'],
    'plan'          => $plan,
    'trial_ends_at' => $user['trial_ends_at'],
    'is_admin'      => (bool)($user['is_admin'] ?? false),
]);
