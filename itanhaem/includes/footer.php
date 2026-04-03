<?php // includes/footer.php ?>
<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <div class="logo">
        <span class="logo-icon"><i class="fas fa-map-marker-alt"></i></span>
        <span class="logo-text">Serviços<strong>Itanhaém</strong></span>
      </div>
      <p>O marketplace de serviços locais de Itanhaém/SP. Conectamos quem precisa com quem faz.</p>
    </div>
    <div class="footer-links">
      <h4>Navegação</h4>
      <a href="<?= SITE_URL ?>/index.php">Início</a>
      <a href="<?= SITE_URL ?>/categorias.php">Categorias</a>
      <a href="<?= SITE_URL ?>/buscar.php">Buscar</a>
      <a href="<?= SITE_URL ?>/cadastro.php">Cadastrar Serviço</a>
    </div>
    <div class="footer-links">
      <h4>Conta</h4>
      <a href="<?= SITE_URL ?>/login.php">Entrar</a>
      <a href="<?= SITE_URL ?>/cadastro.php">Criar Conta</a>
    </div>
    <div class="footer-contact">
      <h4>Contato</h4>
      <p><i class="fas fa-map-marker-alt"></i> Itanhaém, São Paulo</p>
      <p><i class="fas fa-envelope"></i> contato@servicositanhaem.com.br</p>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?= date('Y') ?> ServiçosItanhaém. Todos os direitos reservados.</p>
    </div>
  </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
