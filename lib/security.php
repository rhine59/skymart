<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($submitted) || !is_string($expected) || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('Invalid request token.');
    }
}

function require_login(): void
{
    if (empty($_SESSION['auth_id'])) {
        header('Location: index.php');
        exit;
    }
}

function current_user_id(): ?int
{
    return isset($_SESSION['auth_id']) ? (int) $_SESSION['auth_id'] : null;
}
