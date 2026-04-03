<?php
// cliente/chat.php
require_once '../includes/auth.php';
requireCliente();
$db = getDB();
$pageTitle = 'Mensagens';
$uid = $_SESSION['usuario_id'];

$prestadorId = (int)($_GET['prestador'] ?? 0);

// POST: enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    header('Content-Type: application/json');
    $msg = trim($_POST['mensagem'] ?? '');
    $pid = (int)($_POST['prestador_id'] ?? 0);
    if ($msg && $pid) {
        $db->prepare("INSERT INTO mensagens (de_usuario_id,para_prestador_id,mensagem) VALUES (?,?,?)")
           ->execute([$uid,$pid,$msg]);
        // Marca lida do prestador
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// Conversas do cliente
$conversas = $db->prepare("
    SELECT p.id, p.nome, p.foto,
           (SELECT mensagem FROM mensagens m2
            WHERE (m2.de_usuario_id=? AND m2.para_prestador_id=p.id)
               OR (m2.de_prestador_id=p.id AND m2.para_usuario_id=?)
            ORDER BY m2.criado_em DESC LIMIT 1) AS ultima,
           (SELECT criado_em FROM mensagens m3
            WHERE (m3.de_usuario_id=? AND m3.para_prestador_id=p.id)
               OR (m3.de_prestador_id=p.id AND m3.para_usuario_id=?)
            ORDER BY m3.criado_em DESC LIMIT 1) AS ultima_data
    FROM prestadores p
    WHERE p.id IN (
        SELECT para_prestador_id FROM mensagens WHERE de_usuario_id=?
        UNION
        SELECT de_prestador_id FROM mensagens WHERE para_usuario_id=?
    )
    ORDER BY ultima_data DESC
");
$conversas->execute([$uid,$uid,$uid,$uid,$uid,$uid]);
$conversas = $conversas->fetchAll();

// Mensagens da conversa selecionada
$mensagens = [];
$prestador = null;
if ($prestadorId) {
    $stP = $db->prepare("SELECT id,nome,foto,whatsapp FROM prestadores WHERE id=?");
    $stP->execute([$prestadorId]);
    $prestador = $stP->fetch();

    $stM = $db->prepare("
        SELECT *, 'out' AS dir FROM mensagens
        WHERE de_usuario_id=? AND para_prestador_id=?
        UNION ALL
        SELECT *, 'in' AS dir FROM mensagens
        WHERE de_prestador_id=? AND para_usuario_id=?
        ORDER BY criado_em ASC
    ");
    $stM->execute([$uid,$prestadorId,$prestadorId,$uid]);
    $mensagens = $stM->fetchAll();

    // Marca como lida
    $db->prepare("UPDATE mensagens SET lida=1 WHERE de_prestador_id=? AND para_usuario_id=?")
       ->execute([$prestadorId,$uid]);
}

// Se não está no dashboard embutido, include header
if (!defined('CHAT_EMBEDDED')):
include '../includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>
<div style="padding:20px 0">
<div class="container">
<h2 style="margin-bottom:20px">Mensagens</h2>
<?php endif; ?>

<div class="chat-layout" style="border:1px solid var(--gray-200);border-radius:var(--radius-lg);overflow:hidden;background:#fff;">
  <!-- Lista de conversas -->
  <div class="chat-list">
    <div style="padding:14px 18px;border-bottom:1px solid var(--gray-100);font-weight:700;font-size:14px">
      Conversas
    </div>
    <?php if ($conversas): ?>
      <?php foreach ($conversas as $c): ?>
        <a href="chat.php?prestador=<?= $c['id'] ?>"
           class="chat-item <?= $prestadorId === (int)$c['id'] ? 'active' : '' ?>"
           style="text-decoration:none;display:flex;gap:12px;padding:14px 18px;
                  border-bottom:1px solid var(--gray-100);
                  background:<?= $prestadorId === (int)$c['id'] ? 'var(--blue-light)' : '' ?>">
          <div class="chat-avatar">
            <?php if ($c['foto']): ?><img src="<?= SITE_URL ?>/<?= h($c['foto']) ?>"><?php else: ?><i class="fas fa-user"></i><?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div class="chat-name"><?= h($c['nome']) ?></div>
            <div class="chat-preview"><?= h(substr($c['ultima'] ?? '', 0, 40)) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div style="padding:20px;text-align:center;color:var(--gray-400);font-size:13px">
        Nenhuma conversa ainda.<br>
        <a href="<?= SITE_URL ?>/buscar.php" style="color:var(--blue)">Buscar profissionais</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Janela de chat -->
  <div class="chat-window">
    <?php if ($prestador): ?>
      <div class="chat-header">
        <div class="chat-avatar">
          <?php if ($prestador['foto']): ?><img src="<?= SITE_URL ?>/<?= h($prestador['foto']) ?>"><?php else: ?><i class="fas fa-user"></i><?php endif; ?>
        </div>
        <div>
          <div style="font-weight:700"><?= h($prestador['nome']) ?></div>
          <div style="font-size:12px;color:var(--gray-400)">Prestador de Serviço</div>
        </div>
        <?php if (!empty($prestador['whatsapp'])): ?>
          <a href="<?= whatsappLink($prestador['whatsapp']) ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="margin-left:auto">
            <i class="fab fa-whatsapp"></i> WhatsApp
          </a>
        <?php endif; ?>
      </div>

      <div class="messages-area" id="messagesArea">
        <?php foreach ($mensagens as $m): ?>
          <div class="message-bubble <?= $m['dir'] === 'out' ? 'message-out' : 'message-in' ?>">
            <div><?= h($m['mensagem']) ?></div>
            <div class="message-time"><?= date('d/m H:i', strtotime($m['criado_em'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <form class="chat-input" id="chatForm" action="chat.php" method="POST">
        <input type="hidden" name="prestador_id" value="<?= $prestadorId ?>">
        <input type="text" id="chatInput" name="mensagem" placeholder="Digite sua mensagem..." autocomplete="off">
        <button type="submit"><i class="fas fa-paper-plane"></i></button>
      </form>

    <?php else: ?>
      <div style="flex:1;display:grid;place-items:center;color:var(--gray-400)">
        <div style="text-align:center">
          <i class="fas fa-comments" style="font-size:48px;margin-bottom:12px"></i>
          <p>Selecione uma conversa ou<br>inicie uma nova pelo perfil do profissional</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!defined('CHAT_EMBEDDED')): ?>
</div></div>
<?php include '../includes/footer.php'; ?>
<?php endif; ?>
