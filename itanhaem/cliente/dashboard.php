<?php
// cliente/dashboard.php
require_once '../includes/auth.php';
requireCliente();
$db = getDB();
$pageTitle = 'Minha Conta';
$uid = $_SESSION['usuario_id'];

$usuario = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$usuario->execute([$uid]);
$usuario = $usuario->fetch();

$favoritos = $db->prepare("
    SELECT p.*, c.nome AS cat_nome, c.icone AS cat_icone
    FROM favoritos f
    JOIN prestadores p ON p.id = f.prestador_id
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE f.usuario_id = ? AND p.aprovado = 1
    ORDER BY f.criado_em DESC
");
$favoritos->execute([$uid]);
$favoritos = $favoritos->fetchAll();

$avaliacoes = $db->prepare("
    SELECT a.*, p.nome AS prestador_nome
    FROM avaliacoes a
    JOIN prestadores p ON p.id = a.prestador_id
    WHERE a.usuario_id = ?
    ORDER BY a.criado_em DESC
");
$avaliacoes->execute([$uid]);
$avaliacoes = $avaliacoes->fetchAll();

// Update profile
$ok = $erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $tel  = trim($_POST['telefone'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $foto = $usuario['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $novaFoto = uploadImagem($_FILES['foto'], 'perfil');
        if ($novaFoto) $foto = $novaFoto;
    }
    $st = $db->prepare("UPDATE usuarios SET nome=?,telefone=?,bairro=?,foto=? WHERE id=?");
    $st->execute([$nome,$tel,$bairro,$foto,$uid]);
    $ok = 'Dados atualizados com sucesso!';
    $_SESSION['usuario_nome'] = $nome;
    $usuario['nome'] = $nome;
    $usuario['foto'] = $foto;
}

$aba = $_GET['aba'] ?? 'inicio';
include '../includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="dash-layout">
  <!-- Sidebar -->
  <aside class="dash-sidebar">
    <div class="sidebar-logo">
      <i class="fas fa-user-circle"></i>
      <span>Minha Conta</span>
    </div>
    <nav class="sidebar-menu">
      <a href="?aba=inicio"      class="<?= $aba==='inicio'   ?'active':'' ?>"><i class="fas fa-home"></i> Início</a>
      <a href="?aba=favoritos"   class="<?= $aba==='favoritos'?'active':'' ?>"><i class="fas fa-heart"></i> Favoritos <span style="background:var(--orange);color:#fff;border-radius:50px;padding:1px 7px;font-size:11px;margin-left:4px"><?= count($favoritos) ?></span></a>
      <a href="?aba=avaliacoes"  class="<?= $aba==='avaliacoes'?'active':'' ?>"><i class="fas fa-star"></i> Minhas Avaliações</a>
      <a href="?aba=mensagens"   class="<?= $aba==='mensagens'?'active':'' ?>"><i class="fas fa-comments"></i> Mensagens</a>
      <a href="?aba=perfil"      class="<?= $aba==='perfil'   ?'active':'' ?>"><i class="fas fa-edit"></i> Editar Perfil</a>
      <div class="sidebar-sep">Sistema</div>
      <a href="<?= SITE_URL ?>/buscar.php"><i class="fas fa-search"></i> Buscar Serviços</a>
      <a href="<?= SITE_URL ?>/includes/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="dash-main">
    <div class="dash-header">
      <h1>Olá, <?= h($usuario['nome']) ?>! 👋</h1>
      <p>Bem-vindo à sua área de cliente</p>
    </div>

    <?php if ($ok):  ?><div class="alert alert-success" data-autodismiss><i class="fas fa-check-circle"></i> <?= h($ok) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($erro) ?></div><?php endif; ?>

    <?php if ($aba === 'inicio'): ?>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-heart"></i></div>
          <div><div class="stat-num"><?= count($favoritos) ?></div><div class="stat-label">Favoritos</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-star"></i></div>
          <div><div class="stat-num"><?= count($avaliacoes) ?></div><div class="stat-label">Avaliações feitas</div></div>
        </div>
      </div>
      <div class="table-card">
        <div class="table-card-header">
          <h3>Acesso Rápido</h3>
        </div>
        <div style="padding:20px;display:flex;flex-wrap:wrap;gap:12px">
          <a href="<?= SITE_URL ?>/buscar.php" class="btn btn-primary"><i class="fas fa-search"></i> Buscar Serviços</a>
          <a href="?aba=favoritos" class="btn btn-outline"><i class="fas fa-heart"></i> Meus Favoritos</a>
          <a href="chat.php" class="btn btn-ghost"><i class="fas fa-comments"></i> Mensagens</a>
          <a href="?aba=perfil" class="btn btn-ghost"><i class="fas fa-edit"></i> Editar Perfil</a>
        </div>
      </div>

    <?php elseif ($aba === 'favoritos'): ?>
      <h2 style="margin-bottom:20px">Meus Favoritos</h2>
      <?php if ($favoritos): ?>
        <div class="cards-grid">
          <?php foreach ($favoritos as $p): ?>
            <?php include '../includes/card_prestador.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-heart"></i>
          <h3>Nenhum favorito ainda</h3>
          <p>Explore os profissionais e favorite os que mais gostar</p>
          <a href="<?= SITE_URL ?>/buscar.php" class="btn btn-primary mt-3">Buscar Profissionais</a>
        </div>
      <?php endif; ?>

    <?php elseif ($aba === 'avaliacoes'): ?>
      <h2 style="margin-bottom:20px">Minhas Avaliações</h2>
      <div class="table-card">
        <?php if ($avaliacoes): ?>
          <table>
            <thead><tr><th>Profissional</th><th>Nota</th><th>Comentário</th><th>Data</th></tr></thead>
            <tbody>
              <?php foreach ($avaliacoes as $av): ?>
                <tr>
                  <td><a href="<?= SITE_URL ?>/perfil-prestador.php?id=<?= $av['prestador_id'] ?>"><?= h($av['prestador_nome']) ?></a></td>
                  <td><?= estrelas((float)$av['nota']) ?></td>
                  <td style="font-size:13px;color:var(--gray-600)"><?= h($av['comentario'] ?? '—') ?></td>
                  <td style="font-size:12px;color:var(--gray-400)"><?= date('d/m/Y', strtotime($av['criado_em'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-state"><i class="fas fa-star"></i><h3>Nenhuma avaliação feita</h3></div>
        <?php endif; ?>
      </div>

    <?php elseif ($aba === 'mensagens'): ?>
      <?php include 'chat.php'; ?>

    <?php elseif ($aba === 'perfil'): ?>
      <div style="max-width:520px">
        <h2 style="margin-bottom:20px">Editar Perfil</h2>
        <div style="background:#fff;border-radius:var(--radius-lg);padding:28px;border:1px solid var(--gray-200)">
          <form method="POST" enctype="multipart/form-data">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px">
              <?php if (!empty($usuario['foto'])): ?>
                <img id="fotoPreview" src="<?= SITE_URL ?>/<?= h($usuario['foto']) ?>" class="foto-preview">
              <?php else: ?>
                <img id="fotoPreview" src="" class="foto-preview" style="display:none">
                <div style="width:80px;height:80px;border-radius:50%;background:var(--gray-100);display:grid;place-items:center;font-size:32px;color:var(--gray-400)">
                  <i class="fas fa-user"></i>
                </div>
              <?php endif; ?>
              <div>
                <label style="cursor:pointer;font-size:13px;font-weight:600;color:var(--blue)">
                  <i class="fas fa-camera"></i> Alterar foto
                  <input type="file" name="foto" accept="image/*" style="display:none" data-preview="fotoPreview">
                </label>
              </div>
            </div>
            <div class="form-group">
              <label>Nome</label>
              <input type="text" name="nome" required value="<?= h($usuario['nome']) ?>">
            </div>
            <div class="form-group">
              <label>Telefone</label>
              <input type="text" name="telefone" value="<?= h($usuario['telefone'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Bairro</label>
              <input type="text" name="bairro" value="<?= h($usuario['bairro'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Salvar Alterações
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php include '../includes/footer.php'; ?>
