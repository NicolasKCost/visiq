<?php
// POST /api/stripe/portal — abre o portal de gestão de subscrição Stripe
$userId = require_auth();
$pdo    = db();

$stmt = $pdo->prepare("SELECT stripe_customer_id FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (empty($user['stripe_customer_id'])) {
    json_error(400, 'Sem subscrição activa. Subscreve um plano primeiro.');
}

$stripeKey = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '';
if (!$stripeKey) json_error(500, 'Stripe não configurado');

$ch = curl_init('https://api.stripe.com/v1/billing_portal/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => "$stripeKey:",
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS     => http_build_query([
        'customer'   => $user['stripe_customer_id'],
        'return_url' => 'https://visiq.pro/dashboard/',
    ]),
]);
$resp = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($resp['url'])) {
    json_error(500, $resp['error']['message'] ?? 'Erro ao abrir portal');
}

json_ok(['portal_url' => $resp['url']]);
