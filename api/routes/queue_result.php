<?php
// api/routes/queue_result.php â€” POST /api/queue/{id}/result
// Recebe o resultado de uma tarefa e agenda a prÃ³xima da sequÃªncia

$userId = require_auth();
$taskId = $GLOBALS['task_id'];
$body   = json_decode(file_get_contents('php://input'), true);
$result = $body['result'] ?? 'done';
$pdo    = db();

// Carrega a tarefa para saber o lead e o tipo de aÃ§Ã£o
$stmt = $pdo->prepare('SELECT * FROM activity_queue WHERE id = ? AND user_id = ?');
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) json_error(404, 'Tarefa nÃ£o encontrada');

// â”€â”€ Atualiza o status da tarefa atual â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$isError   = str_starts_with((string) $result, 'error');
$newStatus = $isError ? 'error' : 'done';

$pdo->prepare("
    UPDATE activity_queue
    SET status = ?, executed_at = NOW(), notes = ?, retry_count = retry_count + ?
    WHERE id = ?
")->execute([
    $newStatus,
    is_string($result) ? $result : json_encode($result),
    $isError ? 1 : 0,
    $taskId
]);

if ($isError) {
    // Se der erro, agenda uma nova tentativa em 30 minutos
    if ($task['retry_count'] < 2) {
        $pdo->prepare("
            UPDATE activity_queue
            SET status = 'pending', scheduled_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
            WHERE id = ?
        ")->execute([$taskId]);
    }
    json_ok(['scheduled_next' => false]);
}

// â”€â”€ Atualiza o status do lead â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$leadStatusMap = [
    'visit_profile'  => 'warming_up',
    'like_post'      => 'warming_up',
    'send_invite'    => 'invited',
    'send_followup_1'=> 'pitched',
    'send_followup_2'=> 'pitched',
];
if (isset($leadStatusMap[$task['action_type']])) {
    $pdo->prepare('UPDATE leads SET status = ? WHERE id = ?')
        ->execute([$leadStatusMap[$task['action_type']], $task['lead_id']]);
}

// â”€â”€ Agenda a PRÃ“XIMA tarefa da sequÃªncia de warm-up â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$nextAction = nextActionInSequence($task['action_type']);

if ($nextAction) {
    $delayMinutes = delayForAction($nextAction);
    $pdo->prepare("
        INSERT INTO activity_queue (user_id, lead_id, action_type, status, scheduled_at)
        VALUES (?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL ? MINUTE))
    ")->execute([$userId, $task['lead_id'], $nextAction, $delayMinutes]);

    json_ok(['scheduled_next' => $nextAction, 'in_minutes' => $delayMinutes]);
}

json_ok(['scheduled_next' => false]);

// â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function nextActionInSequence(string $current): ?string {
    return match ($current) {
        'visit_profile'   => 'like_post',
        'like_post'       => 'send_invite',
        'send_invite'     => 'send_followup_1',
        'send_followup_1' => 'send_followup_2',
        default           => null
    };
}

function delayForAction(string $action): int {
    return match ($action) {
        'like_post'       => 60,         //  1 hora apÃ³s visitar perfil
        'send_invite'     => 60 * 24,    // 24 horas apÃ³s o like
        'send_followup_1' => 60 * 72,    //  3 dias apÃ³s o convite
        'send_followup_2' => 60 * 168,   //  7 dias apÃ³s o follow-up 1
        default           => 60
    };
}

