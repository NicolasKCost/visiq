<?php
// api/config.example.php
// Copia este arquivo para api/config.php e preenche com os valores reais.
// api/config.php NUNCA deve ser commitado no Git (ver .gitignore).

define('DB_HOST', 'localhost');
define('DB_NAME', 'o_teu_banco');
define('DB_USER', 'o_teu_user_mysql');
define('DB_PASS', 'a_tua_senha_mysql');

// String aleatória longa (mínimo 32 caracteres) usada para assinar os tokens de login.
// Gera uma com: php -r "echo bin2hex(random_bytes(32));"
define('JWT_SECRET', 'troque-por-string-aleatoria-longa-aqui-minimo-32-chars');

// ── Stripe ──────────────────────────────────────────────────
// https://dashboard.stripe.com/apikeys
define('STRIPE_SECRET_KEY',      'sk_live_ou_sk_test_...');
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_ou_pk_test_...');

// Signing secret do endpoint de webhook configurado no Stripe
define('STRIPE_WEBHOOK_SECRET',  'whsec_...');

// ── Price IDs dos planos ──────────────────────────────────────
define('PRICE_STARTER_MONTHLY', 'price_...');
define('PRICE_STARTER_ANNUAL',  'price_...');
define('PRICE_GROWTH_MONTHLY',  'price_...');
define('PRICE_GROWTH_ANNUAL',   'price_...');

// Mapa de Price IDs → plano (usado pelo webhook)
define('STRIPE_PRICE_MAP', [
    'starter' => [
        'price_...',
        'price_...',
    ],
    'growth' => [
        'price_...',
        'price_...',
    ],
]);

// Limites
define('QUEUE_MAX_PER_RUN', 1);
define('WORK_HOUR_START', 9);
define('WORK_HOUR_END',   18);
