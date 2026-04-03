<?php
// prestador/dashboard.php
require_once '../includes/auth.php';
requirePrestador();
$db = getDB();
$pageTitle = 'Painel do Prestador';
$pid = $_SESSION['prestador_id'];

$prestador = $db->prepare("SELECT p.*, c.nome AS cat_nome FROM prestadores p LEFT JOIN categorias c ON c.id=p.categoria_id WHERE p.id=?");
$prestador->execute([$pid]);
$prestador = $prestador->fetch();

$servicos = $db->prepare("SELECT * FROM servicos WHERE prestador_id=? ORDER BY criado_em DESC");
$servicos->execute([$pid]);
$servicos = $servicos->fetchAll();

$portfolio = $db->prepare("SELECT * FROM portfolio WHERE prestador_id=? ORDER BY criado_em DESC");
$portfolio->execute([$pid]);
$portfolio = $portfolio->fetchAll();

$avs = $db->prepare("SELECT a.*, u.nome AS cliente_nome FROM avaliacoes a LEFT JOIN usuarios u ON u.id=a.usuario_id WHERE a.prestador_id=? ORDER BY a.criado_em DESC");
$avs->execute([$pid]);
$avs = $avs->fetchAll();

$msgs = $db->prepare("
    SELECT m.*, u.nome AS cliente_nome
    FROM mensagens m
    LEFT JOIN usuarios u ON u.id=m.de_usuario_id
    WHERE m.para_prestador_id=?
    ORDER BY m.criado_em DESC LIMIT 20
");
$msgs->execute([$pid]);
$msgs = $msgs->fetchAll();

$cats = $db->query("SELECT * FROM categorias WHERE ativo=1 ORDER BY nome")->fetchAll();

$ok = $erro = '';

// ── Ações POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Atualizar perfil
    if ($acao === 'perfil') {
        $nome  = trim($_POST['nome'] ?? '');
        $tel   = trim($_POST['telefone'] ?? '');
        $wpp   = trim($_POST['whatsapp'] ?? '');
        $bairro= trim($_POST['bairro'] ?? '');
        $desc  = trim($_POST['descricao'] ?? '');
        $cat   = (int)($_POST['categoria_id'] ?? 0);
        $foto  = $prestador['foto'];
        if (!empty($_FILES['foto']['name'])) {
            $novaFoto = uploadImagem($_FILES['foto'], 'perfil');
            if ($novaFoto) $foto = $novaFoto;
        }
        $db->prepare("UPDATE prestadores SET nome=?,telefone=?,whatsapp=?,bairro=?,descricao=?,categoria_id=?,foto=? WHERE id=?")
           ->execute([$nome,$tel,$wpp,$bairro,$desc,$cat,$foto,$pid]);
        $ok = 'Perfil atualizado com sucesso!';
        $prestador['nome']=$nome; $prestador['telefone']=$tel;
        $prestador['whatsapp']=$wpp; $prestador['bairro']=$bairro;
        $prestador['descricao']=$desc; $prestador['categoria_id']=$cat; $prestador['foto']=$foto;
        $_SESSION['prestador_nome'] = $nome;
    }

    // Adicionar serviço
    if ($acao === 'add_servico') {
        $titulo = trim($_POST['titulo'] ?? '');
        $desc   = trim($_POST['descricao_s'] ?? '');
        $preco  = trim($_POST['preco'] ?? '');
        if ($titulo) {
            $db->prepare("INSERT INTO servicos (prestador_id,titulo,descricao,preco) VALUES (?,?,?,?)")
               ->execute([$pid,$titulo,$desc,$preco]);
            $ok = 'Serviço adicionado!';
            $servicos[] = ['titulo'=>$titulo,'descricao'=>$desc,'preco'=>$preco,'id'=>$db->lastInsertId()];
        } else { $erro = 'Título obrigatório.'; }
    }

    // Remover serviço
    if ($acao === 'del_servico') {
        $sid = (int)($_POST['servico_id'] ?? 0);
        $db->prepare("DELETE FROM servicos WHERE id=? AND prestador_id=?")->execute([$sid,$pid]);
        $ok = 'Serviço removido.';
        $servicos = array_filter($servicos, fn($s) => $s['id'] != $sid);
    }

    // Upload portfólio
    if ($acao === 'portfolio' && !empty($_FILES['imagens']['name'][0])) {
        $count = 0;
        foreach ($_FILES['imagens']['tmp_name'] as $i => $tmp) {
            $file = [
                'name'     => $_FILES['imagens']['name'][$i],
                'type'     => $_FILES['imagens']['type'][$i],
                'tmp_name' => $tmp,
                'error'    => $_FILES['imagens']['error'][$i],
                'size'     => $_FILES['imagens']['size'][$i],
            ];
            $path = uploadImagem($file, 'portfolio');
            if ($path) {
                $leg = trim($_POST['legenda'][$i] ?? '');
                $db->prepare("INSERT INTO portfolio (prestador_id,imagem,legenda) VALUES (?,?,?)")
                   ->execute([$pid,$path,$leg]);
                $count++;
            }
        }
        $ok = "$count imagem(ns) adicionada(s) ao portfólio!";
        $portfolio = $db->prepare("SELECT * FROM portfolio WHERE prestador_id=? ORDER BY criado_em DESC");
        $portfolio->execute([$pid]);
        $portfolio = $portfolio->fetchAll();
    }

    // Remover do portfólio
    if ($acao === 'del_portfolio') {
        $imgId = (int)($_POST['img_id'] ?? 0);
        $img = $db->prepare("SELECT imagem FROM portfolio WHERE id=? AND prestador_id=?");
        $img->execute([$imgId,$pid]);
        $img = $img->fetchColumn();
        if ($img && file_exists(dirname(__DIR__).'/'.$img)) unlink(dirname(__DIR__).'/'.$img);
        $db->prepare("DELETE FROM portfolio WHERE id=? AND prestador_id=?")->execute([$imgId,$pid]);
        $ok = 'Imagem removida.';
        $portfolio = array_filter($portfolio, fn($p) => $p['id'] != $imgId);
    }

    // Responder mensagem
    if ($acao === 'responder') {
        $paraUid = (int)($_POST['para_usuario_id'] ?? 0);
        $msg     = trim($_POST['mensagem'] ?? '');
        if ($msg && $paraUid) {
            $db->prepare("INSERT INTO mensagens (de_prestador_id,para_usuario_id,mensagem) VALUES (?,?,?)")
               ->execute([$pid,$paraUid,$msg]);
            $ok = 'Mensagem enviada!';
        }
    }
}

