<?php
// GET /api/auth/verify?token=XXX
// Verifica o email e redireciona para o dashboard
$pdo = db();

$token = trim($_GET['token'] ?? '');
if (!$token) {
    header('Location: https://visiq.pro/dashboard/?error=token_invalido');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, full_name, email, plan, verify_token_exp
    FROM users
    WHERE verify_token=? AND email_verified=0
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: https://visiq.pro/dashboard/?error=token_invalido_ou_expirado');
    exit;
}

// Verifica expiração
if (strtotime($user['verify_token_exp']) < time()) {
    header('Location: https://visiq.pro/dashboard/?error=token_expirado');
    exit;
}

// Marca como verificado e activa trial
$pdo->prepare("
    UPDATE users
    SET email_verified=1, verify_token=NULL, verify_token_exp=NULL,
        trial_starts_at=NOW(),
        trial_ends_at=DATE_ADD(NOW(), INTERVAL 13 DAY)
    WHERE id=?
")->execute([$user['id']]);

// Gera token JWT para auto-login
$jwtToken = jwt_create((int)$user['id']);

// Envia email de boas-vindas
require_once __DIR__ . '/../mailer.php';
sendWelcomeEmail($user['email'], $user['full_name']);

// Redireciona para dashboard com token (auto-login)
header('Location: https://visiq.pro/dashboard/?verified=1&t=' . urlencode($jwtToken) . '&n=' . urlencode($user['full_name']) . '&p=' . urlencode($user['plan']));
exit;
