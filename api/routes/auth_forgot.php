<?php
// POST /api/auth/forgot-password
$body  = json_decode(file_get_contents('php://input'), true);
$pdo   = db();
$email = strtolower(trim($body['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_error(400, 'Email inválido');

$stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email=?');
$stmt->execute([$email]);
$user = $stmt->fetch();

// Responde sempre OK (não revela se email existe — segurança)
if (!$user) { json_ok(['sent' => true]); }

$token    = bin2hex(random_bytes(32));
$tokenExp = date('Y-m-d H:i:s', time() + 3600); // 1 hora

// Guarda na DB (reutiliza verify_token)
try {
    // Tenta com coluna reset_token dedicada
    $pdo->prepare("UPDATE users SET reset_token=?, reset_token_exp=? WHERE id=?")
        ->execute([$token, $tokenExp, $user['id']]);
} catch(PDOException $e) {
    // Fallback: usa verify_token
    $pdo->prepare("UPDATE users SET verify_token=?, verify_token_exp=? WHERE id=?")
        ->execute([$token, $tokenExp, $user['id']]);
}

$resetUrl = 'https://visiq.pro/dashboard/?action=reset&token=' . urlencode($token);

require_once __DIR__ . '/../mailer.php';

$subject = 'Recuperação de senha — VisIQ';
$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#08080f;font-family:Inter,system-ui,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#08080f;padding:40px 20px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0f0f1e;border:1px solid rgba(123,79,224,.2);border-radius:16px;overflow:hidden;max-width:560px">
  <tr><td style="background:linear-gradient(135deg,#7B4FE0,#2E9CF5);padding:28px 32px;text-align:center">
    <div style="font-size:28px;font-weight:900;color:#fff">VisIQ</div>
  </td></tr>
  <tr><td style="padding:36px 32px;color:#c4bfe8">
    <h2 style="color:#f0eeff;font-size:22px;font-weight:800;margin:0 0 12px">🔐 Recuperar senha</h2>
    <p style="font-size:15px;line-height:1.6;margin:0 0 24px">Olá <strong style="color:#f0eeff">' . htmlspecialchars($user['full_name']) . '</strong>,<br>
    Recebemos um pedido de recuperação de senha. Clica no botão abaixo para criar uma nova senha.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="' . $resetUrl . '" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#7B4FE0,#2E9CF5);color:#fff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700">Criar nova senha →</a>
    </div>
    <p style="font-size:12px;color:#7a76a0;line-height:1.6">⚠️ Este link expira em <strong>1 hora</strong>. Se não pediste esta recuperação, ignora este email — a tua senha não foi alterada.</p>
  </td></tr>
  <tr><td style="padding:16px 32px;border-top:1px solid rgba(255,255,255,.06);text-align:center">
    <p style="font-size:11px;color:#3d3960;margin:0">© 2025 VisIQ · suporte@visiq.pro</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

$headers  = "From: VisIQ <noreply@visiq.pro>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
mail($email, $subject, $html, $headers);

json_ok(['sent' => true]);
