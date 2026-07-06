<?php
// api/routes/image_templates_create.php
$userId = require_auth();
$pdo    = db();

$name = trim($_POST['name'] ?? '');
if (!$name) json_error(400, 'Nome é obrigatório');

$imagePath = null;

// Se foi enviado um ficheiro, tem de ser processado com sucesso
// Caso contrário, não cria o registo
if (!empty($_FILES['image'])) {
    $errCode = (int)$_FILES['image']['error'];

    if ($errCode !== UPLOAD_ERR_OK) {
        $msgs = [
            1 => 'Imagem muito grande para o servidor (limite: '.ini_get('upload_max_filesize').'). Reduz o tamanho.',
            2 => 'Imagem muito grande para o formulário.',
            3 => 'Upload incompleto. Tenta novamente.',
            4 => 'Nenhum ficheiro recebido.',
            6 => 'Pasta temporária não encontrada no servidor.',
            7 => 'Sem permissão de escrita no servidor.',
        ];
        json_error(400, $msgs[$errCode] ?? "Erro de upload (código $errCode)");
    }

    $file    = $_FILES['image'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];

    if (!in_array($ext, $allowed)) {
        json_error(400, 'Formato inválido. Usa JPG, PNG, WebP ou GIF.');
    }

    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    $filename  = 'vp_' . $userId . '_' . uniqid() . '.' . $ext;
    $dest      = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        // Diagnóstico detalhado
        $tmpOk   = file_exists($file['tmp_name']);
        $dirOk   = is_writable($uploadDir);
        json_error(500,
            'Falha ao guardar imagem. ' .
            'tmp_exists=' . ($tmpOk?'sim':'não') . ' ' .
            'dir_writable=' . ($dirOk?'sim':'não') . ' ' .
            'tmp=' . $file['tmp_name'] . ' ' .
            'dest=' . $dest
        );
    }

    $imagePath = $filename;
}

// Só insere na DB se tudo correu bem
try {
    $pdo->prepare("
        INSERT INTO image_templates
            (user_id, name, base_image_path, text_field_label,
             text_pos_x, text_pos_y, text_max_width,
             font_size, font_color, logo_pos_x, logo_pos_y, logo_size, is_global)
        VALUES (?, ?, ?, 'company_name', 60, 80, 700, 72, '#FFFFFF', NULL, NULL, 80, 0)
    ")->execute([$userId, $name, $imagePath]);
} catch (PDOException $e) {
    // Remove ficheiro se DB falhou
    if ($imagePath && file_exists($uploadDir.$imagePath)) @unlink($uploadDir.$imagePath);
    json_error(500, 'Erro na base de dados: ' . $e->getMessage());
}

$id = (int)$pdo->lastInsertId();
json_ok([
    'id'            => $id,
    'name'          => $name,
    'base_image_url'=> $imagePath ? 'https://visiq.pro/uploads/' . $imagePath : null,
], 201);
