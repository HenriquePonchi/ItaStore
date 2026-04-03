<?php
// buscar.php
require_once 'includes/auth.php';
$db = getDB();
$pageTitle = 'Buscar Serviços';

$q        = trim($_GET['q'] ?? '');
$catId    = (int)($_GET['categoria'] ?? 0);
$bairro   = trim($_GET['bairro'] ?? '');
$ordem    = $_GET['ordem'] ?? 'nota';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

$cats = $db->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Montar query
$where = ["p.aprovado = 1", "p.bloqueado = 0"];
$params = [];

if ($q) {
    $where[] = "(p.nome LIKE ? OR p.descricao LIKE ? OR c.nome LIKE ?)";
    $like = "%$q%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($catId) {
    $where[] = "p.categoria_id = ?";
    $params[] = $catId;
}
if ($bairro) {
    $where[] = "p.bairro LIKE ?";
    $params[] = "%$bairro%";
}

$whereStr = implode(' AND ', $where);
$orderMap = [
    'nota'    => 'p.nota_media DESC, p.total_avaliacoes DESC',
    'recente' => 'p.criado_em DESC',
    'nome'    => 'p.nome ASC',
];
$orderSQL = $orderMap[$ordem] ?? $orderMap['nota'];

$countSt = $db->prepare("SELECT COUNT(*) FROM prestadores p LEFT JOIN categorias c ON c.id = p.categoria_id WHERE $whereStr");
$countSt->execute($params);
$total = (int)$countSt->fetchColumn();
$pages = ceil($total / $perPage);

$st = $db->prepare("
    SELECT p.*, c.nome AS cat_nome, c.icone AS cat_icone
    FROM prestadores p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE $whereStr
    ORDER BY p.destaque DESC, $orderSQL
    LIMIT $perPage OFFSET $offset
");
$st->execute($params);
$results = $st->fetchAll();

include 'includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="section" style="padding-top:30px">
  <div class="container">
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>">Início</a>
      <span>/</span>
      <span>Buscar</span>
      <?php if ($q): ?><span>/</span><span><?= h($q) ?></span><?php endif; ?>
    </div>

    <!-- Barra de Filtros -->
    <form method="GET" class="filter-bar"
          style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius-lg);
                 padding:20px;margin-bottom:28px;display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end">
      <div class="form-group" style="margin:0;flex:1;min-width:180px">
        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Buscar</label>
        <input type="text" name="q" placeholder="Ex: eletricista..." value="<?= h($q) ?>"
               style="height:40px;border-radius:8px">
      </div>
      <div class="form-group" style="margin:0;min-width:160px">
        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Categoria</label>
        <select name="categoria" style="height:40px;border-radius:8px;padding:0 10px">
          <option value="">Todas</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>><?= h($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;min-width:150px">
        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Bairro</label>
        <input type="text" name="bairro" placeholder="Ex: Centro" value="<?= h($bairro) ?>"
               style="height:40px;border-radius:8px">
      </div>
      <div class="form-group" style="margin:0;min-width:140px">
        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Ordenar</label>
        <select name="ordem" style="height:40px;border-radius:8px;padding:0 10px">
          <option value="nota"    <?= $ordem==='nota'    ?'selected':'' ?>>Melhor avaliados</option>
          <option value="recente" <?= $ordem==='recente' ?'selected':'' ?>>Mais recentes</option>
          <option value="nome"    <?= $ordem==='nome'    ?'selected':'' ?>>Nome A-Z</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="height:40px;align-self:flex-end">
        <i class="fas fa-search"></i> Filtrar
      </button>
      <?php if ($q || $catId || $bairro): ?>
        <a href="buscar.php" class="btn btn-ghost" style="height:40px;align-self:flex-end">Limpar</a>
      <?php endif; ?>
    </form>

    <!-- Resultados -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <h2 style="font-size:1.2rem">
        <?php if ($q): ?>Resultados para "<strong><?= h($q) ?></strong>"<?php else: ?>Profissionais<?php endif; ?>
        <span style="color:var(--gray-400);font-size:14px;font-weight:400;margin-left:6px">(<?= $total ?> encontrado<?= $total != 1 ? 's' : '' ?>)</span>
      </h2>
    </div>

    <?php if ($results): ?>
      <div class="cards-grid">
        <?php foreach ($results as $p): ?>
          <?php include 'includes/card_prestador.php'; ?>
        <?php endforeach; ?>
      </div>

      <!-- Paginação -->
      <?php if ($pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>">
              <i class="fas fa-chevron-left"></i>
            </a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if ($i == $page): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($page < $pages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>">
              <i class="fas fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>Nenhum profissional encontrado</h3>
        <p>Tente buscar por outro termo ou remova os filtros</p>
        <a href="buscar.php" class="btn btn-primary mt-3">Ver todos</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
