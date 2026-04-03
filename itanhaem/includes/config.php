<?php
// ============================================================
// includes/config.php — Configurações do banco de dados
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'servicos_itanhaem');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'ServiçosItanhaém');
define('SITE_URL',  'http://localhost/itanhaem');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL',  SITE_URL . '/uploads/');

// Conexão PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b">
                 <h2>Erro de conexão</h2>
                 <p>Não foi possível conectar ao banco de dados.<br>
                 Verifique se o MySQL está rodando e as configurações em <code>includes/config.php</code>.</p>
                 <pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>');
        }
    }
    return $pdo;
}
