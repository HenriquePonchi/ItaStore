<?php
// ============================================================
// includes/auth.php — Funções de autenticação e sessão
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// ── Usuário (cliente/admin) ──────────────────────────────────

function loginUsuario(string $email, string $senha): bool {
    $db = getDB();
    $st = $db->prepare("SELECT * FROM usuarios WHERE email = ? AND bloqueado = 0");
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($senha, $u['senha'])) {
        $_SESSION['usuario_id']   = $u['id'];
        $_SESSION['usuario_nome'] = $u['nome'];
        $_SESSION['usuario_tipo'] = $u['tipo'];
        return true;
    }
    return false;
}

function isLoggedCliente(): bool {
    return isset($_SESSION['usuario_id']) && $_SESSION['usuario_tipo'] === 'cliente';
}

function isLoggedAdmin(): bool {
    return isset($_SESSION['usuario_id']) && $_SESSION['usuario_tipo'] === 'admin';
}

function requireCliente(): void {
    if (!isLoggedCliente()) { header('Location: ' . SITE_URL . '/login.php?redir=cliente'); exit; }
}

function requireAdmin(): void {
    if (!isLoggedAdmin()) { header('Location: ' . SITE_URL . '/login.php?redir=admin'); exit; }
}

// ── Prestador ────────────────────────────────────────────────

function loginPrestador(string $email, string $senha): bool {
    $db = getDB();
    $st = $db->prepare("SELECT * FROM prestadores WHERE email = ? AND bloqueado = 0");
    $st->execute([$email]);
    $p = $st->fetch();
    if ($p && password_verify($senha, $p['senha'])) {
        $_SESSION['prestador_id']   = $p['id'];
        $_SESSION['prestador_nome'] = $p['nome'];
        $_SESSION['prestador_tipo'] = 'prestador';
        return true;
    }
    return false;
}

function isLoggedPrestador(): bool {
    return isset($_SESSION['prestador_id']);
}

function requirePrestador(): void {
    if (!isLoggedPrestador()) { header('Location: ' . SITE_URL . '/login.php?redir=prestador'); exit; }
}

// ── Logout ───────────────────────────────────────────────────

function logout(): void {
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// ── Helpers gerais ───────────────────────────────────────────

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function uploadImagem(array $file, string $subdir = 'perfil'): ?string {
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nome = uniqid('img_', true) . '.' . $ext;
    $dest = UPLOAD_PATH . $subdir . '/' . $nome;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'uploads/' . $subdir . '/' . $nome;
    }
    return null;
}

function estrelas(float $nota): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $nota ? 'star-on' : 'star-off';
        $html .= '<i class="fas fa-star ' . $class . '"></i>';
    }
    return $html;
}

function whatsappLink(string $numero, string $msg = ''): string {
    $numero = preg_replace('/\D/', '', $numero);
    $msg    = urlencode($msg);
    return "https://wa.me/{$numero}?text={$msg}";
}
