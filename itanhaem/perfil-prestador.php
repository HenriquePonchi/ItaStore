<?php
// perfil-prestador.php
require_once 'includes/auth.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("
    SELECT p.*, c.nome AS cat_nome, c.icone AS cat_icone
    FROM prestadores p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.id = ? AND p.aprovado = 1 AND p.bloqueado = 0
");
$st->execute([$id]);
$p = $st->fetch();

if (!$p) {
    header('Location: ' . SITE_URL . '/buscar.php');
    exit;
}

$pageTitle = $p['nome'];

// Serviços
$servicos = $db->prepare("SELECT * FROM servicos WHERE prestador_id = ?");
$servicos->execute([$id]);
$servicos = $servicos->fetchAll();

// Portfólio
$portfolio = $db->prepare("SELECT * FROM portfolio WHERE prestador_id = ? ORDER BY criado_em DESC LIMIT 12");
$portfolio->execute([$id]);
$portfolio = $portfolio->fetchAll();

// Avaliações
$avsQ = $db->prepare("
    SELECT a.*, u.nome AS autor
    FROM avaliacoes a
    LEFT JOIN usuarios u ON u.id = a.usuario_id
    WHERE a.prestador_id = ? AND a.aprovado = 1
    ORDER BY a.criado_em DESC
");
$avsQ->execute([$id]);
$avs = $avsQ->fetchAll();

// Já avaliou?
$jaAvaliou = false;
if (isLoggedCliente()) {
    $chk = $db->prepare("SELECT id FROM avaliacoes WHERE prestador_id = ? AND usuario_id = ?");
    $chk->execute([$id, $_SESSION['usuario_id']]);
    $jaAvaliou = (bool)$chk->fetchColumn();
}

// Postar avaliação
$msgAv = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nota']) && isLoggedCliente() && !$jaAvaliou) {
    $nota = max(1, min(5, (int)$_POST['nota']));
    $coment = trim($_POST['comentario'] ?? '');
    $insAv = $db->prepare("INSERT INTO avaliacoes (prestador_id,usuario_id,nota,comentario) VALUES (?,?,?,?)");
    $insAv->execute([$id, $_SESSION['usuario_id'], $nota, $coment]);
    // Atualiza média
    $db->prepare("UPDATE prestadores SET
        nota_media = (SELECT AVG(nota) FROM avaliacoes WHERE prestador_id = ? AND aprovado=1),
        total_avaliacoes = (SELECT COUNT(*) FROM avaliacoes WHERE prestador_id = ? AND aprovado=1)
        WHERE id = ?")->execute([$id,$id,$id]);
    $jaAvaliou = true;
    $msgAv = 'Avaliação enviada! Obrigado.';
    // Refresh avaliações
    $avsQ->execute([$id]);
    $avs = $avsQ->fetchAll();
}

// Favorito
$isFav = false;
if (isLoggedCliente()) {
    $chkFav = $db->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND prestador_id = ?");
    $chkFav->execute([$_SESSION['usuario_id'], $id]);
    $isFav = (bool)$chkFav->fetchColumn();
}

include 'includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="section" style="padding-top:24px">
  <div class="container">
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>">Início</a><span>/</span>
      <a href="buscar.php">Buscar</a><span>/</span>
      <span><?= h($p['nome']) ?></span>
    </div>

    <div class="perfil-layout">
      <!-- Sidebar -->
      <div>
        <div class="perfil-card">
          <?php if (!empty($p['foto']) && file_exists(dirname(__FILE__) . '/' . $p['foto'])): ?>
            <img src="<?= SITE_URL ?>/<?= h($p['foto']) ?>" alt="<?= h($p['nome']) ?>" class="perfil-foto">
          <?php else: ?>
            <div class="perfil-foto-placeholder"><i class="fas fa-user-circle"></i></div>
          <?php endif; ?>

          <h2 class="perfil-nome"><?= h($p['nome']) ?></h2>
          <?php if (!empty($p['cat_nome'])): ?>
            <p class="perfil-cat"><i class="fas <?= h($p['cat_icone'] ?? 'fa-briefcase') ?>"></i> <?= h($p['cat_nome']) ?></p>
          <?php endif; ?>
          <?php if (!empty($p['bairro'])): ?>
            <p class="perfil-loc"><i class="fas fa-map-marker-alt"></i> <?= h($p['bairro']) ?>, Itanhaém/SP</p>
          <?php endif; ?>

          <?php if ($p['total_avaliacoes'] > 0): ?>
            <div class="perfil-nota">
              <?= estrelas((float)$p['nota_media']) ?>
              <strong><?= number_format($p['nota_media'],1) ?></strong>
              <span style="color:var(--gray-400);font-size:12px">(<?= $p['total_avaliacoes'] ?>)</span>
            </div>
          <?php endif; ?>

          <div class="perfil-btns">
            <?php if (!empty($p['whatsapp'])): ?>
              <a href="<?= whatsappLink($p['whatsapp'], 'Olá ' . $p['nome'] . '! Vi seu perfil no ServiçosItanhaém. Poderia me dar um orçamento?') ?>"
                 target="_blank" class="btn btn-whatsapp">
                <i class="fab fa-whatsapp"></i> Chamar no WhatsApp
              </a>
            <?php endif; ?>
            <?php if (!empty($p['telefone'])): ?>
              <a href="tel:<?= h($p['telefone']) ?>" class="btn btn-outline">
                <i class="fas fa-phone"></i> <?= h($p['telefone']) ?>
              </a>
            <?php endif; ?>
            <?php if (isLoggedCliente()): ?>
              <button class="btn btn-ghost btn-favoritar <?= $isFav?'favorited':'' ?>"
                      data-id="<?= $p['id'] ?>"
                      style="color:<?= $isFav?'#ef4444':'inherit' ?>">
                <i class="<?= $isFav?'fas':'far' ?> fa-heart"></i>
                <?= $isFav ? 'Favoritado' : 'Favoritar' ?>
              </button>
              <a href="cliente/chat.php?prestador=<?= $p['id'] ?>" class="btn btn-ghost">
                <i class="fas fa-comments"></i> Enviar Mensagem
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Conteúdo Principal -->
      <div class="perfil-main">

        <!-- Sobre -->
        <?php if (!empty($p['descricao'])): ?>
        <div class="perfil-section">
          <h3><i class="fas fa-user" style="color:var(--blue)"></i> Sobre</h3>
          <p style="color:var(--gray-600);line-height:1.7"><?= nl2br(h($p['descricao'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Serviços -->
        <?php if ($servicos): ?>
        <div class="perfil-section">
          <h3><i class="fas fa-list" style="color:var(--blue)"></i> Serviços Oferecidos</h3>
          <?php foreach ($servicos as $s): ?>
            <div class="servico-item">
              <div>
                <div class="servico-nome"><?= h($s['titulo']) ?></div>
                <?php if (!empty($s['descricao'])): ?>
                  <div class="servico-desc"><?= h($s['descricao']) ?></div>
                <?php endif; ?>
              </div>
              <?php if (!empty($s['preco'])): ?>
                <div class="servico-preco"><?= h($s['preco']) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Portfólio -->
        <?php if ($portfolio): ?>
        <div class="perfil-section">
          <h3><i class="fas fa-images" style="color:var(--blue)"></i> Portfólio</h3>
          <div class="portfolio-grid">
            <?php foreach ($portfolio as $img): ?>
              <a href="<?= SITE_URL ?>/<?= h($img['imagem']) ?>" target="_blank">
                <img src="<?= SITE_URL ?>/<?= h($img['imagem']) ?>"
                     alt="<?= h($img['legenda'] ?? 'Trabalho') ?>"
                     title="<?= h($img['legenda'] ?? '') ?>">
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Avaliações -->
        <div class="perfil-section">
          <h3><i class="fas fa-star" style="color:var(--blue)"></i> Avaliações <?php if ($avs): ?>(<?= count($avs) ?>)<?php endif; ?></h3>

          <?php if ($msgAv): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($msgAv) ?></div>
          <?php endif; ?>

          <?php if (isLoggedCliente() && !$jaAvaliou): ?>
            <div style="background:var(--gray-50);border-radius:var(--radius);padding:18px;margin-bottom:20px;border:1px solid var(--gray-200)">
              <h4 style="margin-bottom:14px;font-size:14px">Deixe sua avaliação</h4>
              <form method="POST">
                <div style="margin-bottom:12px">
                  <div class="star-rating" id="starRating">
                    <?php for($i=5;$i>=1;$i--): ?>
                      <input type="radio" name="nota" id="star<?=$i?>" value="<?=$i?>" <?=$i===5?'required':''?>>
                      <label for="star<?=$i?>">★</label>
                    <?php endfor; ?>
                  </div>
                </div>
                <div class="form-group">
                  <textarea name="comentario" placeholder="Conte como foi a experiência (opcional)"
                            style="height:80px;resize:none"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Enviar Avaliação</button>
              </form>
            </div>
          <?php elseif (!isLoggedCliente()): ?>
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              <a href="login.php">Faça login</a> para avaliar este profissional.
            </div>
          <?php endif; ?>

          <?php if ($avs): ?>
            <?php foreach ($avs as $av): ?>
              <div class="avaliacao-item">
                <div class="avaliacao-header">
                  <span class="avaliacao-autor"><i class="fas fa-user-circle" style="color:var(--blue)"></i> <?= h($av['autor']) ?></span>
                  <span class="avaliacao-data"><?= date('d/m/Y', strtotime($av['criado_em'])) ?></span>
                </div>
                <div style="margin-bottom:6px"><?= estrelas((float)$av['nota']) ?></div>
                <?php if (!empty($av['comentario'])): ?>
                  <p class="avaliacao-texto"><?= h($av['comentario']) ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color:var(--gray-400);font-size:14px">Ainda não há avaliações.</p>
          <?php endif; ?>
        </div>

      </div><!-- /perfil-main -->
    </div><!-- /perfil-layout -->
  </div>
</div>

<style>
/* Star rating fix - RTL trick */
.star-rating { display:flex; flex-direction:row-reverse; justify-content:flex-end; gap:4px; }
.star-rating input { display:none; }
.star-rating label { font-size:28px; color:var(--gray-200); cursor:pointer; }
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label { color:#f59e0b; }
</style>

<?php include 'includes/footer.php'; ?>
