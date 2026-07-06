<?php
// api/routes/image_templates_update.php
$userId = require_auth();
$tmplId = $GLOBALS['image_template_id'];
$pdo    = db();

$stmt = $pdo->prepare('SELECT * FROM image_templates WHERE id=? AND user_id=? AND is_global=0');
$stmt->execute([$tmplId, $userId]);
$tmpl = $stmt->fetch();
if (!$tmpl) json_error(404, 'Template não encontrado');

$data      = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);
$imagePath = $tmpl['base_image_path']; // mantém a imagem actual por defeito

if (!empty($_FILES['image'])) {
    $errCode = (int)$_FILES['image']['error'];

    if ($errCode !== UPLOAD_ERR_OK) {
        $msgs = [
            1 => 'Imagem muito grande (limite: '.ini_get('upload_max_filesize').'). Usa uma imagem mais pequena ou converte para JPG.',
            2 => 'Imagem muito grande para o formulário.',
            3 => 'Upload incompleto. Tenta novamente.',
            4 => 'Nenhum ficheiro recebido.',
            6 => 'Pasta temporária não encontrada.',
            7 => 'Sem permissão de escrita.',
        ];
        json_error(400, $msgs[$errCode] ?? "Erro de upload código $errCode");
    }

    $file    = $_FILES['image'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) json_error(400, 'Formato inválido.');

    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    $filename = 'vp_' . $userId . '_' . uniqid() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        json_error(500,
            'Falha ao guardar. tmp_exists=' . (file_exists($file['tmp_name'])?'sim':'não') .
            ' dir_ok=' . (is_writable($uploadDir)?'sim':'não')
        );
    }

    // Remove imagem antiga após sucesso
    if ($tmpl['base_image_path'] && file_exists($uploadDir.$tmpl['base_image_path'])) {
        @unlink($uploadDir.$tmpl['base_image_path']);
    }

    $imagePath = $filename;
}

$name = trim($data['name'] ?? '') ?: $tmpl['name'];

try {
    $pdo->prepare("
        UPDATE image_templates
        SET name=?, base_image_path=?, text_field_label=?,
            text_pos_x=?, text_pos_y=?, text_max_width=?,
            font_size=?, font_color=?
        WHERE id=?
    ")->execute([
        $name, $imagePath,
        $data['text_field_label'] ?? $tmpl['text_field_label'],
        (int)($data['text_pos_x']     ?? $tmpl['text_pos_x']),
        (int)($data['text_pos_y']     ?? $tmpl['text_pos_y']),
        (int)($data['text_max_width'] ?? $tmpl['text_max_width']),
        (int)($data['font_size']      ?? $tmpl['font_size']),
        $data['font_color']            ?? $tmpl['font_color'],
        $tmplId,
    ]);
} catch (PDOException $e) {
    json_error(500, 'Erro na base de dados: ' . $e->getMessage());
}

json_ok([
    'id'            => $tmplId,
    'name'          => $name,
    'base_image_url'=> $imagePath ? 'https://visiq.pro/uploads/'.$imagePath : null,
]);
