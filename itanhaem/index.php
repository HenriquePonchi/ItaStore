<?php
// index.php — Página inicial
require_once 'includes/auth.php';
$db = getDB();
$pageTitle = 'Início';

// Buscar categorias
$cats = $db->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Destaques
$destaques = $db->query("
  SELECT p.*, c.nome AS cat_nome, c.icone AS cat_icone
  FROM prestadores p
  LEFT JOIN categorias c ON c.id = p.categoria_id
  WHERE p.aprovado = 1 AND p.destaque = 1 AND p.bloqueado = 0
  ORDER BY p.nota_media DESC LIMIT 8
")->fetchAll();

// Mais avaliados
$topRated = $db->query("
  SELECT p.*, c.nome AS cat_nome, c.icone AS cat_icone
  FROM prestadores p
  LEFT JOIN categorias c ON c.id = p.categoria_id
  WHERE p.aprovado = 1 AND p.bloqueado = 0 AND p.total_avaliacoes > 0
  ORDER BY p.nota_media DESC, p.total_avaliacoes DESC LIMIT 8
")->fetchAll();

// Recentes
$recentes = $db->query("
  SELECT p.*, c.nome AS cat_nome
  FROM prestadores p
  LEFT JOIN categorias c ON c.id = p.categoria_id
  WHERE p.aprovado = 1 AND p.bloqueado = 0
  ORDER BY p.criado_em DESC LIMIT 4
")->fetchAll();
?>
<?php include 'includes/header.php'; ?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <h1>Encontre serviços em<br>Itanhaém / SP</h1>
    <p>Conectamos você aos melhores profissionais da cidade</p>
    <form class="hero-search" action="buscar.php" method="GET">
      <input type="text" name="q" placeholder="Ex: eletricista, pintor, diarista...">
      <select name="categoria">
        <option value="">Todas as categorias</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>"><?= h($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit"><i class="fas fa-search"></i> Buscar</button>
    </form>
    <div class="hero-tags">
      <?php foreach (array_slice($cats, 0, 8) as $c): ?>
        <a href="buscar.php?categoria=<?= $c['id'] ?>">
          <i class="fas <?= h($c['icone']) ?>"></i> <?= h($c['nome']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CATEGORIAS -->
<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Explore</span>
      <h2>Categorias de Serviços</h2>
      <p>Encontre o profissional certo para cada necessidade</p>
    </div>
    <div class="cat-grid">
      <?php foreach ($cats as $c): ?>
        <a class="cat-card" href="buscar.php?categoria=<?= $c['id'] ?>">
          <i class="fas <?= h($c['icone']) ?>"></i>
          <span><?= h($c['nome']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- DESTAQUES -->
<?php if ($destaques): ?>
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-tag" style="background:#fff7ed;color:var(--orange)">⭐ Em Destaque</span>
      <h2>Profissionais em Destaque</h2>
      <p>Os melhores prestadores de serviço de Itanhaém</p>
    </div>
    <div class="cards-grid">
      <?php foreach ($destaques as $p): ?>
        <?php include 'includes/card_prestador.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- TOP AVALIADOS -->
<?php if ($topRated): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Mais Avaliados</span>
      <h2>Profissionais Melhor Avaliados</h2>
    </div>
    <div class="cards-grid">
      <?php foreach ($topRated as $p): ?>
        <?php include 'includes/card_prestador.php'; ?>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="buscar.php" class="btn btn-primary btn-lg">Ver todos os profissionais</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- BANNER CTA -->
<section class="section" style="background: linear-gradient(135deg,#1a56db,#0f3592); color:#fff; padding:60px 0;">
  <div class="container text-center">
    <h2 style="color:#fff; margin-bottom:12px;">Você é profissional?</h2>
    <p style="opacity:.85; margin-bottom:28px; font-size:1.05rem;">
      Cadastre seus serviços gratuitamente e alcance mais clientes em Itanhaém
    </p>
    <a href="cadastro.php?tipo=prestador" class="btn btn-orange btn-lg">
      <i class="fas fa-plus-circle"></i> Cadastrar Meu Serviço
    </a>
  </div>
</section>

<!-- NOVOS PROFISSIONAIS -->
<?php if ($recentes): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Novidades</span>
      <h2>Novos Profissionais</h2>
    </div>
    <div class="cards-grid">
      <?php foreach ($recentes as $p): ?>
        <?php include 'includes/card_prestador.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
