<?php
// PUT /api/settings
$userId = require_auth();
$body   = json_decode(file_get_contents('php://input'), true);
$pdo    = db();

if (!$body) json_error(400, 'Dados inválidos');

// Tenta actualizar coluna a coluna para evitar erros se alguma não existir
$updated = false;

// Campos sempre disponíveis (existem na tabela original)
$basic = [];
if (isset($body['full_name']) && $body['full_name'])
    $basic['full_name'] = trim($body['full_name']);

if ($basic) {
    $sets   = array_map(fn($k)=>"$k=?", array_keys($basic));
    $vals   = array_values($basic);
    $vals[] = $userId;
    $pdo->prepare("UPDATE users SET ".implode(',',$sets)." WHERE id=?")->execute($vals);
    $updated = true;
}

// Campos novos — tenta um a um com try/catch
$extra = [
    'work_hour_start'      => isset($body['work_hour_start'])      ? max(0,min(23,(int)$body['work_hour_start']))      : null,
    'work_hour_end'        => isset($body['work_hour_end'])        ? max(0,min(23,(int)$body['work_hour_end']))        : null,
    'daily_invite_limit'   => isset($body['daily_invite_limit'])   ? max(1,min(100,(int)$body['daily_invite_limit']))  : null,
    'timezone'             => $body['timezone']             ?? null,
    'linkedin_url'         => $body['linkedin_url']         ?? null,
    'notifications_email'  => isset($body['notifications_email'])  ? (int)(bool)$body['notifications_email']  : null,
    'notifications_summary'=> isset($body['notifications_summary'])? (int)(bool)$body['notifications_summary']: null,
];

foreach ($extra as $field => $value) {
    if ($value === null) continue;
    try {
        $pdo->prepare("UPDATE users SET $field=? WHERE id=?")->execute([$value, $userId]);
        $updated = true;
    } catch (PDOException $e) {
        // Coluna não existe ainda — ignora silenciosamente
    }
}

json_ok(['updated' => $updated]);
