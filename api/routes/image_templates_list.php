<?php
// api/routes/image_templates_list.php
$userId = require_auth();
$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, name, base_image_path, text_field_label,
           text_pos_x, text_pos_y, text_max_width,
           font_size, font_color, logo_pos_x, logo_pos_y, logo_size,
           is_global, created_at,
           IFNULL(canvas_state, '') as canvas_state
    FROM image_templates
    WHERE user_id = ? OR is_global = 1
    ORDER BY is_global ASC, created_at DESC
");
$stmt->execute([$userId]);
$templates = $stmt->fetchAll();

foreach ($templates as &$t) {
    $t['base_image_url'] = $t['base_image_path']
        ? 'https://visiq.pro/uploads/' . $t['base_image_path']
        : null;
    // canvas_state como objeto PHP se for JSON válido
    if (!empty($t['canvas_state'])) {
        $decoded = json_decode($t['canvas_state'], true);
        if ($decoded !== null) $t['canvas_state_obj'] = $decoded;
    }
}

json_ok($templates);
