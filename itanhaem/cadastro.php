<?php
// cadastro.php
require_once 'includes/auth.php';
$db = getDB();
$pageTitle = 'Criar Conta';
$tipo = $_GET['tipo'] ?? ''; // 'cliente' ou 'prestador'
$cats = $db->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
$erro = '';
$ok   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'cliente';
    $nome = trim($_POST['nome'] ?? '');
    $email= trim($_POST['email'] ?? '');
    $senha= $_POST['senha'] ?? '';
    $tel  = trim($_POST['telefone'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');

    if (strlen($nome) < 3)  $erro = 'Nome muito curto.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erro = 'E-mail inválido.';
    elseif (strlen($senha) < 6) $erro = 'A senha deve ter ao menos 6 caracteres.';
    else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        if ($tipo === 'prestador') {
            $cat  = (int)($_POST['categoria_id'] ?? 0);
            $wpp  = trim($_POST['whatsapp'] ?? $tel);
            $desc = trim($_POST['descricao'] ?? '');
            // Checa email duplicado
            $dup = $db->prepare("SELECT id FROM prestadores WHERE email = ?");
            $dup->execute([$email]);
            if ($dup->fetch()) { $erro = 'Este e-mail já está cadastrado como prestador.'; }
            else {
                $foto = null;
                if (!empty($_FILES['foto']['name'])) {
                    $foto = uploadImagem($_FILES['foto'], 'perfil');
                }
                $st = $db->prepare("INSERT INTO prestadores
                    (nome,email,senha,telefone,whatsapp,categoria_id,bairro,descricao,foto,aprovado)
                    VALUES (?,?,?,?,?,?,?,?,?,0)");
                $st->execute([$nome,$email,$hash,$tel,$wpp,$cat,$bairro,$desc,$foto]);
                $ok = 'Cadastro realizado! Aguarde a aprovação do administrador.';
                $tipo = '';
            }
        } else {
            $dup = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $dup->execute([$email]);
            if ($dup->fetch()) { $erro = 'Este e-mail já está cadastrado.'; }
            else {
                $st = $db->prepare("INSERT INTO usuarios (nome,email,senha,telefone,bairro,tipo) VALUES (?,?,?,?,?,'cliente')");
                $st->execute([$nome,$email,$hash,$tel,$bairro]);
                loginUsuario($email, $senha);
                redirect(SITE_URL . '/cliente/dashboard.php');
            }
        }
    }
}

include 'includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div style="padding:50px 20px;">
  <div class="container">

    <?php if (!$tipo): ?>
    <!-- Escolha de tipo -->
    <div class="text-center mb-4">
      <h1>Crie sua conta grátis</h1>
      <p style="color:var(--gray-600)">Escolha como deseja se cadastrar</p>
    </div>
    <?php if ($ok): ?>
      <div class="alert alert-success text-center" style="max-width:500px;margin:0 auto 20px">
        <i class="fas fa-check-circle"></i> <?= h($ok) ?>
        <br><a href="login.php?redir=prestador">Fazer login</a>
      </div>
    <?php endif; ?>
    <div class="auth-type-cards" style="max-width:600px;margin:0 auto">
      <a href="cadastro.php?tipo=cliente" class="auth-type-card">
        <i class="fas fa-user"></i>
        <h3>Sou Cliente</h3>
        <p>Quero encontrar e contratar profissionais na cidade</p>
      </a>
      <a href="cadastro.php?tipo=prestador" class="auth-type-card">
        <i class="fas fa-briefcase"></i>
        <h3>Sou Prestador</h3>
        <p>Quero divulgar meus serviços e conquistar mais clientes</p>
      </a>
    </div>
    <div class="text-center mt-3" style="font-size:14px;color:var(--gray-600)">
      Já tem conta? <a href="login.php">Fazer login</a>
    </div>

    <?php elseif ($tipo === 'cliente'): ?>
    <!-- Formulário Cliente -->
    <div class="form-card">
      <h2>Criar conta de Cliente</h2>
      <p>Preencha os dados abaixo para se cadastrar</p>
      <?php if ($erro): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($erro) ?></div><?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="tipo" value="cliente">
        <div class="form-group">
          <label>Nome completo *</label>
          <input type="text" name="nome" required value="<?= h($_POST['nome'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>E-mail *</label>
          <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Senha *</label>
            <input type="password" name="senha" required placeholder="Mínimo 6 caracteres">
          </div>
          <div class="form-group">
            <label>Telefone</label>
            <input type="text" name="telefone" placeholder="(13) 9 9999-9999" value="<?= h($_POST['telefone'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Bairro em Itanhaém</label>
          <input type="text" name="bairro" placeholder="Ex: Centro, Cibratel..." value="<?= h($_POST['bairro'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <i class="fas fa-user-plus"></i> Criar conta
        </button>
      </form>
      <div class="text-center mt-3" style="font-size:14px">
        <a href="cadastro.php"><i class="fas fa-arrow-left"></i> Voltar</a>
      </div>
    </div>

    <?php else: ?>
    <!-- Formulário Prestador -->
    <div class="form-card" style="max-width:620px">
      <h2>Cadastrar como Prestador</h2>
      <p>Seu perfil será revisado antes de aparecer no site</p>
      <?php if ($erro): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($erro) ?></div><?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="tipo" value="prestador">
        <div class="form-group">
          <label>Nome completo *</label>
          <input type="text" name="nome" required value="<?= h($_POST['nome'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>E-mail *</label>
          <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Senha *</label>
            <input type="password" name="senha" required placeholder="Mínimo 6 caracteres">
          </div>
          <div class="form-group">
            <label>Telefone *</label>
            <input type="text" name="telefone" required placeholder="(13) 9 9999-9999" value="<?= h($_POST['telefone'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>WhatsApp</label>
            <input type="text" name="whatsapp" placeholder="Ex: 5513991110001" value="<?= h($_POST['whatsapp'] ?? '') ?>">
            <p class="form-hint">Com código do país: 55 + DDD + número</p>
          </div>
          <div class="form-group">
            <label>Bairro de Atuação</label>
            <input type="text" name="bairro" placeholder="Ex: Centro, Toda a cidade" value="<?= h($_POST['bairro'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Categoria de Serviço *</label>
          <select name="categoria_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (($_POST['categoria_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                <?= h($c['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Descrição profissional</label>
          <textarea name="descricao" placeholder="Fale sobre sua experiência, especialidades..."><?= h($_POST['descricao'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Foto de Perfil</label>
          <input type="file" name="foto" accept="image/*" data-preview="fotoPreview">
          <div class="mt-2">
            <img id="fotoPreview" src="" alt="" style="display:none;width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--blue-light)">
          </div>
        </div>
        <button type="submit" class="btn btn-orange btn-block btn-lg">
          <i class="fas fa-paper-plane"></i> Enviar Cadastro
        </button>
      </form>
      <div class="text-center mt-3" style="font-size:14px">
        <a href="cadastro.php"><i class="fas fa-arrow-left"></i> Voltar</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const SITE_URL = '<?= SITE_URL ?>';
// Show foto preview
document.querySelector('input[data-preview="fotoPreview"]')?.addEventListener('change', function() {
  const preview = document.getElementById('fotoPreview');
  if (this.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(this.files[0]);
  }
});
</script>

<?php include 'includes/footer.php'; ?>
