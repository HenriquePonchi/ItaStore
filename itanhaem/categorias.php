<?php
// categorias.php
require_once 'includes/auth.php';
$db = getDB();
$pageTitle = 'Categorias';

$cats = $db->query("
    SELECT c.*, COUNT(p.id) AS total
    FROM categorias c
    LEFT JOIN prestadores p ON p.categoria_id = c.id AND p.aprovado = 1 AND p.bloqueado = 0
    WHERE c.ativo = 1
    GROUP BY c.id
    ORDER BY total DESC, c.nome
")->fetchAll();

include 'includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Explore</span>
      <h1>Categorias de Serviços</h1>
      <p>Encontre o profissional ideal para cada tipo de serviço em Itanhaém</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px">
      <?php foreach ($cats as $c): ?>
        <a href="buscar.php?categoria=<?= $c['id'] ?>"
           style="text-decoration:none;background:#fff;border:2px solid var(--gray-200);
                  border-radius:var(--radius-lg);padding:28px 20px;text-align:center;
                  display:flex;flex-direction:column;align-items:center;gap:12px;
                  transition:.2s;color:var(--gray-800);"
           onmouseover="this.style.borderColor='var(--blue)';this.style.transform='translateY(-3px)';this.style.color='var(--blue)'"
           onmouseout="this.style.borderColor='var(--gray-200)';this.style.transform='';this.style.color='var(--gray-800)'">
          <i class="fas <?= h($c['icone']) ?>" style="font-size:36px;color:var(--blue)"></i>
          <strong style="font-size:15px;font-family:var(--font-head)"><?= h($c['nome']) ?></strong>
          <span style="font-size:13px;color:var(--gray-400)">
            <?= $c['total'] ?> profissional<?= $c['total'] != 1 ? 'is' : '' ?>
          </span>
          <?php if ($c['descricao']): ?>
            <p style="font-size:12px;color:var(--gray-600);line-height:1.5;margin:0"><?= h($c['descricao']) ?></p>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
