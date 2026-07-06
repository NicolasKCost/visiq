<?php
// api/mailer.php — Envio de email via SMTP (PHPMailer ou mail())
// Hostinger suporta mail() nativo ou SMTP

function sendVerificationEmail(string $toEmail, string $toName, string $token): bool {
    $verifyUrl = 'https://visiq.pro/api/auth/verify?token=' . urlencode($token);
    $subject   = 'Confirma o teu email — VisIQ';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#08080f;font-family:Inter,system-ui,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#08080f;padding:40px 20px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0f0f1e;border:1px solid rgba(123,79,224,.2);border-radius:16px;overflow:hidden;max-width:560px">
  <tr><td style="background:linear-gradient(135deg,#7B4FE0,#2E9CF5);padding:28px 32px;text-align:center">
    <div style="font-size:28px;font-weight:900;color:#fff;letter-spacing:-1px">VisIQ</div>
    <div style="font-size:13px;color:rgba(255,255,255,.7);margin-top:4px">Prospecção Inteligente no LinkedIn</div>
  </td></tr>
  <tr><td style="padding:36px 32px;color:#c4bfe8">
    <h2 style="color:#f0eeff;font-size:22px;font-weight:800;margin:0 0 12px">Confirma o teu email 👋</h2>
    <p style="font-size:15px;line-height:1.6;margin:0 0 24px">Olá <strong style="color:#f0eeff">' . htmlspecialchars($toName) . '</strong>,<br>
    Obrigado por criares a tua conta VisIQ! Clica no botão abaixo para confirmar o teu email e activar os teus <strong style="color:#2DD4A0">13 dias grátis</strong>.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="' . $verifyUrl . '" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#7B4FE0,#2E9CF5);color:#fff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700">Confirmar email →</a>
    </div>
    <p style="font-size:12px;color:#7a76a0;line-height:1.6">Se não criaste esta conta, ignora este email. O link expira em 24 horas.<br>
    Ou copia este link: <a href="' . $verifyUrl . '" style="color:#7B4FE0">' . $verifyUrl . '</a></p>
  </td></tr>
  <tr><td style="padding:16px 32px;border-top:1px solid rgba(255,255,255,.06);text-align:center">
    <p style="font-size:11px;color:#3d3960;margin:0">© 2025 VisIQ · <a href="mailto:suporte@visiq.pro" style="color:#3d3960">suporte@visiq.pro</a></p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

    $text = "Olá $toName,\n\nConfirma o teu email VisIQ:\n$verifyUrl\n\nO link expira em 24 horas.\n\n© 2025 VisIQ";

    $headers  = "From: VisIQ <noreply@visiq.pro>\r\n";
    $headers .= "Reply-To: suporte@visiq.pro\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    return mail($toEmail, $subject, $html, $headers);
}

function sendWelcomeEmail(string $toEmail, string $toName): bool {
    $dashUrl = 'https://visiq.pro/dashboard/';
    $subject = 'Bem-vindo ao VisIQ! Os teus 13 dias começaram 🚀';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#08080f;font-family:Inter,system-ui,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#08080f;padding:40px 20px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0f0f1e;border:1px solid rgba(123,79,224,.2);border-radius:16px;overflow:hidden;max-width:560px">
  <tr><td style="background:linear-gradient(135deg,#7B4FE0,#2E9CF5);padding:28px 32px;text-align:center">
    <div style="font-size:28px;font-weight:900;color:#fff">VisIQ</div>
  </td></tr>
  <tr><td style="padding:36px 32px;color:#c4bfe8">
    <h2 style="color:#f0eeff;font-size:22px;font-weight:800;margin:0 0 12px">🎉 Email confirmado! Vamos começar.</h2>
    <p style="font-size:15px;line-height:1.6;margin:0 0 20px">Olá <strong style="color:#f0eeff">' . htmlspecialchars($toName) . '</strong>,<br>
    O teu email foi confirmado. Os teus <strong style="color:#2DD4A0">13 dias de teste grátis</strong> começaram agora!</p>
    <div style="background:rgba(123,79,224,.1);border:1px solid rgba(123,79,224,.2);border-radius:10px;padding:16px 20px;margin:20px 0">
      <p style="margin:0;font-size:13px;line-height:1.7;color:#c4bfe8">
        ✅ Instala a extensão Chrome<br>
        ✅ Define o teu primeiro nicho de pesquisa<br>
        ✅ Cria o teu Visual Pitch no Canva<br>
        ✅ Activa as sequências automáticas
      </p>
    </div>
    <div style="text-align:center;margin:28px 0">
      <a href="' . $dashUrl . '" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#7B4FE0,#2E9CF5);color:#fff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700">Abrir Dashboard →</a>
    </div>
  </td></tr>
  <tr><td style="padding:16px 32px;border-top:1px solid rgba(255,255,255,.06);text-align:center">
    <p style="font-size:11px;color:#3d3960;margin:0">© 2025 VisIQ · <a href="mailto:suporte@visiq.pro" style="color:#3d3960">suporte@visiq.pro</a></p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

    $headers  = "From: VisIQ <noreply@visiq.pro>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($toEmail, $subject, $html, $headers);
}
