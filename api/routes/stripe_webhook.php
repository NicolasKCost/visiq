<?php
// POST /api/stripe/webhook — recebe eventos do Stripe
// NÃO requer auth — validado pela assinatura Stripe

$pdo     = db();
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret  = defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : '';

// Valida assinatura do webhook
function verifyStripeSignature(string $payload, string $header, string $secret): bool {
    if (!$secret || !$header) return false;
    $parts = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = explode('=', $part, 2) + ['',''];
        $parts[$k] = $v;
    }
    $timestamp = $parts['t'] ?? '';
    $sig       = $parts['v1'] ?? '';
    if (!$timestamp || !$sig) return false;
    $expected = hash_hmac('sha256', "$timestamp.$payload", $secret);
    return hash_equals($expected, $sig);
}

if ($secret && !verifyStripeSignature($payload, $sigHeader, $secret)) {
    http_response_code(400);
    echo json_encode(['error' => 'Assinatura inválida']);
    exit;
}

$event = json_decode($payload, true);
if (!$event) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$type = $event['type'] ?? '';
$data = $event['data']['object'] ?? [];

// Mapeia price_id → plano
function priceToplan(string $priceId): string {
    $map = defined('STRIPE_PRICE_MAP') ? STRIPE_PRICE_MAP : [];
    foreach ($map as $plan => $ids) {
        if (in_array($priceId, (array)$ids)) return $plan;
    }
    // Fallback: tenta detectar pelo nickname/metadata
    return 'growth';
}

switch ($type) {

    case 'checkout.session.completed':
        // Subscrição criada com sucesso
        $customerId = $data['customer'] ?? '';
        $subId      = $data['subscription'] ?? '';
        if (!$customerId) break;

        // Busca o user_id pelo stripe_customer_id
        $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id=?");
        $stmt->execute([$customerId]);
        $user = $stmt->fetch();

        if (!$user) {
            // Tenta pelo metadata
            $userId = (int)($data['metadata']['user_id'] ?? 0);
            if ($userId) {
                $pdo->prepare("UPDATE users SET stripe_customer_id=? WHERE id=?")
                    ->execute([$customerId, $userId]);
            }
        } else {
            $userId = $user['id'];
        }

        if (!$userId) break;

        // Determina o plano pela price
        $priceId = '';
        if (!empty($data['line_items']['data'][0]['price']['id'])) {
            $priceId = $data['line_items']['data'][0]['price']['id'];
        }
        $plan = priceToplan($priceId);

        $pdo->prepare("UPDATE users SET plan=?, stripe_subscription_id=?, plan_expires_at=NULL WHERE id=?")
            ->execute([$plan, $subId, $userId]);
        break;

    case 'invoice.payment_succeeded':
        // Renovação de subscrição
        $customerId = $data['customer'] ?? '';
        $subId      = $data['subscription'] ?? '';
        $periodEnd  = $data['lines']['data'][0]['period']['end'] ?? null;
        if (!$customerId) break;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id=?");
        $stmt->execute([$customerId]);
        $user = $stmt->fetch();
        if (!$user) break;

        $expires = $periodEnd ? date('Y-m-d H:i:s', $periodEnd) : null;
        $pdo->prepare("UPDATE users SET plan_expires_at=? WHERE id=?")
            ->execute([$expires, $user['id']]);
        break;

    case 'customer.subscription.deleted':
    case 'invoice.payment_failed':
        // Subscrição cancelada ou pagamento falhou
        $customerId = $data['customer'] ?? '';
        if (!$customerId) break;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id=?");
        $stmt->execute([$customerId]);
        $user = $stmt->fetch();
        if (!$user) break;

        $pdo->prepare("UPDATE users SET plan='free', stripe_subscription_id=NULL WHERE id=?")
            ->execute([$user['id']]);
        break;

    case 'customer.subscription.updated':
        // Upgrade/downgrade de plano
        $customerId = $data['customer'] ?? '';
        $priceId    = $data['items']['data'][0]['price']['id'] ?? '';
        if (!$customerId || !$priceId) break;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id=?");
        $stmt->execute([$customerId]);
        $user = $stmt->fetch();
        if (!$user) break;

        $plan = priceToplan($priceId);
        $pdo->prepare("UPDATE users SET plan=? WHERE id=?")->execute([$plan, $user['id']]);
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);
exit;