$aba = $_GET['aba'] ?? 'inicio';
include '../includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="dash-layout">
  <!-- Sidebar -->
  <aside class="dash-sidebar">
    <div class="sidebar-logo">
      <i class="fas fa-briefcase"></i>
      <span>Meu Painel</span>
    </div>
    <nav class="sidebar-menu">
      <a href="?aba=inicio"     class="<?=$aba==='inicio'    ?'active':''?>"><i class="fas fa-home"></i> Início</a>
      <a href="?aba=perfil"     class="<?=$aba==='perfil'    ?'active':''?>"><i class="fas fa-user-edit"></i> Meu Perfil</a>
      <a href="?aba=servicos"   class="<?=$aba==='servicos'  ?'active':''?>"><i class="fas fa-list"></i> Meus Serviços</a>
      <a href="?aba=portfolio"  class="<?=$aba==='portfolio' ?'active':''?>"><i class="fas fa-images"></i> Portfólio</a>
      <a href="?aba=avaliacoes" class="<?=$aba==='avaliacoes'?'active':''?>"><i class="fas fa-star"></i> Avaliações</a>
      <a href="?aba=mensagens"  class="<?=$aba==='mensagens' ?'active':''?>"><i class="fas fa-comments"></i> Mensagens</a>
      <div class="sidebar-sep">Ações</div>
      <a href="<?=SITE_URL?>/perfil-prestador.php?id=<?=$pid?>" target="_blank"><i class="fas fa-eye"></i> Ver Meu Perfil</a>
      <a href="<?=SITE_URL?>/includes/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="dash-main">
    <div class="dash-header">
      <h1>Olá, <?=h($prestador['nome'])?>! 👋</h1>
      <p style="display:flex;align-items:center;gap:8px">
        <?php if ($prestador['aprovado']): ?>
          <span class="pill pill-green"><i class="fas fa-check-circle"></i> Perfil aprovado</span>
        <?php else: ?>
          <span class="pill pill-yellow"><i class="fas fa-clock"></i> Aguardando aprovação</span>
        <?php endif; ?>
        <?php if ($prestador['destaque']): ?>
          <span class="pill pill-orange"><i class="fas fa-star"></i> Em destaque</span>
        <?php endif; ?>
      </p>
    </div>

    <?php if ($ok):  ?><div class="alert alert-success" data-autodismiss><i class="fas fa-check-circle"></i> <?=h($ok)?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?=h($erro)?></div><?php endif; ?>

    <?php if (!$prestador['aprovado']): ?>
      <div class="alert alert-warning"><i class="fas fa-info-circle"></i> Seu cadastro está em análise. Você receberá acesso completo após a aprovação do administrador.</div>
    <?php endif; ?>

    <!-- ── INÍCIO ── -->
    <?php if ($aba === 'inicio'): ?>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-list"></i></div>
          <div><div class="stat-num"><?=count($servicos)?></div><div class="stat-label">Serviços</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-images"></i></div>
          <div><div class="stat-num"><?=count($portfolio)?></div><div class="stat-label">Fotos no portfólio</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-star"></i></div>
          <div><div class="stat-num"><?=number_format($prestador['nota_media'],1)?></div><div class="stat-label">Nota média</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon gray"><i class="fas fa-comments"></i></div>
          <div><div class="stat-num"><?=$prestador['total_avaliacoes']?></div><div class="stat-label">Avaliações</div></div>
        </div>
      </div>
      <div class="table-card">
        <div class="table-card-header"><h3>Acesso Rápido</h3></div>
        <div style="padding:20px;display:flex;flex-wrap:wrap;gap:12px">
          <a href="?aba=perfil"    class="btn btn-primary"><i class="fas fa-user-edit"></i> Editar Perfil</a>
          <a href="?aba=servicos"  class="btn btn-outline"><i class="fas fa-plus"></i> Adicionar Serviço</a>
          <a href="?aba=portfolio" class="btn btn-ghost"><i class="fas fa-upload"></i> Upload Portfólio</a>
          <a href="<?=SITE_URL?>/perfil-prestador.php?id=<?=$pid?>" target="_blank" class="btn btn-ghost"><i class="fas fa-eye"></i> Ver Perfil Público</a>
        </div>
      </div>

    <!-- ── PERFIL ── -->
    <?php elseif ($aba === 'perfil'): ?>
      <h2 style="margin-bottom:20px">Editar Perfil</h2>
      <div style="background:#fff;border-radius:var(--radius-lg);padding:28px;border:1px solid var(--gray-200);max-width:600px">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="acao" value="perfil">
          <div style="display:flex;align-items:center;gap:18px;margin-bottom:24px">
            <?php if (!empty($prestador['foto'])): ?>
              <img id="fotoPreview" src="<?=SITE_URL?>/<?=h($prestador['foto'])?>" class="foto-preview">
            <?php else: ?>
              <img id="fotoPreview" src="" class="foto-preview" style="display:none">
              <div style="width:80px;height:80px;border-radius:50%;background:var(--gray-100);display:grid;place-items:center;font-size:32px;color:var(--gray-400)"><i class="fas fa-user"></i></div>
            <?php endif; ?>
            <div>
              <label style="cursor:pointer;font-size:13px;font-weight:600;color:var(--blue)">
                <i class="fas fa-camera"></i> Alterar foto
                <input type="file" name="foto" accept="image/*" style="display:none" data-preview="fotoPreview">
              </label>
              <p class="form-hint">JPG, PNG ou WEBP. Máx 5MB</p>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Nome *</label>
              <input type="text" name="nome" required value="<?=h($prestador['nome'])?>">
            </div>
            <div class="form-group">
              <label>Categoria</label>
              <select name="categoria_id">
                <option value="">Selecione...</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?=$c['id']?>" <?=$prestador['categoria_id']==$c['id']?'selected':''?>><?=h($c['nome'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Telefone</label>
              <input type="text" name="telefone" value="<?=h($prestador['telefone']??'')?>">
            </div>
            <div class="form-group">
              <label>WhatsApp</label>
              <input type="text" name="whatsapp" placeholder="5513999999999" value="<?=h($prestador['whatsapp']??'')?>">
            </div>
          </div>
          <div class="form-group">
            <label>Bairro de Atuação</label>
            <input type="text" name="bairro" value="<?=h($prestador['bairro']??'')?>">
          </div>
          <div class="form-group">
            <label>Descrição Profissional</label>
            <textarea name="descricao" style="min-height:120px"><?=h($prestador['descricao']??'')?></textarea>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
        </form>
      </div>

    <!-- ── SERVIÇOS ── -->
    <?php elseif ($aba === 'servicos'): ?>
      <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">
        <div>
          <h2 style="margin-bottom:16px">Meus Serviços</h2>
          <?php if ($servicos): ?>
            <?php foreach ($servicos as $s): ?>
              <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:16px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:start">
                <div>
                  <div style="font-weight:700;margin-bottom:4px"><?=h($s['titulo'])?></div>
                  <?php if ($s['descricao']): ?><div style="font-size:13px;color:var(--gray-600);margin-bottom:4px"><?=h($s['descricao'])?></div><?php endif; ?>
                  <?php if ($s['preco']): ?><div style="color:var(--green);font-weight:700;font-size:13px"><?=h($s['preco'])?></div><?php endif; ?>
                </div>
                <form method="POST">
                  <input type="hidden" name="acao" value="del_servico">
                  <input type="hidden" name="servico_id" value="<?=$s['id']?>">
                  <button type="submit" class="btn btn-danger btn-sm" data-confirm="Remover este serviço?"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-state"><i class="fas fa-list"></i><h3>Nenhum serviço cadastrado</h3></div>
          <?php endif; ?>
        </div>
        <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:22px;position:sticky;top:90px">
          <h3 style="margin-bottom:16px">Adicionar Serviço</h3>
          <form method="POST">
            <input type="hidden" name="acao" value="add_servico">
            <div class="form-group"><label>Título *</label><input type="text" name="titulo" required placeholder="Ex: Instalação Elétrica"></div>
            <div class="form-group"><label>Descrição</label><textarea name="descricao_s" style="min-height:80px" placeholder="Descreva o serviço..."></textarea></div>
            <div class="form-group"><label>Preço (opcional)</label><input type="text" name="preco" placeholder="Ex: A partir de R$ 80"></div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Adicionar</button>
          </form>
        </div>
      </div>

    <!-- ── PORTFÓLIO ── -->
    <?php elseif ($aba === 'portfolio'): ?>
      <h2 style="margin-bottom:20px">Portfólio</h2>
      <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:22px;margin-bottom:24px">
        <h3 style="margin-bottom:14px">Adicionar Imagens</h3>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="acao" value="portfolio">
          <div class="form-group">
            <label>Selecionar imagens (múltiplas permitidas)</label>
            <input type="file" name="imagens[]" accept="image/*" multiple>
            <p class="form-hint">JPG, PNG ou WEBP. Máx 5MB cada.</p>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Enviar</button>
        </form>
      </div>
      <?php if ($portfolio): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px">
          <?php foreach ($portfolio as $img): ?>
            <div style="position:relative;border-radius:var(--radius);overflow:hidden;border:1px solid var(--gray-200)">
              <img src="<?=SITE_URL?>/<?=h($img['imagem'])?>" style="width:100%;height:160px;object-fit:cover">
              <div style="padding:8px 10px;background:#fff">
                <div style="font-size:12px;color:var(--gray-600);margin-bottom:6px"><?=h($img['legenda']??'')?></div>
                <form method="POST">
                  <input type="hidden" name="acao" value="del_portfolio">
                  <input type="hidden" name="img_id" value="<?=$img['id']?>">
                  <button type="submit" class="btn btn-danger btn-sm" style="width:100%" data-confirm="Remover esta imagem?"><i class="fas fa-trash"></i> Remover</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state"><i class="fas fa-images"></i><h3>Nenhuma imagem no portfólio</h3></div>
      <?php endif; ?>

    <!-- ── AVALIAÇÕES ── -->
    <?php elseif ($aba === 'avaliacoes'): ?>
      <h2 style="margin-bottom:20px">Avaliações Recebidas</h2>
      <?php if ($avs): ?>
        <?php foreach ($avs as $av): ?>
          <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:16px;margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
              <span style="font-weight:600"><i class="fas fa-user-circle" style="color:var(--blue)"></i> <?=h($av['cliente_nome']??'Cliente')?></span>
              <span style="font-size:12px;color:var(--gray-400)"><?=date('d/m/Y',strtotime($av['criado_em']))?></span>
            </div>
            <div style="margin-bottom:6px"><?=estrelas((float)$av['nota'])?></div>
            <?php if ($av['comentario']): ?><p style="font-size:14px;color:var(--gray-600)"><?=h($av['comentario'])?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state"><i class="fas fa-star"></i><h3>Nenhuma avaliação ainda</h3></div>
      <?php endif; ?>

    <!-- ── MENSAGENS ── -->
    <?php elseif ($aba === 'mensagens'): ?>
      <h2 style="margin-bottom:20px">Mensagens Recebidas</h2>
      <?php if ($msgs): ?>
        <?php foreach ($msgs as $m): ?>
          <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:16px;margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
              <span style="font-weight:600"><i class="fas fa-user-circle" style="color:var(--blue)"></i> <?=h($m['cliente_nome']??'Cliente')?></span>
              <span style="font-size:12px;color:var(--gray-400)"><?=date('d/m H:i',strtotime($m['criado_em']))?></span>
            </div>
            <p style="font-size:14px;margin-bottom:12px"><?=h($m['mensagem'])?></p>
            <form method="POST" style="display:flex;gap:8px">
              <input type="hidden" name="acao" value="responder">
              <input type="hidden" name="para_usuario_id" value="<?=$m['de_usuario_id']?>">
              <input type="text" name="mensagem" placeholder="Responder..." style="flex:1;padding:8px 12px;border:2px solid var(--gray-200);border-radius:var(--radius);font-family:var(--font);font-size:13px">
              <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i></button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state"><i class="fas fa-comments"></i><h3>Nenhuma mensagem ainda</h3></div>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</div>

<?php include '../includes/footer.php'; ?>
