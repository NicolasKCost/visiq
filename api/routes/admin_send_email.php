<?php
// POST /api/admin/send-email
$userId = require_auth();
$pdo    = db();

// Verifica admin
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->execute([$userId]);
$me = $stmt->fetch();
if (!$me || !$me['is_admin']) json_error(403, 'Acesso negado');

$body    = json_decode(file_get_contents('php://input'), true);
$target  = (int)($body['user_id'] ?? 0);
$subject = trim($body['subject'] ?? '');
$message = trim($body['message'] ?? '');

if (!$target)  json_error(400, 'Utilizador obrigatório');
if (!$subject) json_error(400, 'Assunto obrigatório');
if (!$message) json_error(400, 'Mensagem obrigatória');

// Busca dados do destinatário
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id=?");
$stmt->execute([$target]);
$user = $stmt->fetch();
if (!$user) json_error(404, 'Utilizador não encontrado');

require_once __DIR__ . '/../mailer.php';

// Envia email premium
$html_body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#08080f;font-family:Inter,system-ui,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#08080f;padding:40px 20px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0f0f1e;border:1px solid rgba(123,79,224,.2);border-radius:16px;overflow:hidden;max-width:560px">
  <tr><td style="background:linear-gradient(135deg,#7B4FE0,#2E9CF5);padding:28px 32px;text-align:center">
    <div style="font-size:28px;font-weight:900;color:#fff;letter-spacing:-1px">VisIQ</div>
    <div style="font-size:13px;color:rgba(255,255,255,.7);margin-top:4px">Mensagem da equipa VisIQ</div>
  </td></tr>
  <tr><td style="padding:36px 32px;color:#c4bfe8">
    <h2 style="color:#f0eeff;font-size:20px;font-weight:800;margin:0 0 16px">Olá ' . htmlspecialchars($user['full_name']) . '! 👋</h2>
    <div style="font-size:14px;line-height:1.8;white-space:pre-line">' . nl2br(htmlspecialchars($message)) . '</div>
  </td></tr>
  <tr><td style="padding:16px 32px 24px;border-top:1px solid rgba(255,255,255,.06);text-align:center">
    <a href="https://visiq.pro/dashboard/" style="display:inline-block;padding:12px 28px;background:linear-gradient(135deg,#7B4FE0,#2E9CF5);color:#fff;text-decoration:none;border-radius:10px;font-size:14px;font-weight:700">
      Abrir Dashboard →
    </a>
  </td></tr>
  <tr><td style="padding:14px 32px;border-top:1px solid rgba(255,255,255,.06);text-align:center">
    <p style="font-size:11px;color:#3d3960;margin:0">© 2025 VisIQ · <a href="mailto:suporte@visiq.pro" style="color:#3d3960">suporte@visiq.pro</a></p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

$headers  = "From: VisIQ <noreply@visiq.pro>\r\n";
$headers .= "Reply-To: suporte@visiq.pro\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$sent = mail($user['email'], $subject, $html_body, $headers);

if (!$sent) json_error(500, 'Erro ao enviar email. Verifica as configurações SMTP.');

json_ok(['sent' => true, 'to' => $user['email']]);
