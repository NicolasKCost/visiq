<?php
// GET/PUT /api/admin/settings — configurações globais da plataforma
$userId = require_auth();
$pdo    = db();

$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->execute([$userId]);
$me = $stmt->fetch();
if (!$me || !$me['is_admin']) json_error(403, 'Acesso negado');

// Tabela de configurações globais (chave-valor)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS platform_settings (
        `key` VARCHAR(100) PRIMARY KEY,
        `value` TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("SELECT `key`, `value` FROM platform_settings")->fetchAll();
    $settings = [];
    foreach ($rows as $r) $settings[$r['key']] = $r['value'];

    // Defaults
    $defaults = [
        'trial_days'          => '13',
        'trial_max_leads'     => '100',
        'starter_scrape_limit'=> '25',
        'growth_scrape_limit' => '50',
        'maintenance_mode'    => '0',
        'maintenance_message' => 'O VisIQ está em manutenção. Voltamos em breve!',
        'signup_enabled'      => '1',
        'announcement'        => '',
        'support_email'       => 'suporte@visiq.pro',
    ];
    foreach ($defaults as $k => $v) {
        if (!array_key_exists($k, $settings)) $settings[$k] = $v;
    }
    json_ok($settings);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);
    foreach ($body as $k => $v) {
        $pdo->prepare("INSERT INTO platform_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=?")
            ->execute([$k, $v, $v]);
    }
    json_ok(['saved' => true]);
}
