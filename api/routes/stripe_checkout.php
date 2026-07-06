<?php
// POST /api/stripe/checkout — cria uma sessão de checkout Stripe
$userId = require_auth();
$pdo    = db();

$body     = json_decode(file_get_contents('php://input'), true) ?: [];
$priceId  = trim($body['price_id'] ?? '');
$currency = strtolower(trim($body['currency'] ?? 'eur'));

if (!$priceId) json_error(400, 'price_id obrigatório');

// Busca dados do utilizador
$stmt = $pdo->prepare("SELECT full_name, email, stripe_customer_id FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) json_error(404, 'Utilizador não encontrado');

$stripeKey = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '';
if (!$stripeKey) json_error(500, 'Stripe não configurado. Adiciona STRIPE_SECRET_KEY ao config.php');

// Chama a API Stripe via cURL
function stripeRequest(string $method, string $endpoint, array $data, string $key): array {
    $ch = curl_init("https://api.stripe.com/v1/$endpoint");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => "$key:",
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true) ?: [];
}

// Cria ou reutiliza customer Stripe
$customerId = $user['stripe_customer_id'] ?? null;
if (!$customerId) {
    $customer = stripeRequest('POST', 'customers', [
        'email' => $user['email'],
        'name'  => $user['full_name'],
        'metadata[user_id]' => $userId,
    ], $stripeKey);

    if (!empty($customer['id'])) {
        $customerId = $customer['id'];
        $pdo->prepare("UPDATE users SET stripe_customer_id=? WHERE id=?")
            ->execute([$customerId, $userId]);
    }
}

// Cria sessão de Checkout
$params = [
    'mode'                            => 'subscription',
    'line_items[0][price]'            => $priceId,
    'line_items[0][quantity]'         => '1',
    'success_url'                     => 'https://visiq.pro/dashboard/?payment=success&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'                      => 'https://visiq.pro/dashboard/?payment=cancelled',
    'customer'                        => $customerId,
    'subscription_data[metadata][user_id]' => $userId,
    'allow_promotion_codes'           => 'true',
    'billing_address_collection'      => 'auto',
    'locale'                          => 'auto',
];

// Adiciona moeda se não for EUR (Stripe usa a moeda do Price por defeito)
if ($currency === 'brl') {
    $params['currency'] = 'brl';
}

$session = stripeRequest('POST', 'checkout/sessions', $params, $stripeKey);

if (empty($session['url'])) {
    $errMsg = $session['error']['message'] ?? 'Erro ao criar sessão Stripe';
    json_error(500, $errMsg);
}

json_ok([
    'checkout_url' => $session['url'],
    'session_id'   => $session['id'],
]);
