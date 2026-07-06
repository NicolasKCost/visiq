<?php
// POST /api/auth/register
$body  = json_decode(file_get_contents('php://input'), true);
$pdo   = db();

$name  = trim($body['full_name']  ?? '');
$email = strtolower(trim($body['email'] ?? ''));
$pass  = $body['password'] ?? '';

if (!$name)  json_error(400, 'Nome é obrigatório');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_error(400, 'Email inválido');
if (strlen($pass) < 8) json_error(400, 'Senha deve ter pelo menos 8 caracteres');

// Verifica duplicado
$stmt = $pdo->prepare('SELECT id FROM users WHERE email=?');
$stmt->execute([$email]);
if ($stmt->fetch()) json_error(409, 'Este email já está registado. Faz login ou recupera a senha.');

// Gera token de verificação
$verifyToken  = bin2hex(random_bytes(32));
$tokenExp     = date('Y-m-d H:i:s', time() + 86400); // 24h
$trialStart   = date('Y-m-d H:i:s');
$trialEnd     = date('Y-m-d H:i:s', time() + 13 * 86400); // 13 dias

// Cria utilizador (email_verified = 0, trial pendente)
$hash = password_hash($pass, PASSWORD_BCRYPT);
try {
    $pdo->prepare("
        INSERT INTO users (full_name, email, password_hash, plan,
                           email_verified, verify_token, verify_token_exp,
                           trial_starts_at, trial_ends_at, created_at)
        VALUES (?, ?, ?, 'trial', 0, ?, ?, ?, ?, NOW())
    ")->execute([$name, $email, $hash, $verifyToken, $tokenExp, $trialStart, $trialEnd]);
} catch (PDOException $e) {
    // Fallback se colunas novas não existirem ainda
    $pdo->prepare("
        INSERT INTO users (full_name, email, password_hash, plan, created_at)
        VALUES (?, ?, ?, 'trial', NOW())
    ")->execute([$name, $email, $hash]);
}

$userId = (int)$pdo->lastInsertId();

// Envia email de verificação
require_once __DIR__ . '/../mailer.php';
$sent = sendVerificationEmail($email, $name, $verifyToken);

// Responde com sucesso mas SEM token (utilizador precisa verificar email)
json_ok([
    'requires_verification' => true,
    'email'                 => $email,
    'message'               => 'Conta criada! Verifica o teu email para activar os 13 dias grátis.',
    'email_sent'            => $sent,
], 201);
