# 🗺️ ServiçosItanhaém — Guia de Instalação

## Requisitos
- XAMPP (PHP 8.0+ | MySQL 5.7+ | Apache)

---

## ✅ Passo a Passo

### 1. Copiar arquivos
Extraia a pasta `itanhaem` dentro de:
```
C:\xampp\htdocs\itanhaem\
```

### 2. Criar o banco de dados
1. Inicie o **Apache** e o **MySQL** no XAMPP Control Panel
2. Abra o navegador e acesse: `http://localhost/phpmyadmin`
3. Clique em **"Novo"** (barra lateral esquerda)
4. Nome do banco: `servicos_itanhaem` → clique **Criar**
5. Clique na aba **SQL** e cole o conteúdo do arquivo `banco_de_dados.sql`
6. Clique **Executar**

### 3. Configurar conexão (se necessário)
Abra o arquivo `includes/config.php` e verifique:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'servicos_itanhaem');
define('DB_USER', 'root');
define('DB_PASS', '');   // senha padrão do XAMPP é vazia
define('SITE_URL', 'http://localhost/itanhaem');
```

### 4. Permissões de upload
Certifique-se de que as pastas abaixo existem e têm permissão de escrita:
```
uploads/perfil/
uploads/portfolio/
```
No Windows com XAMPP isso já é automático.

### 5. Acessar o site
- **Site:** `http://localhost/itanhaem`
- **Admin:** `http://localhost/itanhaem/login.php`

---

## 🔑 Credenciais padrão

| Tipo | E-mail | Senha |
|------|--------|-------|
| Admin | admin@itanhaem.com | password |
| Prestador demo | joao@email.com | password |
| Cliente (criar sua conta) | — | — |

> ⚠️ **Troque a senha do admin imediatamente após o primeiro acesso!**

---

## 📁 Estrutura de Pastas

```
itanhaem/
├── index.php               ← Página inicial
├── login.php               ← Login
├── cadastro.php            ← Cadastro
├── buscar.php              ← Busca de serviços
├── categorias.php          ← Lista de categorias
├── perfil-prestador.php    ← Perfil público do prestador
├── banco_de_dados.sql      ← Script SQL
├── .htaccess
│
├── admin/
│   └── dashboard.php       ← Painel administrativo
│
├── cliente/
│   ├── dashboard.php       ← Dashboard do cliente
│   ├── chat.php            ← Chat com prestadores
│   └── favoritar.php       ← Endpoint AJAX (favoritos)
│
├── prestador/
│   └── dashboard.php       ← Dashboard do prestador
│
├── includes/
│   ├── config.php          ← Configurações e conexão
│   ├── auth.php            ← Funções de autenticação
│   ├── header.php          ← Cabeçalho global
│   ├── footer.php          ← Rodapé global
│   ├── logout.php          ← Logout
│   └── card_prestador.php  ← Card reutilizável
│
├── assets/
│   ├── css/main.css        ← Estilos globais
│   └── js/main.js          ← Scripts globais
│
└── uploads/
    ├── perfil/             ← Fotos de perfil
    └── portfolio/          ← Imagens do portfólio
```

---

## 🔒 Segurança implementada

- Senhas com `password_hash()` (bcrypt)
- Consultas com PDO Prepared Statements (anti SQL Injection)
- Controle de sessão por tipo de usuário
- Upload restrito por tipo MIME e tamanho (5MB)
- `.htaccess` bloqueando execução de PHP em uploads
- XSS protegido com `htmlspecialchars()`

---

## 💡 Dicas

- Para **destacar** um prestador: Admin → Prestadores → clique no ★
- Para **aprovar** um prestador: Admin → Prestadores → clique no ✓
- O botão **WhatsApp** abre direto no app com mensagem pré-preenchida
- O chat é básico (mensagens de texto); para produção, considere WebSockets

---

Desenvolvido para Itanhaém/SP 🏖️
