<?php
// cliente/favoritar.php — AJAX endpoint
require_once '../includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedCliente()) {
    echo json_encode(['ok'=>false,'msg'=>'Não autenticado']);
    exit;
}
$db = getDB();
$pid = (int)($_POST['prestador_id'] ?? 0);
$uid = $_SESSION['usuario_id'];
if (!$pid) { echo json_encode(['ok'=>false]); exit; }

$chk = $db->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND prestador_id = ?");
$chk->execute([$uid, $pid]);
$fav = $chk->fetchColumn();

if ($fav) {
    $db->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND prestador_id = ?")->execute([$uid,$pid]);
    echo json_encode(['ok'=>true,'favoritado'=>false]);
} else {
    $db->prepare("INSERT INTO favoritos (usuario_id,prestador_id) VALUES (?,?)")->execute([$uid,$pid]);
    echo json_encode(['ok'=>true,'favoritado'=>true]);
}
