<?php
// login.php
require_once 'includes/auth.php';
$pageTitle = 'Entrar';
$erro = '';
$tipo = $_GET['redir'] ?? 'cliente'; // cliente | admin | prestador

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipoPOST = $_POST['tipo'] ?? 'cliente';

    if ($tipoPOST === 'prestador') {
        if (loginPrestador($email, $senha)) {
            redirect(SITE_URL . '/prestador/dashboard.php');
        } else {
            $erro = 'E-mail ou senha incorretos. Verifique seus dados.';
        }
    } else {
        if (loginUsuario($email, $senha)) {
            if ($_SESSION['usuario_tipo'] === 'admin') {
                redirect(SITE_URL . '/admin/dashboard.php');
            } else {
                redirect(SITE_URL . '/cliente/dashboard.php');
            }
        } else {
            $erro = 'E-mail ou senha incorretos. Verifique seus dados.';
        }
    }
}
include 'includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="auth-page">
  <div class="form-card" style="width:100%;max-width:440px">
    <div class="text-center mb-4">
      <div class="logo" style="justify-content:center;margin-bottom:8px">
        <span class="logo-icon"><i class="fas fa-map-marker-alt"></i></span>
        <span class="logo-text">Serviços<strong>Itanhaém</strong></span>
      </div>
      <h2>Bem-vindo de volta!</h2>
      <p>Faça login para continuar</p>
    </div>

    <?php if ($erro): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($erro) ?></div>
    <?php endif; ?>

    <!-- Tipo de conta -->
    <div style="display:flex;gap:6px;margin-bottom:22px;background:var(--gray-100);border-radius:var(--radius);padding:4px;">
      <button type="button" class="tipo-tab btn" data-tipo="cliente"
              style="flex:1;border-radius:calc(var(--radius) - 2px);background:<?= $tipo==='prestador'?'transparent':'#fff' ?>;color:var(--gray-800);border:none;font-size:13px;">
        <i class="fas fa-user"></i> Cliente / Admin
      </button>
      <button type="button" class="tipo-tab btn" data-tipo="prestador"
              style="flex:1;border-radius:calc(var(--radius) - 2px);background:<?= $tipo==='prestador'?'#fff':'transparent' ?>;color:var(--gray-800);border:none;font-size:13px;">
        <i class="fas fa-briefcase"></i> Prestador
      </button>
    </div>

    <form method="POST" id="loginForm">
      <input type="hidden" name="tipo" id="tipoInput" value="<?= h($tipo) ?>">
      <div class="form-group">
        <label>E-mail</label>
        <input type="email" name="email" required placeholder="seu@email.com" value="<?= h($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Senha</label>
        <input type="password" name="senha" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px">
        <i class="fas fa-sign-in-alt"></i> Entrar
      </button>
    </form>

    <div class="text-center mt-3" style="font-size:14px;color:var(--gray-600)">
      Não tem conta?
      <a href="cadastro.php" style="font-weight:600">Criar conta grátis</a>
    </div>
  </div>
</div>

<script>
const SITE_URL = '<?= SITE_URL ?>';
document.querySelectorAll('.tipo-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tipo-tab').forEach(b => {
      b.style.background = 'transparent';
    });
    btn.style.background = '#fff';
    btn.style.boxShadow = '0 1px 4px rgba(0,0,0,.1)';
    document.getElementById('tipoInput').value = btn.dataset.tipo;
  });
});
</script>

<?php include 'includes/footer.php'; ?>
