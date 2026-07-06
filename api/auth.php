<?php
// api/auth.php — JWT minimalista (sem biblioteca externa)

function jwt_create(int $userId): string {
    $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode([
        'sub' => $userId,
        'iat' => time(),
        'exp' => time() + 60 * 60 * 24 * 30  // 30 dias
    ]));
    $sig = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

function jwt_verify(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $payload, $sig] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));

    if (!hash_equals($expected, $sig)) return null;

    $data = json_decode(base64url_decode($payload), true);
    if (!$data || $data['exp'] < time()) return null;

    return $data;
}

// Middleware: lê o token do header Authorization e retorna o user_id
// Chama json_error e encerra se inválido
function require_auth(): int {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/', $authHeader, $m)) {
        json_error(401, 'Token não fornecido');
    }
    $claims = jwt_verify($m[1]);
    if (!$claims) {
        json_error(401, 'Token inválido ou expirado');
    }
    return (int) $claims['sub'];
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}
