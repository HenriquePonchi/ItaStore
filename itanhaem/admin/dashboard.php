<?php
// admin/dashboard.php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Painel Admin';

// Stats
$totalUsuarios   = $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo='cliente'")->fetchColumn();
$totalPrestadores= $db->query("SELECT COUNT(*) FROM prestadores")->fetchColumn();
$totalServicos   = $db->query("SELECT COUNT(*) FROM servicos")->fetchColumn();
$totalAvaliacoes = $db->query("SELECT COUNT(*) FROM avaliacoes")->fetchColumn();
$pendentes       = $db->query("SELECT COUNT(*) FROM prestadores WHERE aprovado=0")->fetchColumn();

// Ações
$ok = $erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Aprovar/reprovar prestador
    if ($acao === 'aprovar') {
        $id  = (int)$_POST['id'];
        $val = (int)$_POST['val'];
        $db->prepare("UPDATE prestadores SET aprovado=? WHERE id=?")->execute([$val,$id]);
        $ok = $val ? 'Prestador aprovado!' : 'Aprovação removida.';
    }
    if ($acao === 'destaque') {
        $id  = (int)$_POST['id'];
        $val = (int)$_POST['val'];
        $db->prepare("UPDATE prestadores SET destaque=? WHERE id=?")->execute([$val,$id]);
        $ok = $val ? 'Prestador em destaque!' : 'Removido dos destaques.';
    }
    if ($acao === 'bloquear_p') {
        $id  = (int)$_POST['id'];
        $val = (int)$_POST['val'];
        $db->prepare("UPDATE prestadores SET bloqueado=? WHERE id=?")->execute([$val,$id]);
        $ok = $val ? 'Prestador bloqueado.' : 'Prestador desbloqueado.';
    }
    if ($acao === 'del_prestador') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM prestadores WHERE id=?")->execute([$id]);
        $ok = 'Prestador excluído.';
    }
    if ($acao === 'bloquear_u') {
        $id  = (int)$_POST['id'];
        $val = (int)$_POST['val'];
        $db->prepare("UPDATE usuarios SET bloqueado=? WHERE id=? AND tipo='cliente'")->execute([$val,$id]);
        $ok = $val ? 'Usuário bloqueado.' : 'Usuário desbloqueado.';
    }
    if ($acao === 'del_usuario') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM usuarios WHERE id=? AND tipo='cliente'")->execute([$id]);
        $ok = 'Usuário excluído.';
    }
    if ($acao === 'add_cat') {
        $nome  = trim($_POST['nome_cat'] ?? '');
        $icone = trim($_POST['icone_cat'] ?? 'fa-briefcase');
        $desc  = trim($_POST['desc_cat'] ?? '');
        if ($nome) {
            $db->prepare("INSERT INTO categorias (nome,icone,descricao) VALUES (?,?,?)")->execute([$nome,$icone,$desc]);
            $ok = 'Categoria adicionada!';
        }
    }
    if ($acao === 'del_cat') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM categorias WHERE id=?")->execute([$id]);
        $ok = 'Categoria removida.';
    }
    if ($acao === 'del_avaliacao') {
        $id = (int)$_POST['id'];
        $av = $db->prepare("SELECT prestador_id FROM avaliacoes WHERE id=?"); $av->execute([$id]);
        $pid = $av->fetchColumn();
        $db->prepare("DELETE FROM avaliacoes WHERE id=?")->execute([$id]);
        // Recalcula
        if ($pid) {
            $db->prepare("UPDATE prestadores SET
                nota_media=(SELECT COALESCE(AVG(nota),0) FROM avaliacoes WHERE prestador_id=? AND aprovado=1),
                total_avaliacoes=(SELECT COUNT(*) FROM avaliacoes WHERE prestador_id=? AND aprovado=1)
                WHERE id=?")->execute([$pid,$pid,$pid]);
        }
        $ok = 'Avaliação removida.';
    }
}

$aba = $_GET['aba'] ?? 'inicio';

