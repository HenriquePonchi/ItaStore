<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
$isCliente   = isset($_SESSION['usuario_id'])   && $_SESSION['usuario_tipo'] === 'cliente';
$isAdmin     = isset($_SESSION['usuario_id'])   && $_SESSION['usuario_tipo'] === 'admin';
$isPrestador = isset($_SESSION['prestador_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="<?= SITE_URL ?>/index.php" class="logo">
      <span class="logo-icon"><i class="fas fa-map-marker-alt"></i></span>
      <span class="logo-text">Serviços<strong>Itanhaém</strong></span>
    </a>

    <form class="header-search" action="<?= SITE_URL ?>/buscar.php" method="GET">
      <i class="fas fa-search"></i>
      <input type="text" name="q" placeholder="Buscar serviço..." value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>">
      <button type="submit">Buscar</button>
    </form>

    <nav class="header-nav">
      <a href="<?= SITE_URL ?>/categorias.php">Categorias</a>
      <?php if ($isAdmin): ?>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn-nav">Painel Admin</a>
        <a href="<?= SITE_URL ?>/includes/logout.php" class="btn-nav btn-outline">Sair</a>
      <?php elseif ($isPrestador): ?>
        <a href="<?= SITE_URL ?>/prestador/dashboard.php" class="btn-nav">Meu Painel</a>
        <a href="<?= SITE_URL ?>/includes/logout.php" class="btn-nav btn-outline">Sair</a>
      <?php elseif ($isCliente): ?>
        <a href="<?= SITE_URL ?>/cliente/dashboard.php" class="btn-nav">Minha Conta</a>
        <a href="<?= SITE_URL ?>/includes/logout.php" class="btn-nav btn-outline">Sair</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="btn-nav btn-outline">Entrar</a>
        <a href="<?= SITE_URL ?>/cadastro.php" class="btn-nav btn-primary">Cadastrar</a>
      <?php endif; ?>
    </nav>

    <button class="hamburger" id="hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>

  <!-- Mobile nav -->
  <div class="mobile-nav" id="mobileNav">
    <a href="<?= SITE_URL ?>/categorias.php">Categorias</a>
    <a href="<?= SITE_URL ?>/buscar.php">Buscar</a>
    <?php if ($isAdmin): ?>
      <a href="<?= SITE_URL ?>/admin/dashboard.php">Painel Admin</a>
      <a href="<?= SITE_URL ?>/includes/logout.php">Sair</a>
    <?php elseif ($isPrestador): ?>
      <a href="<?= SITE_URL ?>/prestador/dashboard.php">Meu Painel</a>
      <a href="<?= SITE_URL ?>/includes/logout.php">Sair</a>
    <?php elseif ($isCliente): ?>
      <a href="<?= SITE_URL ?>/cliente/dashboard.php">Minha Conta</a>
      <a href="<?= SITE_URL ?>/includes/logout.php">Sair</a>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/login.php">Entrar</a>
      <a href="<?= SITE_URL ?>/cadastro.php">Cadastrar</a>
    <?php endif; ?>
  </div>
</header>
