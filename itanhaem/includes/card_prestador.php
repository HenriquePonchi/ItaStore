<?php
// includes/card_prestador.php — card reutilizável
// Espera a variável $p com dados do prestador
$isFav = false;
if (isLoggedCliente()) {
    $dbC = getDB();
    $stFav = $dbC->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND prestador_id = ?");
    $stFav->execute([$_SESSION['usuario_id'], $p['id']]);
    $isFav = (bool) $stFav->fetchColumn();
}
?>
<div class="prestador-card">
  <div class="card-img">
    <?php if (!empty($p['foto']) && file_exists(dirname(__DIR__) . '/' . $p['foto'])): ?>
      <img src="<?= SITE_URL ?>/<?= h($p['foto']) ?>" alt="<?= h($p['nome']) ?>">
    <?php else: ?>
      <div class="no-photo"><i class="fas fa-user-circle"></i></div>
    <?php endif; ?>
    <?php if (!empty($p['destaque'])): ?>
      <span class="badge-destaque"><i class="fas fa-star"></i> Destaque</span>
    <?php endif; ?>
    <?php if (isLoggedCliente()): ?>
      <button class="btn-favoritar <?= $isFav ? 'favorited' : '' ?>"
              data-id="<?= $p['id'] ?>"
              style="position:absolute;top:10px;right:10px;background:rgba(255,255,255,.9);
                     border:none;border-radius:50%;width:34px;height:34px;cursor:pointer;
                     display:grid;place-items:center;font-size:16px;color:<?= $isFav ? '#ef4444' : '#94a3b8' ?>">
        <i class="<?= $isFav ? 'fas' : 'far' ?> fa-heart"></i>
      </button>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if (!empty($p['cat_nome'])): ?>
      <span class="card-cat">
        <i class="fas <?= h($p['cat_icone'] ?? 'fa-briefcase') ?>"></i>
        <?= h($p['cat_nome']) ?>
      </span>
    <?php endif; ?>
    <h3><?= h($p['nome']) ?></h3>
    <?php if (!empty($p['bairro'])): ?>
      <p class="card-meta"><i class="fas fa-map-marker-alt"></i> <?= h($p['bairro']) ?>, Itanhaém</p>
    <?php endif; ?>
    <?php if ($p['total_avaliacoes'] > 0): ?>
      <div class="card-rating">
        <?= estrelas((float)$p['nota_media']) ?>
        <span><?= number_format($p['nota_media'], 1) ?> (<?= $p['total_avaliacoes'] ?> avaliações)</span>
      </div>
    <?php else: ?>
      <div class="card-rating"><span style="color:var(--gray-400);font-size:12px;">Sem avaliações ainda</span></div>
    <?php endif; ?>
    <?php if (!empty($p['descricao'])): ?>
      <p class="card-desc"><?= h($p['descricao']) ?></p>
    <?php endif; ?>
  </div>
  <div class="card-footer">
    <a href="<?= SITE_URL ?>/perfil-prestador.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Ver Perfil</a>
    <?php if (!empty($p['whatsapp'])): ?>
      <a href="<?= whatsappLink($p['whatsapp'], 'Olá! Vi seu perfil no ServiçosItanhaém e gostaria de um orçamento.') ?>"
         target="_blank" class="btn btn-whatsapp btn-sm">
        <i class="fab fa-whatsapp"></i> WhatsApp
      </a>
    <?php endif; ?>
  </div>
</div>