// Dados por aba
$prestadores = $usuarios = $categorias = $avaliacoes = [];
if ($aba === 'prestadores' || $aba === 'inicio') {
    $prestadores = $db->query("SELECT p.*,c.nome AS cat_nome FROM prestadores p LEFT JOIN categorias c ON c.id=p.categoria_id ORDER BY p.aprovado ASC, p.criado_em DESC")->fetchAll();
}
if ($aba === 'usuarios') {
    $usuarios = $db->query("SELECT * FROM usuarios WHERE tipo='cliente' ORDER BY criado_em DESC")->fetchAll();
}
if ($aba === 'categorias') {
    $categorias = $db->query("SELECT c.*,COUNT(p.id) AS total FROM categorias c LEFT JOIN prestadores p ON p.categoria_id=c.id GROUP BY c.id ORDER BY c.nome")->fetchAll();
}
if ($aba === 'avaliacoes') {
    $avaliacoes = $db->query("SELECT a.*,p.nome AS prestador_nome,u.nome AS usuario_nome FROM avaliacoes a JOIN prestadores p ON p.id=a.prestador_id JOIN usuarios u ON u.id=a.usuario_id ORDER BY a.criado_em DESC")->fetchAll();
}

include '../includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="dash-layout">
  <aside class="dash-sidebar">
    <div class="sidebar-logo">
      <i class="fas fa-shield-alt" style="color:var(--orange)"></i>
      <span>Admin</span>
    </div>
    <nav class="sidebar-menu">
      <a href="?aba=inicio"      class="<?=$aba==='inicio'     ?'active':''?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="?aba=prestadores" class="<?=$aba==='prestadores'?'active':''?>">
        <i class="fas fa-briefcase"></i> Prestadores
        <?php if ($pendentes): ?><span style="background:var(--orange);color:#fff;border-radius:50px;padding:1px 7px;font-size:11px;margin-left:4px"><?=$pendentes?></span><?php endif; ?>
      </a>
      <a href="?aba=usuarios"    class="<?=$aba==='usuarios'   ?'active':''?>"><i class="fas fa-users"></i> Clientes</a>
      <a href="?aba=categorias"  class="<?=$aba==='categorias' ?'active':''?>"><i class="fas fa-tags"></i> Categorias</a>
      <a href="?aba=avaliacoes"  class="<?=$aba==='avaliacoes' ?'active':''?>"><i class="fas fa-star"></i> Avaliações</a>
      <div class="sidebar-sep">Site</div>
      <a href="<?=SITE_URL?>/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Ver Site</a>
      <a href="<?=SITE_URL?>/includes/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-header">
      <h1>Painel Administrativo</h1>
      <p>Controle total do ServiçosItanhaém</p>
    </div>

    <?php if ($ok):  ?><div class="alert alert-success" data-autodismiss><i class="fas fa-check-circle"></i> <?=h($ok)?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?=h($erro)?></div><?php endif; ?>

    <!-- ── INÍCIO ── -->
    <?php if ($aba === 'inicio'): ?>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-users"></i></div><div><div class="stat-num"><?=$totalUsuarios?></div><div class="stat-label">Clientes</div></div></div>
        <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-briefcase"></i></div><div><div class="stat-num"><?=$totalPrestadores?></div><div class="stat-label">Prestadores</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-list"></i></div><div><div class="stat-num"><?=$totalServicos?></div><div class="stat-label">Serviços</div></div></div>
        <div class="stat-card"><div class="stat-icon gray"><i class="fas fa-star"></i></div><div><div class="stat-num"><?=$totalAvaliacoes?></div><div class="stat-label">Avaliações</div></div></div>
      </div>

      <?php if ($pendentes): ?>
        <div class="alert alert-warning"><i class="fas fa-clock"></i> <strong><?=$pendentes?></strong> prestador(es) aguardando aprovação. <a href="?aba=prestadores">Ver agora</a></div>
      <?php endif; ?>

      <div class="table-card">
        <div class="table-card-header"><h3>Últimos Prestadores</h3><a href="?aba=prestadores" class="btn btn-sm btn-outline">Ver todos</a></div>
        <table>
          <thead><tr><th>Nome</th><th>Categoria</th><th>Bairro</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php foreach (array_slice($prestadores,0,8) as $p): ?>
              <tr>
                <td><?=h($p['nome'])?></td>
                <td><?=h($p['cat_nome']??'—')?></td>
                <td><?=h($p['bairro']??'—')?></td>
                <td>
                  <?php if ($p['bloqueado']): ?>
                    <span class="pill pill-red">Bloqueado</span>
                  <?php elseif ($p['aprovado']): ?>
                    <span class="pill pill-green">Aprovado</span>
                  <?php else: ?>
                    <span class="pill pill-yellow">Pendente</span>
                  <?php endif; ?>
                  <?php if ($p['destaque']): ?><span class="pill pill-orange">★ Destaque</span><?php endif; ?>
                </td>
                <td><a href="?aba=prestadores" class="btn btn-ghost btn-sm">Gerenciar</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <!-- ── PRESTADORES ── -->
    <?php elseif ($aba === 'prestadores'): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <h2>Gerenciar Prestadores</h2>
        <span style="color:var(--gray-600);font-size:14px"><?=count($prestadores)?> cadastrado(s)</span>
      </div>
      <div class="table-card" style="overflow-x:auto">
        <table>
          <thead><tr><th>Nome</th><th>E-mail</th><th>Categoria</th><th>Nota</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php foreach ($prestadores as $p): ?>
              <tr>
                <td>
                  <div style="font-weight:600"><?=h($p['nome'])?></div>
                  <div style="font-size:12px;color:var(--gray-400)"><?=h($p['bairro']??'')?></div>
                </td>
                <td style="font-size:13px"><?=h($p['email'])?></td>
                <td style="font-size:13px"><?=h($p['cat_nome']??'—')?></td>
                <td>
                  <?php if ($p['total_avaliacoes']>0): ?>
                    <span style="color:#f59e0b">★</span> <?=number_format($p['nota_media'],1)?> <span style="font-size:11px;color:var(--gray-400)">(<?=$p['total_avaliacoes']?>)</span>
                  <?php else: echo '—'; endif; ?>
                </td>
                <td>
                  <?php if ($p['bloqueado']): ?><span class="pill pill-red">Bloqueado</span>
                  <?php elseif ($p['aprovado']): ?><span class="pill pill-green">Aprovado</span>
                  <?php else: ?><span class="pill pill-yellow">Pendente</span><?php endif; ?>
                  <?php if ($p['destaque']): ?><span class="pill pill-orange">★</span><?php endif; ?>
                </td>
                <td>
                  <div class="td-actions" style="flex-wrap:wrap">
                    <!-- Aprovar/Reprovar -->
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="acao" value="aprovar">
                      <input type="hidden" name="id" value="<?=$p['id']?>">
                      <input type="hidden" name="val" value="<?=$p['aprovado']?0:1?>">
                      <button type="submit" class="btn btn-sm <?=$p['aprovado']?'btn-ghost':'btn-primary'?>" title="<?=$p['aprovado']?'Reprovar':'Aprovar'?>">
                        <i class="fas fa-<?=$p['aprovado']?'times':'check'?>"></i>
                      </button>
                    </form>
                    <!-- Destaque -->
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="acao" value="destaque">
                      <input type="hidden" name="id" value="<?=$p['id']?>">
                      <input type="hidden" name="val" value="<?=$p['destaque']?0:1?>">
                      <button type="submit" class="btn btn-sm <?=$p['destaque']?'btn-orange':'btn-ghost'?>" title="<?=$p['destaque']?'Tirar destaque':'Destacar'?>">
                        <i class="fas fa-star"></i>
                      </button>
                    </form>
                    <!-- Bloquear -->
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="acao" value="bloquear_p">
                      <input type="hidden" name="id" value="<?=$p['id']?>">
                      <input type="hidden" name="val" value="<?=$p['bloqueado']?0:1?>">
                      <button type="submit" class="btn btn-sm btn-ghost" title="<?=$p['bloqueado']?'Desbloquear':'Bloquear'?>">
                        <i class="fas fa-<?=$p['bloqueado']?'lock-open':'ban'?>"></i>
                      </button>
                    </form>
                    <!-- Ver -->
                    <a href="<?=SITE_URL?>/perfil-prestador.php?id=<?=$p['id']?>" target="_blank" class="btn btn-sm btn-ghost" title="Ver perfil"><i class="fas fa-eye"></i></a>
                    <!-- Excluir -->
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="acao" value="del_prestador">
                      <input type="hidden" name="id" value="<?=$p['id']?>">
                      <button type="submit" class="btn btn-sm btn-danger" data-confirm="Excluir este prestador permanentemente?" title="Excluir">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <!-- ── USUÁRIOS ── -->
    <?php elseif ($aba === 'usuarios'): ?>
      <h2 style="margin-bottom:20px">Gerenciar Clientes</h2>
      <div class="table-card" style="overflow-x:auto">
        <table>
          <thead><tr><th>Nome</th><th>E-mail</th><th>Bairro</th><th>Cadastro</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
              <tr>
                <td style="font-weight:600"><?=h($u['nome'])?></td>
                <td style="font-size:13px"><?=h($u['email'])?></td>
                <td style="font-size:13px"><?=h($u['bairro']??'—')?></td>
                <td style="font-size:12px;color:var(--gray-400)"><?=date('d/m/Y',strtotime($u['criado_em']))?></td>
                <td><?=$u['bloqueado']?'<span class="pill pill-red">Bloqueado</span>':'<span class="pill pill-green">Ativo</span>'?></td>
                <td>
                  <div class="td-actions">
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="acao" value="bloquear_u">
                      <input type="hidden" name="id" value="<?=$u['id']?>">
                      <input type="hidden" name="val" value="<?=$u['bloqueado']?0:1?>">
                      <button type="submit" class="btn btn-sm btn-ghost" title="<?=$u['bloqueado']?'Desbloquear':'Bloquear'?>"><i class="fas fa-<?=$u['bloqueado']?'lock-open':'ban'?>"></i></button>
                    </form>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="acao" value="del_usuario">
                      <input type="hidden" name="id" value="<?=$u['id']?>">
                      <button type="submit" class="btn btn-sm btn-danger" data-confirm="Excluir este usuário?"><i class="fas fa-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <!-- ── CATEGORIAS ── -->
    <?php elseif ($aba === 'categorias'): ?>
      <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
        <div>
          <h2 style="margin-bottom:16px">Categorias de Serviços</h2>
          <div class="table-card">
            <table>
              <thead><tr><th>Ícone</th><th>Nome</th><th>Prestadores</th><th>Ações</th></tr></thead>
              <tbody>
                <?php foreach ($categorias as $c): ?>
                  <tr>
                    <td><i class="fas <?=h($c['icone'])?>" style="color:var(--blue);font-size:18px"></i></td>
                    <td><strong><?=h($c['nome'])?></strong><br><span style="font-size:12px;color:var(--gray-400)"><?=h($c['descricao']??'')?></span></td>
                    <td><?=$c['total']?></td>
                    <td>
                      <form method="POST" style="display:inline">
                        <input type="hidden" name="acao" value="del_cat">
                        <input type="hidden" name="id" value="<?=$c['id']?>">
                        <button type="submit" class="btn btn-sm btn-danger" data-confirm="Excluir esta categoria?"><i class="fas fa-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:22px;position:sticky;top:90px">
          <h3 style="margin-bottom:16px">Nova Categoria</h3>
          <form method="POST">
            <input type="hidden" name="acao" value="add_cat">
            <div class="form-group"><label>Nome *</label><input type="text" name="nome_cat" required placeholder="Ex: Pintor"></div>
            <div class="form-group"><label>Ícone Font Awesome</label><input type="text" name="icone_cat" placeholder="fa-paint-roller" value="fa-briefcase"></div>
            <div class="form-group"><label>Descrição</label><input type="text" name="desc_cat" placeholder="Breve descrição"></div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Adicionar</button>
          </form>
          <p class="form-hint mt-2"><a href="https://fontawesome.com/icons" target="_blank">Ver ícones disponíveis →</a></p>
        </div>
      </div>

    <!-- ── AVALIAÇÕES ── -->
    <?php elseif ($aba === 'avaliacoes'): ?>
      <h2 style="margin-bottom:20px">Gerenciar Avaliações</h2>
      <div class="table-card" style="overflow-x:auto">
        <table>
          <thead><tr><th>Cliente</th><th>Prestador</th><th>Nota</th><th>Comentário</th><th>Data</th><th>Ações</th></tr></thead>
          <tbody>
            <?php foreach ($avaliacoes as $av): ?>
              <tr>
                <td style="font-size:13px"><?=h($av['usuario_nome'])?></td>
                <td style="font-size:13px"><a href="<?=SITE_URL?>/perfil-prestador.php?id=<?=$av['prestador_id']?>"><?=h($av['prestador_nome'])?></a></td>
                <td><?=estrelas((float)$av['nota'])?></td>
                <td style="font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=h($av['comentario']??'—')?></td>
                <td style="font-size:12px;color:var(--gray-400)"><?=date('d/m/Y',strtotime($av['criado_em']))?></td>
                <td>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="acao" value="del_avaliacao">
                    <input type="hidden" name="id" value="<?=$av['id']?>">
                    <button type="submit" class="btn btn-sm btn-danger" data-confirm="Excluir esta avaliação?"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php include '../includes/footer.php'; ?>
